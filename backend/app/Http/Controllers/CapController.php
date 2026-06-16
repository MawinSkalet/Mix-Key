<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CapService;
use App\Models\Alert;
use Exception;

class CapController extends Controller
{
    protected $capService;

    public function __construct(CapService $capService)
    {
        $this->capService = $capService;
    }

    /**
     * Handle inbound CAP XML or JSON alerts from Sri Gateway or external agencies.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function inboundAlert(Request $request)
    {
        $payload = $request->getContent();
        $contentType = $request->header('Content-Type', '');

        if (empty(trim($payload))) {
            return response()->json([
                'success' => false,
                'message' => 'Empty request payload'
            ], 400);
        }

        try {
            // Parse CAP payload into local alert attributes
            $alertData = $this->capService->parseCap($payload, $contentType);

            // Create local alert record
            $alert = Alert::create([
                'type' => $alertData['type'],
                'title' => $alertData['title'],
                'message' => $alertData['message'],
                'severity' => $alertData['severity'],
                'announced_at' => $alertData['announced_at'],
                'expires_at' => $alertData['expires_at']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'CAP alert processed and imported successfully',
                'data' => [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'title' => $alert->title,
                    'severity' => $alert->severity,
                    'announced_at' => $alert->announced_at->toIso8601String(),
                    'expires_at' => $alert->expires_at->toIso8601String()
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process CAP alert: ' . $e->getMessage()
            ], 400);
        }
    }
}
