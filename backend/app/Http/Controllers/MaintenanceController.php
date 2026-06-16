<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MaintenanceLog;
use App\Models\MaintenanceRequest;
use App\Models\Station;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    public function index(Request $request, $stationId)
    {
        $perPage = intval($request->query('per_page', 15));
        
        $logs = MaintenanceLog::where('station_id', $stationId)
            ->orderBy('maintenance_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'station_id' => 'required|exists:stations,id',
            'maintenance_date' => 'required|date',
            'description' => 'required|string',
            'status' => 'required|in:completed,in_progress',
            'image' => 'nullable|image|max:5120' // Max 5MB
        ], [
            'station_id.required' => 'กรุณาระบุรหัสสถานี',
            'station_id.exists' => 'ไม่พบสถานีนี้ในระบบ',
            'maintenance_date.required' => 'กรุณาระบุวันที่ดำเนินการ',
            'description.required' => 'กรุณาระบุรายละเอียดการซ่อมบำรุง',
            'status.required' => 'กรุณาระบุสถานะ',
            'status.in' => 'สถานะไม่ถูกต้อง',
            'image.image' => 'ไฟล์ที่ส่งมาต้องเป็นไฟล์ภาพ',
            'image.max' => 'รูปภาพต้องมีขนาดไม่เกิน 5MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }

        $photoUrl = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $path = $request->file('image')->store('maintenance', 'public');
            $photoUrl = asset('storage/' . $path);
        }

        $log = MaintenanceLog::create([
            'station_id' => $request->station_id,
            'maintenance_date' => $request->maintenance_date,
            'description' => $request->description,
            'status' => $request->status,
            'photo_url' => $photoUrl,
            'performed_by' => auth()->user()->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกข้อมูลการซ่อมบำรุงและรูปหลักฐานสำเร็จ',
            'data' => [
                'id' => $log->id,
                'station_id' => $log->station_id,
                'maintenance_date' => $log->maintenance_date,
                'description' => $log->description,
                'status' => $log->status,
                'photo_url' => $log->photo_url,
                'performed_by' => $log->performed_by
            ]
        ], 201);
    }

    public function storeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'station_id' => 'required|exists:stations,id',
            'request_type' => 'required|in:hardware,repair',
            'item_requested' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'reason' => 'required|string',
        ], [
            'station_id.required' => 'กรุณาระบุรหัสสถานี',
            'station_id.exists' => 'ไม่พบสถานีนี้ในระบบ',
            'request_type.required' => 'กรุณาระบุประเภทคำร้อง',
            'request_type.in' => 'ประเภทคำร้องไม่ถูกต้อง',
            'item_requested.required' => 'กรุณาระบุรายการอุปกรณ์',
            'priority.required' => 'กรุณาระบุระดับความเร่งด่วน',
            'priority.in' => 'ระดับความเร่งด่วนไม่ถูกต้อง',
            'reason.required' => 'กรุณาระบุเหตุผลการร้องขอ'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลคำร้องขอไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate Ticket Number (Format: REQ-YYYYMMDD-XX)
        $ticketNumber = 'REQ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        $req = MaintenanceRequest::create([
            'ticket_number' => $ticketNumber,
            'station_id' => $request->station_id,
            'request_type' => $request->request_type,
            'item_requested' => $request->item_requested,
            'priority' => $request->priority,
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => "ส่งคำร้องขอเรียบร้อยแล้ว หมายเลขอ้างอิง #{$ticketNumber}",
            'data' => [
                'ticket_number' => $req->ticket_number,
                'status' => $req->status
            ]
        ], 201);
    }
}
