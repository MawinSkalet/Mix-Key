<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Alert;

class AlertController extends Controller
{
    public function activeAlerts()
    {
        $alerts = Alert::where('expires_at', '>', now())
            ->orderBy('announced_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $alerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'severity' => $alert->severity,
                    'announced_at' => $alert->announced_at,
                    'expires_at' => $alert->expires_at
                ];
            })
        ]);
    }
}
