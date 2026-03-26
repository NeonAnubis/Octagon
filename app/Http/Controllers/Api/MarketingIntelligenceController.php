<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MarketingIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketingIntelligenceController extends Controller
{
    public function __construct(private MarketingIntelligenceService $intelligence) {}

    public function timing(Request $request): JsonResponse
    {
        return response()->json(
            $this->intelligence->getCampaignTimingRecommendations($request->input('event_id'))
        );
    }

    public function audience(Request $request): JsonResponse
    {
        return response()->json(
            $this->intelligence->getAudienceTargetingSuggestions($request->input('event_id'))
        );
    }

    public function spendOptimization(Request $request): JsonResponse
    {
        return response()->json(
            $this->intelligence->getSpendOptimization($request->input('event_id'))
        );
    }

    public function historicalComparison(int $eventId): JsonResponse
    {
        return response()->json(
            $this->intelligence->getHistoricalComparison($eventId)
        );
    }
}
