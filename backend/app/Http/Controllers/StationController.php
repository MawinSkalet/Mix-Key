<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Station;
use App\Models\Telemetry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function index(Request $request)
    {
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $radius = $request->query('radius'); // in km

        $stations = Station::all()->map(function ($station) {
            // Determine status based on thresholds
            $status = 'normal';
            if ($station->water_height_datum >= $station->critical_datum_m) {
                $status = 'critical';
            } elseif ($station->water_height_datum >= $station->warning_datum_m) {
                $status = 'warning';
            }

            return [
                'id' => $station->id,
                'name' => $station->name,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'status' => $status,
                'current_water_level' => [
                    'water_height_datum' => $station->water_height_datum,
                    'calculated_msl' => $station->calculated_msl,
                    'trend' => $station->trend ?: 'stable',
                    'rain_status' => $station->rain_status ?: 'none',
                    'last_updated_at' => $station->updated_at->toIso8601String(),
                ]
            ];
        });

        // Simulating Spatial Query using Haversine formula if lat/lng are supplied
        if ($latitude !== null && $longitude !== null) {
            $lat1 = floatval($latitude);
            $lon1 = floatval($longitude);
            $maxRadius = $radius !== null ? floatval($radius) : null;

            $stations = $stations->map(function ($station) use ($lat1, $lon1) {
                $R = 6371; // Earth's radius in km
                $dLat = deg2rad($station['latitude'] - $lat1);
                $dLon = deg2rad($station['longitude'] - $lon1);
                $a = sin($dLat / 2) * sin($dLat / 2) +
                     cos(deg2rad($lat1)) * cos(deg2rad($station['latitude'])) *
                     sin($dLon / 2) * sin($dLon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $distance = $R * $c;

                $station['distance_km'] = round($distance, 2);
                return $station;
            });

            if ($maxRadius !== null) {
                $stations = $stations->filter(function ($station) use ($maxRadius) {
                    return $station['distance_km'] <= $maxRadius;
                });
            }

            $stations = $stations->sortBy('distance_km')->values();
        }

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    }

    public function show($id)
    {
        $station = Station::find($id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบสถานี ID {$id}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $station->id,
                'name' => $station->name,
                'location_description' => $station->location_description,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'thresholds' => [
                    'warning_datum_m' => $station->warning_datum_m,
                    'critical_datum_m' => $station->critical_datum_m,
                ],
                'future_features' => [
                    'rating_curve_enabled' => (bool)$station->rating_curve_enabled,
                    'rating_curve_version' => $station->rating_curve_version,
                    'flow_rate_q_m3s' => $station->flow_rate_q_m3s,
                    'velocity_v_ms' => $station->velocity_v_ms,
                ]
            ]
        ]);
    }

    public function waterLevelHistory(Request $request, $id)
    {
        $station = Station::find($id);
        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบสถานี ID {$id}"
            ], 404);
        }

        $range = $request->query('range', '6h');
        $perPage = intval($request->query('per_page', 100));

        $query = Telemetry::where('station_id', $id)->orderBy('timestamp', 'desc');

        if ($range === '6h') {
            // Raw telemetry logs (15m intervals) for the last 6 hours
            $query->where('timestamp', '>=', now()->subHours(6));
        } elseif ($range === '1m') {
            // Hourly averages for the last 1 month (PostgreSQL date_trunc)
            $query = Telemetry::where('station_id', $id)
                ->where('timestamp', '>=', now()->subMonth())
                ->select(
                    DB::raw("date_trunc('hour', timestamp) as timestamp"),
                    DB::raw("AVG(water_height_datum) as water_height_datum"),
                    DB::raw("AVG(calculated_msl) as calculated_msl")
                )
                ->groupBy(DB::raw("date_trunc('hour', timestamp)"))
                ->orderBy('timestamp', 'desc');
        } elseif ($range === '3m' || $range === '6m') {
            // Daily averages for the last 3 or 6 months
            $months = $range === '3m' ? 3 : 6;
            $query = Telemetry::where('station_id', $id)
                ->where('timestamp', '>=', now()->subMonths($months))
                ->select(
                    DB::raw("date_trunc('day', timestamp) as timestamp"),
                    DB::raw("AVG(water_height_datum) as water_height_datum"),
                    DB::raw("AVG(calculated_msl) as calculated_msl")
                )
                ->groupBy(DB::raw("date_trunc('day', timestamp)"))
                ->orderBy('timestamp', 'desc');
        }

        $paginated = $query->paginate($perPage);

        // Map items for standardized response format
        $data = collect($paginated->items())->map(function ($item) {
            // Convert to timestamp string
            $ts = $item->timestamp instanceof \Carbon\Carbon 
                ? $item->timestamp->toIso8601String() 
                : (is_string($item->timestamp) ? \Carbon\Carbon::parse($item->timestamp)->toIso8601String() : $item->timestamp);

            return [
                'timestamp' => $ts,
                'water_height_datum' => round(floatval($item->water_height_datum), 2),
                'calculated_msl' => round(floatval($item->calculated_msl), 2),
            ];
        });

        return response()->json([
            'success' => true,
            'range' => $range,
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    public function telemetry($id)
    {
        $station = Station::find($id);
        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบสถานี ID {$id}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'station_id' => $station->id,
                'sensor_id' => $station->id === 1 ? 'PT-01' : 'BL-01',
                'is_online' => (bool)$station->is_online,
                'battery_voltage' => $station->battery_voltage,
                'last_communication_at' => $station->updated_at->toIso8601String(),
                'signal_strength_rssi' => $station->signal_strength_rssi,
            ]
        ]);
    }

    public function updateThresholds(Request $request, $id)
    {
        $station = Station::find($id);
        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบสถานี ID {$id}"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'warning_datum_m' => 'required|numeric',
            'critical_datum_m' => 'required|numeric',
        ], [
            'warning_datum_m.required' => 'กรุณาระบุ warning_datum_m',
            'warning_datum_m.numeric' => 'ค่า warning_datum_m ต้องเป็นตัวเลข',
            'critical_datum_m.required' => 'กรุณาระบุ critical_datum_m',
            'critical_datum_m.numeric' => 'ค่า critical_datum_m ต้องเป็นตัวเลข',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }

        $warning = floatval($request->warning_datum_m);
        $critical = floatval($request->critical_datum_m);

        if ($critical <= $warning) {
            return response()->json([
                'success' => false,
                'message' => 'ค่าขีดจำกัดวิกฤต (critical_datum_m) ต้องสูงกว่าค่าขีดจำกัดเตือนภัย (warning_datum_m)'
            ], 422);
        }

        $station->update([
            'warning_datum_m' => $warning,
            'critical_datum_m' => $critical
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปรับปรุงค่าขีดจำกัดระดับเตือนภัยสำเร็จ',
            'data' => [
                'station_id' => $station->id,
                'warning_datum_m' => $station->warning_datum_m,
                'critical_datum_m' => $station->critical_datum_m,
                'updated_by' => auth()->user()->name,
            ]
        ]);
    }
}
