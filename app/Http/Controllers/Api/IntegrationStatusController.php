<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integrations\GoogleAdsService;
use App\Services\Integrations\KlaviyoService;
use App\Services\Integrations\MetaAdsService;
use App\Services\Integrations\TicketmasterService;
use Illuminate\Http\JsonResponse;

class IntegrationStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'integrations' => [
                [
                    'name' => 'Ticketmaster',
                    'source' => 'ticketmaster',
                    'type' => 'sales',
                    'configured' => app(TicketmasterService::class)->isConfigured(),
                    'description' => 'Event discovery, ticket inventory, and sales data',
                ],
                [
                    'name' => 'Meta Ads',
                    'source' => 'meta',
                    'type' => 'paid',
                    'configured' => app(MetaAdsService::class)->isConfigured(),
                    'description' => 'Facebook & Instagram ad campaigns, spend, and conversions',
                ],
                [
                    'name' => 'Google Ads',
                    'source' => 'google',
                    'type' => 'paid',
                    'configured' => app(GoogleAdsService::class)->isConfigured(),
                    'description' => 'Search, display, and performance max campaigns',
                ],
                [
                    'name' => 'Klaviyo',
                    'source' => 'klaviyo',
                    'type' => 'email',
                    'configured' => app(KlaviyoService::class)->isConfigured(),
                    'description' => 'Email campaigns, flows, open/click rates, and revenue',
                ],
                [
                    'name' => 'Instagram',
                    'source' => 'instagram',
                    'type' => 'organic',
                    'configured' => !empty(config('services.meta_ads.access_token')),
                    'description' => 'Organic post reach, engagement, and follower growth',
                ],
                [
                    'name' => 'X / Twitter',
                    'source' => 'twitter',
                    'type' => 'organic',
                    'configured' => false,
                    'description' => 'Tweet impressions, engagement, and follower metrics',
                ],
                [
                    'name' => 'YouTube',
                    'source' => 'youtube',
                    'type' => 'organic',
                    'configured' => false,
                    'description' => 'Video views, watch time, subscribers, and engagement',
                ],
            ],
        ]);
    }
}
