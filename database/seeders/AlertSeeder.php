<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Event;
use App\Models\Section;
use Illuminate\Database\Seeder;

class AlertSeeder extends Seeder
{
    public function run(): void
    {
        $upcomingEvents = Event::where('status', '!=', 'completed')->get();

        foreach ($upcomingEvents as $event) {
            $softSections = $event->sections()->whereIn('status', ['soft', 'warning', 'critical'])->get();

            foreach ($softSections as $section) {
                $severity = match ($section->status) {
                    'critical' => 'critical',
                    'warning' => 'warning',
                    default => 'info',
                };

                Alert::create([
                    'event_id' => $event->id,
                    'section_id' => $section->id,
                    'type' => 'soft_section',
                    'severity' => $severity,
                    'title' => "{$section->name} underperforming",
                    'message' => "{$section->name} is at {$section->sell_through_percentage}% sellthrough with {$event->days_to_event} days to event. Historical average at this point is " . rand(55, 75) . "%.",
                    'recommendation' => $this->getRecommendation($section, $severity),
                ]);
            }

            // Marketing efficiency alerts
            if (rand(0, 1)) {
                Alert::create([
                    'event_id' => $event->id,
                    'type' => 'spend_efficiency',
                    'severity' => 'warning',
                    'title' => 'Meta Ads CPA exceeding threshold',
                    'message' => "Meta Ads CPA for {$event->name} has increased 40% over the last 3 days while conversion volume remained flat.",
                    'recommendation' => 'Consider pausing underperforming ad sets and reallocating budget to Google Search campaigns which are showing 2.3x better ROAS this week.',
                ]);
            }

            if (rand(0, 1)) {
                Alert::create([
                    'event_id' => $event->id,
                    'type' => 'velocity_drop',
                    'severity' => 'info',
                    'title' => 'Sales velocity decline detected',
                    'message' => "Daily ticket sales velocity for {$event->name} dropped 25% compared to the 7-day moving average.",
                    'recommendation' => 'This is common at this stage. Consider deploying a flash promo code via email to re-accelerate. Historical data shows a 15-20% velocity bump from similar promotions.',
                ]);
            }
        }
    }

    private function getRecommendation(Section $section, string $severity): string
    {
        return match ($severity) {
            'critical' => "Immediate action recommended: Consider a 15-20% price reduction for {$section->name} or release a targeted promo code. At current velocity, this section will finish at approximately " . min(100, $section->sell_through_percentage + rand(5, 15)) . "% sellthrough.",
            'warning' => "Consider activating a geo-targeted email campaign for {$section->name}. Dynamic pricing adjustment of 10% could accelerate sales based on similar past events.",
            default => "Monitor {$section->name} closely. If velocity doesn't improve in 48 hours, consider deploying a social media push with section-specific pricing callout.",
        };
    }
}
