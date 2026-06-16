<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Station;
use App\Models\Telemetry;
use App\Models\StaffNotification;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TelemetryIngestController extends Controller
{
    public function ingest(Request $request)
    {
        $apiKey = $request->header('X-API-KEY');
        
        // Accept either the default secret password or the UI test key
        $validKey1 = env('MQTT_PASSWORD', 'secret');
        $validKey2 = 'iot_secret_credentials_key_2026';

        if (!$apiKey || ($apiKey !== $validKey1 && $apiKey !== $validKey2)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid X-API-KEY'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'sensor_id' => 'required|string',
            'water_level_cm' => 'required|numeric',
            'battery_voltage' => 'nullable|numeric',
            'signal_strength_rssi' => 'nullable|integer',
            'timestamp' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูล Telemetry ไม่ผ่านเกณฑ์ Validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Map sensor_id to station ID (PT-01 -> 1, BL-01 -> 2)
        $sensorId = $request->sensor_id;
        $stationId = str_starts_with(strtoupper($sensorId), 'PT') ? 1 : 2;

        $station = Station::find($stationId);
        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสถานีที่เชื่อมโยงกับ Sensor ID นี้'
            ], 404);
        }

        $newDatum = round($request->water_level_cm / 100, 2);
        $delta = $newDatum - $station->water_height_datum;
        
        // Determine trend
        $trend = 'stable';
        if ($delta > 0.01) {
            $trend = 'rising';
        } elseif ($delta < -0.01) {
            $trend = 'falling';
        }

        // Save old status to compare for notifications
        $oldStatus = 'normal';
        if ($station->water_height_datum >= $station->critical_datum_m) {
            $oldStatus = 'critical';
        } elseif ($station->water_height_datum >= $station->warning_datum_m) {
            $oldStatus = 'warning';
        }

        // Calculate new MSL
        $newMsl = round($station->calculated_msl + $delta, 2);

        // Update station stats
        $station->update([
            'water_height_datum' => $newDatum,
            'calculated_msl' => $newMsl,
            'trend' => $trend,
            'battery_voltage' => $request->has('battery_voltage') ? floatval($request->battery_voltage) : $station->battery_voltage,
            'signal_strength_rssi' => $request->has('signal_strength_rssi') ? intval($request->signal_strength_rssi) : $station->signal_strength_rssi,
            'is_online' => true,
        ]);

        // Save telemetry log
        $timestamp = $request->timestamp ? Carbon::parse($request->timestamp) : now();
        Telemetry::create([
            'station_id' => $station->id,
            'timestamp' => $timestamp,
            'water_height_datum' => $newDatum,
            'calculated_msl' => $newMsl
        ]);

        // Check new status
        $newStatus = 'normal';
        if ($newDatum >= $station->critical_datum_m) {
            $newStatus = 'critical';
        } elseif ($newDatum >= $station->warning_datum_m) {
            $newStatus = 'warning';
        }

        // Trigger notifications on threshold breach state changes
        if ($newStatus === 'critical' && $oldStatus !== 'critical') {
            $notification = StaffNotification::create([
                'station_id' => $station->id,
                'severity' => 'critical',
                'message' => "ระดับน้ำสูงวิกฤตล้นตลิ่ง: {$newDatum} ม. (Threshold: {$station->critical_datum_m} ม.) แนะนำให้เตรียมประกาศแจ้งเตือนชุมชนท้ายน้ำ",
                'triggered_at' => now(),
                'is_read' => false
            ]);
            $this->dispatchToSriGateway($notification);
        } elseif ($newStatus === 'warning' && $oldStatus === 'normal') {
            $notification = StaffNotification::create([
                'station_id' => $station->id,
                'severity' => 'warning',
                'message' => "ระดับน้ำเข้าสู่จุดเตือนภัย: {$newDatum} ม. (Threshold: {$station->warning_datum_m} ม.) โปรดเฝ้าระวังอย่างใกล้ชิด",
                'triggered_at' => now(),
                'is_read' => false
            ]);
            $this->dispatchToSriGateway($notification);
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกข้อมูล Telemetry เรียบร้อยแล้ว'
        ]);
    }

    /**
     * Dispatch CAP JSON alert outbound to Sri Gateway.
     */
    protected function dispatchToSriGateway(StaffNotification $notification)
    {
        try {
            $capService = app(\App\Services\CapService::class);
            $payload = $capService->generateJson($notification);
            
            $url = 'https://sri-alert.kku.ac.th/alert';
            $token = env('SRI_ALERT_BEARER_TOKEN', 'mock_token_sri_gateway_2026');

            // Dispatch request
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->withHeaders(['content-type' => 'application/json'])
                ->post($url, $payload);

            // Log details
            \Illuminate\Support\Facades\Log::info("Dispatched CAP alert to Sri Gateway", [
                'notification_id' => $notification->id,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to dispatch CAP alert to Sri Gateway: " . $e->getMessage());
        }
    }
}
