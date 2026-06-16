<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\StaffNotification;
use App\Models\Station;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $unreadOnly = $request->query('unread_only') === 'true';
        $perPage = intval($request->query('per_page', 15));

        $query = StaffNotification::orderBy('triggered_at', 'desc');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate($perPage);

        // Map data to match API doc specs
        $data = collect($notifications->items())->map(function ($n) {
            $station = Station::find($n->station_id);
            return [
                'id' => $n->id,
                'station_id' => $n->station_id,
                'station_name' => $station ? $station->name : 'ไม่ระบุสถานี',
                'severity' => $n->severity,
                'message' => $n->message,
                'is_read' => (bool)$n->is_read,
                'triggered_at' => $n->triggered_at
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total()
            ]
        ]);
    }

    public function markAsRead($id)
    {
        $notification = StaffNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => "ไม่พบแจ้งเตือน ID {$id}"
            ], 404);
        }

        $notification->update([
            'is_read' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการรับทราบแจ้งเตือนสำเร็จ',
            'data' => [
                'notification_id' => $notification->id,
                'is_read' => true,
                'read_at' => now()->toIso8601String(),
                'read_by' => auth()->user()->name
            ]
        ]);
    }
}
