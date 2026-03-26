<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::with(['event', 'section'])
            ->when($request->input('event_id'), fn ($q, $id) => $q->where('event_id', $id))
            ->when($request->boolean('unresolved', true), fn ($q) => $q->where('is_resolved', false))
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        return response()->json($alerts);
    }

    public function resolve(int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);
        $alert->update(['is_resolved' => true, 'resolved_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markRead(int $id): JsonResponse
    {
        Alert::findOrFail($id)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
