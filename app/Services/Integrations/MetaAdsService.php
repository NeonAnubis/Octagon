<?php

namespace App\Services\Integrations;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaAdsService
{
    private string $baseUrl = 'https://graph.facebook.com/v21.0';
    private ?string $accessToken;

    public function __construct()
    {
        $this->accessToken = config('services.meta_ads.access_token');
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessToken);
    }

    /**
     * Get all ad accounts accessible with the current token
     */
    public function getAdAccounts(): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/me/adaccounts", [
            'access_token' => $this->accessToken,
            'fields' => 'name,account_id,account_status,currency,business',
        ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * Get campaigns for an ad account
     */
    public function getCampaigns(string $accountId): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/act_{$accountId}/campaigns", [
            'access_token' => $this->accessToken,
            'fields' => 'name,status,objective,daily_budget,lifetime_budget,start_time,stop_time',
            'limit' => 100,
        ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * Get campaign insights (performance metrics) for a date range
     */
    public function getCampaignInsights(string $campaignId, string $since, string $until): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/{$campaignId}/insights", [
            'access_token' => $this->accessToken,
            'fields' => 'campaign_name,spend,impressions,clicks,reach,actions,cost_per_action_type,cpm,cpc,ctr,frequency',
            'time_range' => json_encode(['since' => $since, 'until' => $until]),
            'time_increment' => 1, // daily breakdown
            'limit' => 100,
        ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * Get ad set level data for audience targeting insights
     */
    public function getAdSetInsights(string $accountId, string $since, string $until): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/act_{$accountId}/insights", [
            'access_token' => $this->accessToken,
            'fields' => 'adset_name,spend,impressions,clicks,actions,cost_per_action_type',
            'level' => 'adset',
            'time_range' => json_encode(['since' => $since, 'until' => $until]),
            'breakdowns' => 'age,gender',
            'limit' => 200,
        ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * Get audience demographic breakdown for targeting suggestions
     */
    public function getAudienceBreakdown(string $accountId, string $since, string $until): array
    {
        $breakdowns = ['age,gender', 'country', 'region', 'platform_position'];
        $results = [];

        foreach ($breakdowns as $breakdown) {
            $response = Http::timeout(30)->get("{$this->baseUrl}/act_{$accountId}/insights", [
                'access_token' => $this->accessToken,
                'fields' => 'spend,impressions,clicks,actions,cost_per_action_type',
                'time_range' => json_encode(['since' => $since, 'until' => $until]),
                'breakdowns' => $breakdown,
                'limit' => 100,
            ]);

            if ($response->successful()) {
                $results[$breakdown] = $response->json('data', []);
            }
        }

        return $results;
    }

    /**
     * Sync Meta campaigns and metrics into the database
     */
    public function syncCampaigns(string $accountId, ?int $eventId = null): int
    {
        $metaCampaigns = $this->getCampaigns($accountId);
        $count = 0;

        foreach ($metaCampaigns as $mc) {
            $campaign = Campaign::updateOrCreate(
                ['external_id' => $mc['id'], 'source' => 'meta'],
                [
                    'event_id' => $eventId,
                    'name' => $mc['name'],
                    'type' => 'paid',
                    'status' => strtolower($mc['status'] ?? 'active') === 'active' ? 'active' : 'paused',
                    'start_date' => isset($mc['start_time']) ? date('Y-m-d', strtotime($mc['start_time'])) : null,
                    'end_date' => isset($mc['stop_time']) ? date('Y-m-d', strtotime($mc['stop_time'])) : null,
                    'budget' => ($mc['lifetime_budget'] ?? $mc['daily_budget'] ?? 0) / 100,
                ]
            );

            // Sync daily insights
            $since = $campaign->start_date?->format('Y-m-d') ?? now()->subDays(30)->format('Y-m-d');
            $until = now()->format('Y-m-d');
            $insights = $this->getCampaignInsights($mc['id'], $since, $until);

            foreach ($insights as $day) {
                $conversions = collect($day['actions'] ?? [])
                    ->where('action_type', 'offsite_conversion.fb_pixel_purchase')
                    ->sum('value');

                CampaignMetric::updateOrCreate(
                    ['campaign_id' => $campaign->id, 'date' => $day['date_start']],
                    [
                        'event_id' => $eventId,
                        'spend' => $day['spend'] ?? 0,
                        'impressions' => $day['impressions'] ?? 0,
                        'clicks' => $day['clicks'] ?? 0,
                        'conversions' => $conversions,
                        'cpm' => $day['cpm'] ?? 0,
                        'cpc' => $day['cpc'] ?? 0,
                        'ctr' => ($day['ctr'] ?? 0) / 100,
                        'reach' => $day['reach'] ?? 0,
                    ]
                );
            }

            $count++;
        }

        return $count;
    }
}
