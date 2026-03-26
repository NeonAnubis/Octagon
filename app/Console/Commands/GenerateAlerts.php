<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\Event;
use App\Models\SalesSnapshot;
use Illuminate\Console\Command;

class GenerateAlerts extends Command
{
    protected $signature = 'octagon:generate-alerts';
    protected $description = 'Analyze current data and generate alerts for soft sections, spend issues, and velocity drops';

    public function handle(): int
    {
        $this->info('Analyzing data for alerts...');
        $count = 0;

        $events = Event::where('status', '!=', 'completed')->with('sections')->get();

        foreach ($events as $event) {
            // Soft section detection
            foreach ($event->sections as $section) {
                $sellthrough = $section->sell_through_percentage;
                $daysToEvent = $event->days_to_event;

                // Get historical average
                $historicalAvg = SalesSnapshot::whereHas('event', fn ($q) => $q->where('status', 'completed'))
                    ->where('days_to_event', '>=', $daysToEvent - 3)
                    ->where('days_to_event', '<=', $daysToEvent + 3)
                    ->avg('sellthrough_pct') ?? 60;

                $gap = $sellthrough - $historicalAvg;

                if ($gap < -15) {
                    $this->createAlertIfNew($event->id, $section->id, null, 'soft_section', 'critical',
                        "{$section->name} critically underperforming",
                        "{$section->name} is at {$sellthrough}% vs {$historicalAvg}% historical average with {$daysToEvent} days to event.",
                        "Immediate action: Consider 15-20% price reduction or deploy targeted promo code. At current velocity, projected final sellthrough is " . min(100, round($sellthrough + ($sellthrough * $daysToEvent / 60))) . "%."
                    );
                    $count++;
                } elseif ($gap < -8) {
                    $this->createAlertIfNew($event->id, $section->id, null, 'soft_section', 'warning',
                        "{$section->name} below pace",
                        "{$section->name} is at {$sellthrough}% vs {$historicalAvg}% historical average with {$daysToEvent} days to event.",
                        "Consider a geo-targeted email blast for this section or a 10% temporary price adjustment."
                    );
                    $count++;
                }
            }

            // Spend efficiency check
            $campaigns = Campaign::where('event_id', $event->id)->where('type', 'paid')->get();
            foreach ($campaigns as $campaign) {
                $recentMetrics = CampaignMetric::where('campaign_id', $campaign->id)
                    ->where('date', '>=', now()->subDays(7))
                    ->get();

                $spend = $recentMetrics->sum('spend');
                $conversions = $recentMetrics->sum('conversions');
                $cpa = $conversions > 0 ? $spend / $conversions : 0;

                if ($cpa > 200 && $spend > 500) {
                    $this->createAlertIfNew($event->id, null, $campaign->id, 'spend_efficiency', 'warning',
                        "{$campaign->source} CPA exceeding threshold",
                        "7-day CPA for '{$campaign->name}' is \${$cpa} with {$conversions} conversions on \${$spend} spend.",
                        "Consider pausing this campaign and reallocating budget to higher-ROAS channels."
                    );
                    $count++;
                }
            }
        }

        $this->info("Generated {$count} new alerts.");
        return Command::SUCCESS;
    }

    private function createAlertIfNew(int $eventId, ?int $sectionId, ?int $campaignId, string $type, string $severity, string $title, string $message, string $recommendation): void
    {
        $existing = Alert::where('event_id', $eventId)
            ->where('section_id', $sectionId)
            ->where('campaign_id', $campaignId)
            ->where('type', $type)
            ->where('is_resolved', false)
            ->first();

        if (!$existing) {
            Alert::create([
                'event_id' => $eventId,
                'section_id' => $sectionId,
                'campaign_id' => $campaignId,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'recommendation' => $recommendation,
            ]);
        }
    }
}
