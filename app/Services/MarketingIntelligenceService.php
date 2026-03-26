<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\Event;
use App\Models\SalesSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketingIntelligenceService
{
    /**
     * ═══════════════════════════════════════════════════
     *  CAMPAIGN TIMING RECOMMENDATIONS
     *  Analyze when to push spend based on sales pace
     * ═══════════════════════════════════════════════════
     */
    public function getCampaignTimingRecommendations(?int $eventId = null): array
    {
        $events = $eventId
            ? Event::where('id', $eventId)->where('status', '!=', 'completed')->get()
            : Event::where('status', '!=', 'completed')->orderBy('event_date')->get();

        $recommendations = [];

        foreach ($events as $event) {
            $daysToEvent = $event->days_to_event;
            $sellthrough = $event->sell_through_percentage;

            // Get recent velocity trend (last 7 days)
            $recentVelocity = SalesSnapshot::where('event_id', $event->id)
                ->where('captured_at', '>=', now()->subDays(7))
                ->avg('velocity_daily') ?? 0;

            // Get spend trend (last 7 days vs prior 7 days)
            $recentSpend = CampaignMetric::where('campaign_metrics.event_id', $event->id)
                ->where('date', '>=', now()->subDays(7))
                ->sum('spend');
            $priorSpend = CampaignMetric::where('campaign_metrics.event_id', $event->id)
                ->whereBetween('date', [now()->subDays(14), now()->subDays(7)])
                ->sum('spend');

            $spendTrend = $priorSpend > 0
                ? round((($recentSpend - $priorSpend) / $priorSpend) * 100, 1)
                : 0;

            // Determine optimal timing phase
            $phase = $this->getMarketingPhase($daysToEvent);

            // Get historical average sellthrough at this days-to-event
            $historicalAvg = $this->getHistoricalSellthrough($daysToEvent);

            // Generate timing recommendation
            $recommendations[] = [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'days_to_event' => $daysToEvent,
                'current_sellthrough' => $sellthrough,
                'historical_avg_sellthrough' => $historicalAvg,
                'sellthrough_gap' => round($sellthrough - $historicalAvg, 1),
                'velocity_daily' => round($recentVelocity, 1),
                'recent_spend' => round($recentSpend, 2),
                'spend_trend_pct' => $spendTrend,
                'phase' => $phase,
                'recommendations' => $this->generateTimingActions($event, $daysToEvent, $sellthrough, $historicalAvg, $recentVelocity, $spendTrend),
                'optimal_channels' => $this->getOptimalChannelsByPhase($event->id, $phase),
                'spend_schedule' => $this->generateSpendSchedule($event, $daysToEvent, $sellthrough),
            ];
        }

        return $recommendations;
    }

    private function getMarketingPhase(int $daysToEvent): array
    {
        if ($daysToEvent > 45) {
            return ['name' => 'awareness', 'label' => 'Awareness Phase', 'description' => 'Build buzz and early interest. Focus on broad reach.'];
        }
        if ($daysToEvent > 21) {
            return ['name' => 'consideration', 'label' => 'Consideration Phase', 'description' => 'Drive consideration with targeted messaging. Retarget engaged audiences.'];
        }
        if ($daysToEvent > 7) {
            return ['name' => 'urgency', 'label' => 'Urgency Phase', 'description' => 'Create urgency. Push hard on remaining inventory. Deploy scarcity messaging.'];
        }
        return ['name' => 'fight_week', 'label' => 'Fight Week', 'description' => 'Maximum intensity. Last-chance messaging. Focus on walk-up and late buyers.'];
    }

    private function getHistoricalSellthrough(int $daysToEvent): float
    {
        $avg = SalesSnapshot::whereHas('event', fn ($q) => $q->where('status', 'completed'))
            ->where('days_to_event', '>=', $daysToEvent - 2)
            ->where('days_to_event', '<=', $daysToEvent + 2)
            ->avg('sellthrough_pct');

        return round($avg ?? 55, 1);
    }

    private function generateTimingActions(Event $event, int $daysToEvent, float $sellthrough, float $historicalAvg, float $velocity, float $spendTrend): array
    {
        $actions = [];
        $gap = $sellthrough - $historicalAvg;

        // Underperforming vs historical
        if ($gap < -10) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'Increase spend immediately',
                'detail' => "Sellthrough is {$gap}% behind historical average at {$daysToEvent} days out. Recommend 25-30% spend increase across top-performing channels.",
            ];
        } elseif ($gap < -5) {
            $actions[] = [
                'priority' => 'medium',
                'action' => 'Moderate spend increase',
                'detail' => "Sellthrough is slightly behind pace. Consider 10-15% spend increase, focusing on retargeting campaigns.",
            ];
        }

        // Velocity declining
        if ($velocity < 5 && $daysToEvent < 21) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'Deploy flash promotion',
                'detail' => "Daily velocity has dropped below 5 tickets/day with {$daysToEvent} days remaining. Deploy a time-limited promo code via email to spike velocity.",
            ];
        }

        // Spend trend vs sales trend
        if ($spendTrend > 20 && $gap < 0) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'Audit channel efficiency',
                'detail' => "Spend increased {$spendTrend}% but sellthrough is still behind. Reallocate from underperforming channels before increasing further.",
            ];
        }

        // Phase-specific timing
        if ($daysToEvent <= 7) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'Activate fight week sequence',
                'detail' => 'Deploy fight week email series, boost organic social posts, and increase Google Search bids on event-name keywords by 50%.',
            ];
        } elseif ($daysToEvent <= 14) {
            $actions[] = [
                'priority' => 'medium',
                'action' => 'Shift to urgency messaging',
                'detail' => 'Update ad creative to scarcity-focused copy ("Limited Seats", "Almost Sold Out"). Increase retargeting budget by 20%.',
            ];
        }

        // Outperforming
        if ($gap > 10) {
            $actions[] = [
                'priority' => 'low',
                'action' => 'Maintain current pace',
                'detail' => "Sellthrough is {$gap}% ahead of historical average. Consider holding spend steady or testing price increases on strong sections.",
            ];
        }

        if (empty($actions)) {
            $actions[] = [
                'priority' => 'low',
                'action' => 'On track — maintain strategy',
                'detail' => 'Sales pace is aligned with historical benchmarks. Continue current marketing strategy.',
            ];
        }

        return $actions;
    }

    private function getOptimalChannelsByPhase(int $eventId, array $phase): array
    {
        $metrics = CampaignMetric::where('campaign_metrics.event_id', $eventId)
            ->join('campaigns', 'campaign_metrics.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.source, campaigns.type, SUM(campaign_metrics.spend) as spend, SUM(campaign_metrics.conversions) as conversions, SUM(campaign_metrics.revenue_attributed) as revenue')
            ->groupBy('campaigns.source', 'campaigns.type')
            ->get();

        return $metrics->map(function ($m) {
            $roas = $m->spend > 0 ? round($m->revenue / $m->spend, 2) : 0;
            $cpa = $m->conversions > 0 ? round($m->spend / $m->conversions, 2) : 0;
            return [
                'source' => $m->source,
                'type' => $m->type,
                'roas' => $roas,
                'cpa' => $cpa,
                'conversions' => $m->conversions,
                'recommended_action' => $roas >= 3 ? 'increase' : ($roas >= 1.5 ? 'maintain' : 'decrease'),
            ];
        })->sortByDesc('roas')->values()->toArray();
    }

    private function generateSpendSchedule(Event $event, int $daysToEvent, float $sellthrough): array
    {
        $schedule = [];
        $totalBudgetRemaining = Campaign::where('event_id', $event->id)
            ->whereNotNull('budget')
            ->sum('budget');

        $allocations = [
            ['label' => 'Now — 21 days out', 'pct' => 25, 'focus' => 'Awareness + Retargeting'],
            ['label' => '21 — 14 days out', 'pct' => 25, 'focus' => 'Consideration + Conversion'],
            ['label' => '14 — 7 days out', 'pct' => 30, 'focus' => 'Urgency + Scarcity messaging'],
            ['label' => 'Fight week', 'pct' => 20, 'focus' => 'Last chance + Walk-up push'],
        ];

        foreach ($allocations as $alloc) {
            $schedule[] = [
                'window' => $alloc['label'],
                'budget_pct' => $alloc['pct'],
                'estimated_budget' => round($totalBudgetRemaining * ($alloc['pct'] / 100), 2),
                'focus' => $alloc['focus'],
            ];
        }

        return $schedule;
    }

    /**
     * ═══════════════════════════════════════════════════
     *  AUDIENCE TARGETING SUGGESTIONS
     *  Surface signals from high-converting campaigns
     * ═══════════════════════════════════════════════════
     */
    public function getAudienceTargetingSuggestions(?int $eventId = null): array
    {
        // Get top performing campaigns by ROAS
        $topCampaigns = Campaign::query()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->where('type', 'paid')
            ->get()
            ->map(function ($c) {
                $metrics = $c->metrics;
                $spend = $metrics->sum('spend');
                $conversions = $metrics->sum('conversions');
                $revenue = $metrics->sum('revenue_attributed');
                return [
                    'campaign' => $c,
                    'spend' => $spend,
                    'conversions' => $conversions,
                    'revenue' => $revenue,
                    'roas' => $spend > 0 ? $revenue / $spend : 0,
                    'cpa' => $conversions > 0 ? $spend / $conversions : 0,
                ];
            })
            ->filter(fn ($c) => $c['conversions'] > 0)
            ->sortByDesc('roas');

        $top5 = $topCampaigns->take(5);
        $bottom5 = $topCampaigns->sortBy('roas')->take(5);

        // Analyze what makes top performers different
        $topSources = $top5->groupBy(fn ($c) => $c['campaign']->source)
            ->map(fn ($group) => [
                'count' => $group->count(),
                'avg_roas' => round($group->avg('roas'), 2),
                'total_conversions' => $group->sum('conversions'),
            ]);

        $suggestions = [];

        // Source performance analysis
        foreach ($topSources as $source => $data) {
            $suggestions[] = [
                'type' => 'channel',
                'source' => $source,
                'insight' => "{$source} campaigns are your top converters with {$data['avg_roas']}x average ROAS across {$data['count']} campaigns.",
                'action' => "Increase {$source} budget allocation and create lookalike audiences from these converters.",
            ];
        }

        // Campaign naming pattern analysis (infer targeting from campaign names)
        $retargetingCampaigns = $topCampaigns->filter(fn ($c) => str_contains(strtolower($c['campaign']->name), 'retarget'));
        $prospectCampaigns = $topCampaigns->filter(fn ($c) => str_contains(strtolower($c['campaign']->name), 'broad') || str_contains(strtolower($c['campaign']->name), 'awareness'));

        if ($retargetingCampaigns->isNotEmpty()) {
            $avgRetargetRoas = round($retargetingCampaigns->avg('roas'), 2);
            $avgProspectRoas = $prospectCampaigns->isNotEmpty() ? round($prospectCampaigns->avg('roas'), 2) : 0;

            $suggestions[] = [
                'type' => 'targeting',
                'insight' => "Retargeting campaigns deliver {$avgRetargetRoas}x ROAS vs {$avgProspectRoas}x for prospecting.",
                'action' => 'Increase retargeting budget share. Build retargeting pools from: website visitors (last 14 days), video viewers (75%+ completion), engagement audiences.',
            ];
        }

        // Market-based suggestions
        $eventMarkets = Event::query()
            ->when($eventId, fn ($q) => $q->where('id', $eventId))
            ->pluck('market', 'city')
            ->toArray();

        foreach ($eventMarkets as $city => $market) {
            $suggestions[] = [
                'type' => 'geo',
                'insight' => "Target within 50-mile radius of {$city} for highest conversion rates.",
                'action' => "Create geo-targeted campaigns for {$market} market. Layer with interest targeting: MMA, UFC, Combat Sports, Boxing.",
            ];
        }

        // General best-practice suggestions based on data
        $suggestions[] = [
            'type' => 'audience',
            'insight' => 'Historical data suggests M25-44 demographic converts at the highest rate for combat sports events.',
            'action' => 'Prioritize M25-44 targeting on Meta and Google. Test expansion to F25-34 for co-main events featuring popular fighters.',
        ];

        $suggestions[] = [
            'type' => 'lookalike',
            'insight' => 'Create lookalike audiences from your highest-value purchasers.',
            'action' => 'Upload purchaser lists from completed events to Meta and Google. Create 1%, 3%, and 5% lookalike audiences. Test each with separate ad sets.',
        ];

        return [
            'top_campaigns' => $top5->map(fn ($c) => [
                'name' => $c['campaign']->name,
                'source' => $c['campaign']->source,
                'roas' => round($c['roas'], 2),
                'conversions' => $c['conversions'],
                'cpa' => round($c['cpa'], 2),
            ])->values()->toArray(),
            'underperformers' => $bottom5->map(fn ($c) => [
                'name' => $c['campaign']->name,
                'source' => $c['campaign']->source,
                'roas' => round($c['roas'], 2),
                'conversions' => $c['conversions'],
                'cpa' => round($c['cpa'], 2),
            ])->values()->toArray(),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * ═══════════════════════════════════════════════════
     *  SPEND OPTIMIZATION ANALYSIS
     *  Flag when spend isn't tracking against velocity
     * ═══════════════════════════════════════════════════
     */
    public function getSpendOptimization(?int $eventId = null): array
    {
        $events = $eventId
            ? Event::where('id', $eventId)->get()
            : Event::where('status', '!=', 'completed')->get();

        $analysis = [];

        foreach ($events as $event) {
            $channels = Campaign::where('event_id', $event->id)
                ->get()
                ->groupBy('source');

            $channelAnalysis = [];

            foreach ($channels as $source => $campaigns) {
                $campaignIds = $campaigns->pluck('id');

                // Last 7 days
                $recent = CampaignMetric::whereIn('campaign_id', $campaignIds)
                    ->where('date', '>=', now()->subDays(7))
                    ->get();

                // Prior 7 days
                $prior = CampaignMetric::whereIn('campaign_id', $campaignIds)
                    ->whereBetween('date', [now()->subDays(14), now()->subDays(7)])
                    ->get();

                $recentSpend = $recent->sum('spend');
                $priorSpend = $prior->sum('spend');
                $recentConversions = $recent->sum('conversions');
                $priorConversions = $prior->sum('conversions');
                $recentRevenue = $recent->sum('revenue_attributed');

                $spendChange = $priorSpend > 0
                    ? round((($recentSpend - $priorSpend) / $priorSpend) * 100, 1)
                    : 0;
                $conversionChange = $priorConversions > 0
                    ? round((($recentConversions - $priorConversions) / $priorConversions) * 100, 1)
                    : 0;

                $recentCpa = $recentConversions > 0 ? round($recentSpend / $recentConversions, 2) : 0;
                $priorCpa = $priorConversions > 0 ? round($priorSpend / $priorConversions, 2) : 0;
                $cpaChange = $priorCpa > 0 ? round((($recentCpa - $priorCpa) / $priorCpa) * 100, 1) : 0;

                $roas = $recentSpend > 0 ? round($recentRevenue / $recentSpend, 2) : 0;

                // Determine health status
                $status = 'healthy';
                $alerts = [];

                if ($spendChange > 20 && $conversionChange < 5) {
                    $status = 'warning';
                    $alerts[] = "Spend up {$spendChange}% but conversions flat. Diminishing returns detected.";
                }
                if ($cpaChange > 30) {
                    $status = 'warning';
                    $alerts[] = "CPA increased {$cpaChange}% week over week.";
                }
                if ($roas < 1.5 && $recentSpend > 100) {
                    $status = 'critical';
                    $alerts[] = "ROAS below 1.5x ({$roas}x). Spend is not generating sufficient return.";
                }
                if ($recentConversions === 0 && $recentSpend > 50) {
                    $status = 'critical';
                    $alerts[] = 'Zero conversions despite active spend. Pause and investigate.';
                }

                $channelAnalysis[] = [
                    'source' => $source,
                    'type' => $campaigns->first()->type,
                    'recent_spend' => round($recentSpend, 2),
                    'prior_spend' => round($priorSpend, 2),
                    'spend_change_pct' => $spendChange,
                    'recent_conversions' => $recentConversions,
                    'prior_conversions' => $priorConversions,
                    'conversion_change_pct' => $conversionChange,
                    'recent_cpa' => $recentCpa,
                    'cpa_change_pct' => $cpaChange,
                    'roas' => $roas,
                    'status' => $status,
                    'alerts' => $alerts,
                    'recommendation' => $this->getSpendRecommendation($status, $source, $roas, $spendChange, $conversionChange),
                ];
            }

            // Sort by status severity
            usort($channelAnalysis, function ($a, $b) {
                $order = ['critical' => 0, 'warning' => 1, 'healthy' => 2];
                return ($order[$a['status']] ?? 3) <=> ($order[$b['status']] ?? 3);
            });

            $totalRecentSpend = collect($channelAnalysis)->sum('recent_spend');

            // Calculate optimal allocation based on ROAS
            $withRoas = collect($channelAnalysis)->filter(fn ($c) => $c['roas'] > 0);
            $totalRoas = $withRoas->sum('roas');

            $optimalAllocation = $withRoas->map(function ($c) use ($totalRoas, $totalRecentSpend) {
                $optimalShare = $totalRoas > 0 ? round(($c['roas'] / $totalRoas) * 100, 1) : 0;
                $currentShare = $totalRecentSpend > 0 ? round(($c['recent_spend'] / $totalRecentSpend) * 100, 1) : 0;
                return [
                    'source' => $c['source'],
                    'current_share' => $currentShare,
                    'optimal_share' => $optimalShare,
                    'shift' => round($optimalShare - $currentShare, 1),
                    'recommended_budget' => round($totalRecentSpend * ($optimalShare / 100), 2),
                ];
            })->values()->toArray();

            $analysis[] = [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'days_to_event' => $event->days_to_event,
                'total_recent_spend' => round($totalRecentSpend, 2),
                'channels' => $channelAnalysis,
                'optimal_allocation' => $optimalAllocation,
            ];
        }

        return $analysis;
    }

    private function getSpendRecommendation(string $status, string $source, float $roas, float $spendChange, float $convChange): string
    {
        if ($status === 'critical') {
            return "Pause underperforming {$source} campaigns immediately. Audit targeting and creative before restarting. Reallocate budget to higher-ROAS channels.";
        }

        if ($status === 'warning') {
            if ($spendChange > 20 && $convChange < 5) {
                return "Roll back {$source} spend to prior week levels. The incremental spend is not driving incremental conversions.";
            }
            return "Optimize {$source} campaigns: refresh creative, tighten targeting, or reduce bids by 15-20%.";
        }

        if ($roas > 5) {
            return "Strong performer. Consider testing 15-20% spend increase to capture additional volume at this ROAS.";
        }

        return "Maintaining healthy efficiency. Continue current strategy and monitor weekly.";
    }

    /**
     * ═══════════════════════════════════════════════════
     *  HISTORICAL COMPARISON
     *  Compare current event against completed events
     * ═══════════════════════════════════════════════════
     */
    public function getHistoricalComparison(int $eventId): array
    {
        $event = Event::findOrFail($eventId);

        // Get completed events at the same venue or market for comparison
        $comps = Event::where('status', 'completed')
            ->where('id', '!=', $eventId)
            ->orderByDesc('event_date')
            ->limit(3)
            ->get();

        $currentCurve = SalesSnapshot::where('event_id', $eventId)
            ->selectRaw('days_to_event, AVG(sellthrough_pct) as avg_sellthrough')
            ->groupBy('days_to_event')
            ->orderByDesc('days_to_event')
            ->get()
            ->map(fn ($s) => [
                'days_to_event' => $s->days_to_event,
                'sellthrough' => round($s->avg_sellthrough, 1),
            ]);

        $compCurves = [];
        foreach ($comps as $comp) {
            $compCurves[] = [
                'event_id' => $comp->id,
                'event_name' => $comp->name,
                'final_sellthrough' => $comp->sell_through_percentage,
                'curve' => SalesSnapshot::where('event_id', $comp->id)
                    ->selectRaw('days_to_event, AVG(sellthrough_pct) as avg_sellthrough')
                    ->groupBy('days_to_event')
                    ->orderByDesc('days_to_event')
                    ->get()
                    ->map(fn ($s) => [
                        'days_to_event' => $s->days_to_event,
                        'sellthrough' => round($s->avg_sellthrough, 1),
                    ]),
            ];
        }

        return [
            'current_event' => [
                'id' => $event->id,
                'name' => $event->name,
                'sellthrough' => $event->sell_through_percentage,
                'days_to_event' => $event->days_to_event,
                'curve' => $currentCurve,
            ],
            'comparisons' => $compCurves,
        ];
    }
}
