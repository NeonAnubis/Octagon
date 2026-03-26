<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'event_id' => 'nullable|integer|exists:events,id',
            'session_id' => 'nullable|string|max:100',
        ]);

        $response = $this->ai->chat(
            $request->input('message'),
            $request->input('event_id'),
            $request->input('session_id', 'session_' . uniqid()),
        );

        return response()->json($response);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $recommendations = $this->ai->generateRecommendations(
            $request->input('event_id')
        );

        return response()->json($recommendations);
    }
}
