<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    public function overview(): JsonResponse
    {
        return response()->json($this->analytics->getDashboardOverview());
    }
}
