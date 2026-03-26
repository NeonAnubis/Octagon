<?php

namespace App\Services\Integrations;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsService
{
    private string $baseUrl = 'https://googleads.googleapis.com/v17';
    private ?string $developerToken;
    private ?string $accessToken;
    private ?string $customerId;

    public function __construct()
    {
        $this->developerToken = config('services.google_ads.developer_token');
        $this->accessToken = config('services.google_ads.access_token');
        $this->customerId = config('services.google_ads.customer_id');
    }

    public function isConfigured(): bool
    {
        return !empty($this->developerToken) && !empty($this->accessToken);
    }

    /**
     * Execute a Google Ads Query Language (GAQL) query
     */
    public function query(string $gaql): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'developer-token' => $this->developerToken,
            ])
            ->post("{$this->baseUrl}/customers/{$this->customerId}/googleAds:searchStream", [
                'query' => $gaql,
            ]);

        if (!$response->successful()) {
            Log::error('Google Ads query failed', [
                'status' => $response->status(),
                'query' => $gaql,
            ]);
            return [];
        }

        return $response->json();
    }

    /**
     * Get all campaigns with their performance metrics
     */
    public function getCampaigns(string $since, string $until): array
    {
        $gaql = "
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.advertising_channel_type,
                campaign.start_date,
                campaign.end_date,
                campaign_budget.amount_micros,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.conversions_value,
                metrics.average_cpc,
                metrics.ctr,
                metrics.average_cpm
            FROM campaign
            WHERE segments.date BETWEEN '{$since}' AND '{$until}'
                AND campaign.status != 'REMOVED'
            ORDER BY metrics.cost_micros DESC
        ";

        return $this->query($gaql);
    }

    /**
     * Get daily campaign metrics for a specific campaign
     */
    public function getCampaignDailyMetrics(string $campaignId, string $since, string $until): array
    {
        $gaql = "
            SELECT
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.conversions_value,
                metrics.average_cpc,
                metrics.ctr,
                metrics.search_impression_share
            FROM campaign
            WHERE campaign.id = {$campaignId}
                AND segments.date BETWEEN '{$since}' AND '{$until}'
            ORDER BY segments.date
        ";

        return $this->query($gaql);
    }

    /**
     * Get search term performance for keyword insights
     */
    public function getSearchTermReport(string $since, string $until, int $limit = 100): array
    {
        $gaql = "
            SELECT
                search_term_view.search_term,
                metrics.impressions,
                metrics.clicks,
                metrics.cost_micros,
                metrics.conversions,
                metrics.ctr
            FROM search_term_view
            WHERE segments.date BETWEEN '{$since}' AND '{$until}'
                AND metrics.impressions > 10
            ORDER BY metrics.conversions DESC
            LIMIT {$limit}
        ";

        return $this->query($gaql);
    }

    /**
     * Get audience/demographic insights from display and performance max campaigns
     */
    public function getAudienceInsights(string $since, string $until): array
    {
        $gaql = "
            SELECT
                ad_group_criterion.age_range.type,
                ad_group_criterion.gender.type,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.cost_micros
            FROM gender_view
            WHERE segments.date BETWEEN '{$since}' AND '{$until}'
            ORDER BY metrics.conversions DESC
        ";

        return $this->query($gaql);
    }

    /**
     * Sync Google Ads campaigns and daily metrics into database
     */
    public function syncCampaigns(?int $eventId = null, string $since = null, string $until = null): int
    {
        $since = $since ?? now()->subDays(30)->format('Y-m-d');
        $until = $until ?? now()->format('Y-m-d');

        $results = $this->getCampaigns($since, $until);
        $count = 0;

        foreach ($results as $batch) {
            foreach ($batch['results'] ?? [] as $row) {
                $c = $row['campaign'] ?? [];
                $m = $row['metrics'] ?? [];

                $campaign = Campaign::updateOrCreate(
                    ['external_id' => $c['id'] ?? '', 'source' => 'google'],
                    [
                        'event_id' => $eventId,
                        'name' => $c['name'] ?? 'Unknown',
                        'type' => 'paid',
                        'status' => strtolower($c['status'] ?? '') === 'ENABLED' ? 'active' : 'paused',
                        'start_date' => $c['startDate'] ?? null,
                        'end_date' => $c['endDate'] ?? null,
                        'budget' => isset($row['campaignBudget']['amountMicros'])
                            ? $row['campaignBudget']['amountMicros'] / 1_000_000
                            : null,
                    ]
                );

                // Fetch daily metrics for this campaign
                $dailyMetrics = $this->getCampaignDailyMetrics($c['id'], $since, $until);

                foreach ($dailyMetrics as $dayBatch) {
                    foreach ($dayBatch['results'] ?? [] as $dayRow) {
                        $dm = $dayRow['metrics'] ?? [];
                        $date = $dayRow['segments']['date'] ?? null;

                        if (!$date) continue;

                        $spend = ($dm['costMicros'] ?? 0) / 1_000_000;
                        $conversions = (int) ($dm['conversions'] ?? 0);
                        $revenue = $dm['conversionsValue'] ?? 0;

                        CampaignMetric::updateOrCreate(
                            ['campaign_id' => $campaign->id, 'date' => $date],
                            [
                                'event_id' => $eventId,
                                'spend' => $spend,
                                'impressions' => $dm['impressions'] ?? 0,
                                'clicks' => $dm['clicks'] ?? 0,
                                'conversions' => $conversions,
                                'revenue_attributed' => $revenue,
                                'cpm' => ($dm['impressions'] ?? 0) > 0
                                    ? round($spend / ($dm['impressions'] / 1000), 2)
                                    : 0,
                                'cpc' => ($dm['averageCpc'] ?? 0) / 1_000_000,
                                'cpa' => $conversions > 0 ? round($spend / $conversions, 2) : 0,
                                'roas' => $spend > 0 ? round($revenue / $spend, 2) : 0,
                                'ctr' => $dm['ctr'] ?? 0,
                            ]
                        );
                    }
                }

                $count++;
            }
        }

        return $count;
    }
}
