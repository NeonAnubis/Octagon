<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\AnalyticsService;
use App\Services\ForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
        private ForecastService $forecast,
    ) {}

    public function index(): JsonResponse
    {
        $events = Event::withCount('sections')
            ->orderBy('event_date')
            ->get()
            ->map(fn ($e) => array_merge($e->toArray(), [
                'sellthrough_pct' => $e->sell_through_percentage,
                'days_to_event' => $e->days_to_event,
            ]));

        return response()->json($events);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->analytics->getEventAnalytics($id));
    }

    public function velocity(int $id): JsonResponse
    {
        return response()->json($this->analytics->getSalesVelocity($id));
    }

    public function forecast(int $id): JsonResponse
    {
        return response()->json($this->forecast->generateForecast($id));
    }
}
