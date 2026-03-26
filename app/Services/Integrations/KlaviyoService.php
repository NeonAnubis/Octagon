<?php

namespace App\Services\Integrations;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KlaviyoService
{
    private string $baseUrl = 'https://a.klaviyo.com/api';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.klaviyo.api_key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    private function request(string $endpoint, array $params = []): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Klaviyo-API-Key ' . $this->apiKey,
                'revision' => '2024-10-15',
                'Accept' => 'application/json',
            ])
            ->get("{$this->baseUrl}/{$endpoint}", $params);

        if (!$response->successful()) {
            Log::error('Klaviyo API failed', ['endpoint' => $endpoint, 'status' => $response->status()]);
            return [];
        }

        return $response->json('data', []);
    }

    /**
     * Get all email campaigns
     */
    public function getCampaigns(string $filter = null): array
    {
        $params = [];
        if ($filter) {
            $params['filter'] = $filter;
        }

        return $this->request('campaigns', $params);
    }

    /**
     * Get campaign send/open/click metrics
     */
    public function getCampaignMetrics(string $campaignId): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Klaviyo-API-Key ' . $this->apiKey,
                'revision' => '2024-10-15',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/campaign-values-reports/", [
                'data' => [
                    'type' => 'campaign-values-report',
                    'attributes' => [
                        'statistics' => [
                            'unique_recipients', 'opens', 'unique_opens',
                            'clicks', 'unique_clicks', 'unsubscribes',
                            'revenue', 'conversion_count',
                        ],
                        'timeframe' => ['key' => 'last_365_days'],
                        'conversion_metric_id' => 'Placed Order',
                        'filter' => "equals(campaign_id,\"{$campaignId}\")",
                    ],
                ],
            ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * Get flows (automated email sequences)
     */
    public function getFlows(): array
    {
        return $this->request('flows');
    }

    /**
     * Get lists/segments for audience analysis
     */
    public function getLists(): array
    {
        return $this->request('lists');
    }

    /**
     * Get subscriber profiles for targeting insights
     */
    public function getProfiles(string $listId, int $limit = 100): array
    {
        return $this->request("lists/{$listId}/profiles", [
            'page[size]' => $limit,
            'fields[profile]' => 'email,location,properties',
        ]);
    }

    /**
     * Sync Klaviyo campaigns and metrics into the database
     */
    public function syncCampaigns(?int $eventId = null): int
    {
        $klaviyoCampaigns = $this->getCampaigns();
        $count = 0;

        foreach ($klaviyoCampaigns as $kc) {
            $attrs = $kc['attributes'] ?? [];

            $campaign = Campaign::updateOrCreate(
                ['external_id' => $kc['id'], 'source' => 'klaviyo'],
                [
                    'event_id' => $eventId,
                    'name' => $attrs['name'] ?? 'Unknown',
                    'type' => 'email',
                    'status' => ($attrs['status'] ?? '') === 'Sent' ? 'completed' : 'active',
                    'start_date' => isset($attrs['send_time']) ? date('Y-m-d', strtotime($attrs['send_time'])) : null,
                ]
            );

            // Sync metrics
            $metrics = $this->getCampaignMetrics($kc['id']);

            foreach ($metrics as $metric) {
                $stats = $metric['attributes']['statistics'] ?? [];
                $sends = $stats['unique_recipients'] ?? 0;
                $opens = $stats['unique_opens'] ?? 0;
                $clicks = $stats['unique_clicks'] ?? 0;

                CampaignMetric::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'date' => $campaign->start_date ?? now()->format('Y-m-d'),
                    ],
                    [
                        'event_id' => $eventId,
                        'sends' => $sends,
                        'opens' => $opens,
                        'unique_clicks' => $clicks,
                        'open_rate' => $sends > 0 ? round($opens / $sends, 4) : 0,
                        'click_rate' => $opens > 0 ? round($clicks / $opens, 4) : 0,
                        'conversions' => $stats['conversion_count'] ?? 0,
                        'revenue_attributed' => $stats['revenue'] ?? 0,
                        'impressions' => $sends,
                        'clicks' => $clicks,
                        'reach' => $opens,
                    ]
                );
            }

            $count++;
        }

        return $count;
    }
}
