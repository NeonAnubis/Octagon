<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\Event;
use App\Models\SalesSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardOverview(): array
    {
        $upcomingEvents = Event::where('status', '!=', 'completed')
            ->orderBy('event_date')
            ->get();

        $completedEvents = Event::where('status', 'completed')
            ->orderBy('event_date', 'desc')
            ->limit(5)
            ->get();

        $totalRevenue = Event::sum('total_revenue');
        $totalSold = Event::sum('total_sold');
        $totalCapacity = Event::sum('total_capacity');
        $avgSellthrough = $totalCapacity > 0 ? round(($totalSold / $totalCapacity) * 100, 1) : 0;

        $totalAdSpend = CampaignMetric::sum('spend');
        $totalConversions = CampaignMetric::sum('conversions');
        $overallRoas = $totalAdSpend > 0
            ? round(CampaignMetric::sum('revenue_attributed') / $totalAdSpend, 2)
            : 0;

        $activeAlerts = DB::table('alerts')
            ->where('is_resolved', false)
            ->count();

        return [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_tickets_sold' => $totalSold,
                'avg_sellthrough' => $avgSellthrough,
                'total_ad_spend' => $totalAdSpend,
                'total_conversions' => $totalConversions,
                'overall_roas' => $overallRoas,
                'active_alerts' => $activeAlerts,
                'upcoming_events' => $upcomingEvents->count(),
            ],
            'upcoming_events' => $upcomingEvents,
            'completed_events' => $completedEvents,
        ];
    }

    public function getEventAnalytics(int $eventId): array
    {
        $event = Event::with(['sections', 'forecasts', 'alerts' => function ($q) {
            $q->where('is_resolved', false)->orderBy('severity');
        }])->findOrFail($eventId);

        $velocityTrend = SalesSnapshot::where('event_id', $eventId)
            ->selectRaw('DATE(captured_at) as date, SUM(sold) as total_sold, AVG(velocity_daily) as avg_velocity, AVG(sellthrough_pct) as avg_sellthrough')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $sectionPerformance = $event->sections->map(function ($section) {
            $latestSnapshot = $section->snapshots()->latest('captured_at')->first();
            return [
                'id' => $section->id,
                'name' => $section->name,
                'price_tier' => $section->price_tier,
                'capacity' => $section->capacity,
                'sold' => $section->sold,
                'sellthrough' => $section->sell_through_percentage,
                'revenue' => $section->revenue,
                'status' => $section->status,
                'current_price' => $section->current_price,
                'base_price' => $section->base_price,
                'velocity' => $latestSnapshot?->velocity_daily ?? 0,
            ];
        });

        return [
            'event' => $event,
            'velocity_trend' => $velocityTrend,
            'section_performance' => $sectionPerformance,
            'forecasts' => $event->forecasts,
            'alerts' => $event->alerts,
        ];
    }

    public function getMarketingAnalytics(?int $eventId = null): array
    {
        $query = CampaignMetric::query();
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        // Channel performance
        $channelPerformance = Campaign::query()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->get()
            ->groupBy('source')
            ->map(function (Collection $campaigns, string $source) {
                $campaignIds = $campaigns->pluck('id');
                $metrics = CampaignMetric::whereIn('campaign_id', $campaignIds)->get();

                $totalSpend = $metrics->sum('spend');
                $totalConversions = $metrics->sum('conversions');
                $totalRevenue = $metrics->sum('revenue_attributed');
                $totalClicks = $metrics->sum('clicks');
                $totalImpressions = $metrics->sum('impressions');

                return [
                    'source' => $source,
                    'type' => $campaigns->first()->type,
                    'campaigns' => $campaigns->count(),
                    'total_spend' => round($totalSpend, 2),
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'total_conversions' => $totalConversions,
                    'total_revenue' => round($totalRevenue, 2),
                    'roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0,
                    'cpa' => $totalConversions > 0 ? round($totalSpend / $totalConversions, 2) : 0,
                    'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                ];
            })
            ->values();

        // Daily spend trend
        $spendTrend = CampaignMetric::query()
            ->when($eventId, fn ($q) => $q->where('campaign_metrics.event_id', $eventId))
            ->join('campaigns', 'campaign_metrics.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaign_metrics.date, campaigns.source, SUM(campaign_metrics.spend) as spend, SUM(campaign_metrics.conversions) as conversions, SUM(campaign_metrics.revenue_attributed) as revenue')
            ->groupBy('campaign_metrics.date', 'campaigns.source')
            ->orderBy('campaign_metrics.date')
            ->get();

        // Attribution breakdown
        $attribution = CampaignMetric::query()
            ->when($eventId, fn ($q) => $q->where('campaign_metrics.event_id', $eventId))
            ->join('campaigns', 'campaign_metrics.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.source, campaigns.type, SUM(campaign_metrics.conversions) as conversions, SUM(campaign_metrics.revenue_attributed) as revenue')
            ->groupBy('campaigns.source', 'campaigns.type')
            ->orderByDesc('revenue')
            ->get();

        return [
            'channel_performance' => $channelPerformance,
            'spend_trend' => $spendTrend,
            'attribution' => $attribution,
        ];
    }

    public function getSalesVelocity(int $eventId): array
    {
        $snapshots = SalesSnapshot::where('event_id', $eventId)
            ->orderBy('captured_at')
            ->get()
            ->groupBy('section_id');

        $velocityData = $snapshots->map(function ($sectionSnapshots) {
            return $sectionSnapshots->map(fn ($s) => [
                'date' => $s->captured_at->format('Y-m-d'),
                'sold' => $s->sold,
                'velocity' => $s->velocity_daily,
                'sellthrough' => $s->sellthrough_pct,
                'days_to_event' => $s->days_to_event,
            ]);
        });

        return ['velocity_by_section' => $velocityData];
    }
}
