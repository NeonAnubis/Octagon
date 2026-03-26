<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->analytics->getMarketingAnalytics($request->input('event_id'))
        );
    }

    public function campaigns(Request $request): JsonResponse
    {
        $campaigns = Campaign::with('event')
            ->when($request->input('event_id'), fn ($q, $id) => $q->where('event_id', $id))
            ->when($request->input('source'), fn ($q, $s) => $q->where('source', $s))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($c) {
                $totalSpend = $c->metrics()->sum('spend');
                $totalConversions = $c->metrics()->sum('conversions');
                $totalRevenue = $c->metrics()->sum('revenue_attributed');
                return array_merge($c->toArray(), [
                    'total_spend' => round($totalSpend, 2),
                    'total_conversions' => $totalConversions,
                    'total_revenue' => round($totalRevenue, 2),
                    'roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0,
                ]);
            });

        return response()->json($campaigns);
    }
}
