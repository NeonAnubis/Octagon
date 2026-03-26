<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\Event;
use App\Models\SalesSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->model = 'gpt-4o';
    }

    public function chat(string $message, ?int $eventId = null, string $sessionId = 'default'): array
    {
        $context = $this->buildContext($eventId);

        // Store user message
        AiConversation::create([
            'event_id' => $eventId,
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $message,
        ]);

        // Get conversation history
        $history = AiConversation::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        $systemPrompt = $this->getSystemPrompt($context);

        // If no API key, return intelligent mock response
        if (empty($this->apiKey)) {
            $response = $this->generateMockResponse($message, $context);
        } else {
            $response = $this->callOpenAI($systemPrompt, $history);
        }

        // Store assistant response
        AiConversation::create([
            'event_id' => $eventId,
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $response,
            'context' => $context,
        ]);

        return [
            'message' => $response,
            'context' => [
                'event_id' => $eventId,
                'session_id' => $sessionId,
            ],
        ];
    }

    public function generateRecommendations(?int $eventId = null): array
    {
        $context = $this->buildContext($eventId);

        if (empty($this->apiKey)) {
            return $this->getMockRecommendations($context);
        }

        $prompt = "Based on the following analytics data, provide 3-5 specific, actionable recommendations. Format each as a JSON object with 'title', 'priority' (high/medium/low), 'category' (pricing/marketing/timing), and 'description' fields.\n\n" . json_encode($context);

        $response = $this->callOpenAI($this->getSystemPrompt($context), [
            ['role' => 'user', 'content' => $prompt],
        ]);

        $parsed = json_decode($response, true);
        if (!$parsed) {
            $parsed = [['title' => 'AI Analysis', 'priority' => 'medium', 'category' => 'general', 'description' => $response]];
        }

        return $parsed;
    }

    private function buildContext(?int $eventId): array
    {
        $context = ['generated_at' => now()->toISOString()];

        if ($eventId) {
            $event = Event::with(['sections', 'alerts' => fn ($q) => $q->where('is_resolved', false)])
                ->find($eventId);

            if ($event) {
                $context['event'] = [
                    'name' => $event->name,
                    'venue' => $event->venue,
                    'city' => $event->city,
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'days_to_event' => $event->days_to_event,
                    'capacity' => $event->total_capacity,
                    'sold' => $event->total_sold,
                    'sellthrough' => $event->sell_through_percentage . '%',
                    'revenue' => '$' . number_format($event->total_revenue, 2),
                    'fight_card' => $event->fight_card,
                ];

                $context['sections'] = $event->sections->map(fn ($s) => [
                    'name' => $s->name,
                    'tier' => $s->price_tier,
                    'capacity' => $s->capacity,
                    'sold' => $s->sold,
                    'sellthrough' => $s->sell_through_percentage . '%',
                    'status' => $s->status,
                    'base_price' => '$' . $s->base_price,
                    'current_price' => '$' . $s->current_price,
                    'revenue' => '$' . number_format($s->revenue, 2),
                ])->toArray();

                $context['active_alerts'] = $event->alerts->map(fn ($a) => [
                    'type' => $a->type,
                    'severity' => $a->severity,
                    'message' => $a->message,
                    'recommendation' => $a->recommendation,
                ])->toArray();

                // Marketing data for this event
                $channelMetrics = CampaignMetric::where('campaign_metrics.event_id', $eventId)
                    ->join('campaigns', 'campaign_metrics.campaign_id', '=', 'campaigns.id')
                    ->selectRaw('campaigns.source, campaigns.type, SUM(campaign_metrics.spend) as total_spend, SUM(campaign_metrics.conversions) as total_conversions, SUM(campaign_metrics.revenue_attributed) as total_revenue')
                    ->groupBy('campaigns.source', 'campaigns.type')
                    ->get();

                $context['marketing_by_channel'] = $channelMetrics->map(fn ($m) => [
                    'source' => $m->source,
                    'type' => $m->type,
                    'spend' => '$' . number_format($m->total_spend, 2),
                    'conversions' => $m->total_conversions,
                    'revenue' => '$' . number_format($m->total_revenue, 2),
                    'roas' => $m->total_spend > 0 ? round($m->total_revenue / $m->total_spend, 2) . 'x' : 'N/A',
                ])->toArray();

                // Recent velocity
                $recentVelocity = SalesSnapshot::where('event_id', $eventId)
                    ->where('captured_at', '>=', now()->subDays(7))
                    ->avg('velocity_daily');
                $context['recent_avg_daily_velocity'] = round($recentVelocity ?? 0, 1) . ' tickets/day';
            }
        } else {
            $events = Event::where('status', '!=', 'completed')->get();
            $context['overview'] = [
                'upcoming_events' => $events->count(),
                'total_revenue' => '$' . number_format(Event::sum('total_revenue'), 2),
                'total_tickets_sold' => Event::sum('total_sold'),
                'avg_sellthrough' => $events->count() > 0 ? round($events->avg(fn ($e) => $e->sell_through_percentage), 1) . '%' : '0%',
            ];

            $context['events_summary'] = $events->map(fn ($e) => [
                'name' => $e->name,
                'venue' => $e->venue . ', ' . $e->city,
                'days_to_event' => $e->days_to_event,
                'sellthrough' => $e->sell_through_percentage . '%',
                'revenue' => '$' . number_format($e->total_revenue, 2),
            ])->toArray();

            // Overall marketing summary
            $totalSpend = CampaignMetric::sum('spend');
            $totalRevenue = CampaignMetric::sum('revenue_attributed');
            $context['marketing_overview'] = [
                'total_spend' => '$' . number_format($totalSpend, 2),
                'total_attributed_revenue' => '$' . number_format($totalRevenue, 2),
                'blended_roas' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) . 'x' : 'N/A',
            ];

            $context['active_alerts_count'] = DB::table('alerts')->where('is_resolved', false)->count();
        }

        return $context;
    }

    private function getSystemPrompt(array $context): string
    {
        return "You are Octagon AI, an expert analytics assistant for a combat sports promotion company. You analyze ticket sales data, marketing campaign performance, and provide actionable intelligence.

Your role:
- Analyze ticket sales velocity, sellthrough rates, and pricing optimization
- Evaluate marketing campaign performance across Meta, Google, Email, and organic channels
- Provide specific, data-driven recommendations for pricing actions, marketing spend allocation, and campaign timing
- Flag risks early and suggest concrete mitigation steps
- Speak in clear, direct language. No fluff. Be specific with numbers.

Current data context:
" . json_encode($context, JSON_PRETTY_PRINT) . "

Always reference specific data points in your responses. If asked about something not in the context, say so clearly.";
    }

    private function callOpenAI(string $systemPrompt, array $messages): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'temperature' => 0.7,
            'max_tokens' => 1500,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content', 'Unable to generate response.');
        }

        return 'AI service temporarily unavailable. Error: ' . $response->status();
    }

    private function generateMockResponse(string $message, array $context): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'sellthrough') || str_contains($lower, 'sell through') || str_contains($lower, 'selling')) {
            $eventName = $context['event']['name'] ?? 'the upcoming events';
            $sellthrough = $context['event']['sellthrough'] ?? 'N/A';
            return "**Sellthrough Analysis for {$eventName}**\n\nCurrent sellthrough is at {$sellthrough}. Based on historical velocity patterns for similar fight cards at this venue, here's my assessment:\n\n- **VIP & Floor sections** are tracking ahead of pace — these typically sell out within 10 days of event for PPV cards\n- **Upper sections** are lagging behind the 60-day benchmark by approximately 8-12%\n- **Recommendation:** Consider deploying a targeted email campaign to previous attendees in the market area, focusing on upper bowl value pricing. A 10-15% promo code historically drives a 20% velocity spike in the 48 hours following deployment.\n\nWould you like me to drill into a specific section or compare against a historical event?";
        }

        if (str_contains($lower, 'marketing') || str_contains($lower, 'campaign') || str_contains($lower, 'ads') || str_contains($lower, 'roas')) {
            return "**Marketing Performance Summary**\n\nAcross active campaigns:\n\n- **Meta Ads** are delivering the highest volume but CPA has been climbing — up 23% over the last 7 days. Retargeting campaigns are outperforming prospecting by 3.2x on ROAS.\n- **Google Search** remains the most efficient channel with an average CPA of \$42 on brand terms. Consider increasing budget on event-name keywords.\n- **Email (Klaviyo)** is showing strong click-through rates (4.2%) on fight-week sequences. The 'Last Chance' template converts at 2.1x the average.\n- **Organic social** engagement spiked 340% after the press conference — capitalize on this momentum with boosted posts.\n\n**Action items:**\n1. Shift 15% of Meta prospecting budget to Google Search\n2. Deploy the Fight Week email sequence 48 hours earlier than planned\n3. Boost the top-performing organic post on Instagram with \$500 budget";
        }

        if (str_contains($lower, 'price') || str_contains($lower, 'pricing')) {
            return "**Pricing Recommendations**\n\nBased on current sellthrough velocity and days-to-event:\n\n1. **Hold VIP pricing** — demand is strong, no need to discount. Consider releasing 10 held seats at a 5% premium.\n2. **Lower bowl (Sections 100-102)** — on track. Maintain current pricing.\n3. **Upper bowl (Sections 300-305)** — consider a flash sale at 12% off for 48 hours to accelerate sellthrough from current ~45% to target 65% before fight week.\n4. **Dynamic pricing alert:** If Section 301 doesn't hit 50% sellthrough by 7 days out, trigger automatic 15% reduction.\n\nHistorical data shows that fight-week pricing adjustments in upper bowl recover an average of 8.5% additional sellthrough.";
        }

        return "**Octagon AI Analysis**\n\nI've analyzed the current data across your events and marketing channels. Here's what stands out:\n\n1. **Sales Velocity:** Overall ticket velocity is trending positively for upcoming PPV events, with fight-week acceleration expected based on historical patterns.\n\n2. **Marketing Efficiency:** Paid channels are generating a blended ROAS of 3.8x. Email remains the most cost-effective channel for driving conversions.\n\n3. **Risk Areas:** Watch the upper sections on the Las Vegas events — they're tracking behind the historical curve at this days-to-event window.\n\n4. **Opportunity:** The Mexico City event has strong early demand signals. Consider front-loading marketing spend to capitalize on momentum.\n\nWhat specific area would you like me to dive deeper into? I can analyze:\n- Specific event sellthrough\n- Channel attribution\n- Pricing optimization\n- Campaign timing recommendations";
    }

    private function getMockRecommendations(array $context): array
    {
        return [
            [
                'title' => 'Reallocate Meta Ads Budget to Google Search',
                'priority' => 'high',
                'category' => 'marketing',
                'description' => 'Meta Ads CPA has increased 23% over the past week while Google Search maintains a 3.2x ROAS. Recommend shifting 15% of Meta prospecting budget to Google brand and event-name search campaigns.',
            ],
            [
                'title' => 'Deploy Upper Bowl Flash Promotion',
                'priority' => 'high',
                'category' => 'pricing',
                'description' => 'Upper sections are 12% behind historical sellthrough benchmarks at current days-to-event. A 48-hour flash sale with 12% discount code via email could recover 8-10% additional sellthrough.',
            ],
            [
                'title' => 'Accelerate Fight Week Email Sequence',
                'priority' => 'medium',
                'category' => 'timing',
                'description' => 'Based on velocity patterns, deploying the fight-week email sequence 48 hours earlier than scheduled could capture an additional 3-5% conversions before day-of walk-up window.',
            ],
            [
                'title' => 'Boost Top Organic Post',
                'priority' => 'medium',
                'category' => 'marketing',
                'description' => 'Press conference content generated 340% engagement spike on Instagram. Boosting the top post with $500 spend targeting lookalike audiences could drive an estimated 40-60 additional ticket sales.',
            ],
            [
                'title' => 'Hold VIP Inventory Strategy',
                'priority' => 'low',
                'category' => 'pricing',
                'description' => 'VIP sections are tracking at strong sellthrough. Release 10 held seats at 5% premium pricing — demand data suggests they will sell within 72 hours.',
            ],
        ];
    }
}
