<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();

        $campaignTemplates = [
            ['source' => 'meta', 'type' => 'paid', 'prefix' => 'META'],
            ['source' => 'google', 'type' => 'paid', 'prefix' => 'GADS'],
            ['source' => 'klaviyo', 'type' => 'email', 'prefix' => 'EMAIL'],
            ['source' => 'instagram', 'type' => 'organic', 'prefix' => 'IG'],
            ['source' => 'twitter', 'type' => 'organic', 'prefix' => 'X'],
            ['source' => 'youtube', 'type' => 'organic', 'prefix' => 'YT'],
        ];

        $adNames = [
            'meta' => ['Awareness - Broad', 'Retargeting - Website Visitors', 'Lookalike - Purchasers', 'Stories - Fight Week'],
            'google' => ['Search - Brand Terms', 'Search - Event Name', 'Display - Sports Enthusiasts', 'Performance Max'],
            'klaviyo' => ['On-Sale Announcement', 'Early Bird Reminder', 'Fight Week Push', 'Last Chance'],
            'instagram' => ['Fighter Spotlight', 'Behind The Scenes', 'Weigh-In Coverage'],
            'twitter' => ['Fight Announcement', 'Press Conference', 'Countdown Posts'],
            'youtube' => ['Embedded Countdown', 'Fighter Interview', 'Promo Video'],
        ];

        foreach ($events as $event) {
            foreach ($campaignTemplates as $template) {
                $names = $adNames[$template['source']];

                foreach ($names as $adName) {
                    $budget = match ($template['type']) {
                        'paid' => rand(2000, 25000),
                        'email' => 0,
                        'organic' => 0,
                    };

                    $campaign = Campaign::create([
                        'event_id' => $event->id,
                        'external_id' => $template['prefix'] . '-' . $event->id . '-' . rand(10000, 99999),
                        'source' => $template['source'],
                        'type' => $template['type'],
                        'name' => $template['prefix'] . ' | ' . $event->name . ' | ' . $adName,
                        'status' => $event->status === 'completed' ? 'completed' : 'active',
                        'start_date' => $event->on_sale_date,
                        'end_date' => $event->event_date,
                        'budget' => $budget > 0 ? $budget : null,
                    ]);

                    // Generate daily metrics
                    $startDate = $event->on_sale_date->copy();
                    $endDate = $event->status === 'completed' ? $event->event_date->copy() : now();
                    $totalDays = (int) $startDate->diffInDays($endDate);

                    for ($d = 0; $d <= $totalDays; $d += rand(1, 3)) {
                        $date = $startDate->copy()->addDays($d);
                        $daysToEvent = max(0, (int) $date->diffInDays($event->event_date, false));

                        // Ramp up spend/engagement as event approaches
                        $urgencyMultiplier = max(0.3, 1 - ($daysToEvent / max(1, $totalDays)));
                        $dayOfWeekBoost = in_array($date->dayOfWeek, [5, 6]) ? 1.3 : 1.0;

                        if ($template['type'] === 'paid') {
                            $dailySpend = round(($budget / max(1, $totalDays)) * $urgencyMultiplier * $dayOfWeekBoost * (rand(70, 140) / 100), 2);
                            $impressions = (int) ($dailySpend * rand(80, 200));
                            $ctr = rand(8, 45) / 1000;
                            $clicks = (int) ($impressions * $ctr);
                            $conversionRate = rand(15, 60) / 1000;
                            $conversions = (int) ($clicks * $conversionRate);
                            $avgTicketValue = rand(100, 350);

                            CampaignMetric::create([
                                'campaign_id' => $campaign->id,
                                'event_id' => $event->id,
                                'date' => $date,
                                'spend' => $dailySpend,
                                'impressions' => $impressions,
                                'clicks' => $clicks,
                                'conversions' => $conversions,
                                'revenue_attributed' => round($conversions * $avgTicketValue, 2),
                                'cpm' => $impressions > 0 ? round(($dailySpend / $impressions) * 1000, 2) : 0,
                                'cpc' => $clicks > 0 ? round($dailySpend / $clicks, 2) : 0,
                                'cpa' => $conversions > 0 ? round($dailySpend / $conversions, 2) : 0,
                                'roas' => $dailySpend > 0 ? round(($conversions * $avgTicketValue) / $dailySpend, 2) : 0,
                                'ctr' => $ctr,
                                'reach' => (int) ($impressions * rand(60, 90) / 100),
                                'engagement' => (int) ($impressions * rand(10, 50) / 1000),
                            ]);
                        } elseif ($template['type'] === 'email') {
                            $sends = (int) (rand(5000, 50000) * $urgencyMultiplier);
                            $openRate = rand(180, 420) / 1000;
                            $clickRate = rand(20, 80) / 1000;
                            $opens = (int) ($sends * $openRate);
                            $uniqueClicks = (int) ($opens * $clickRate);
                            $conversions = (int) ($uniqueClicks * rand(30, 120) / 1000);

                            CampaignMetric::create([
                                'campaign_id' => $campaign->id,
                                'event_id' => $event->id,
                                'date' => $date,
                                'sends' => $sends,
                                'opens' => $opens,
                                'unique_clicks' => $uniqueClicks,
                                'open_rate' => $openRate,
                                'click_rate' => $clickRate,
                                'clicks' => $uniqueClicks,
                                'conversions' => $conversions,
                                'revenue_attributed' => round($conversions * rand(100, 300), 2),
                                'impressions' => $sends,
                                'reach' => $opens,
                            ]);
                        } else {
                            // Organic
                            $reach = (int) (rand(2000, 80000) * $urgencyMultiplier * $dayOfWeekBoost);
                            $engagementRate = rand(20, 80) / 1000;
                            $engagement = (int) ($reach * $engagementRate);

                            CampaignMetric::create([
                                'campaign_id' => $campaign->id,
                                'event_id' => $event->id,
                                'date' => $date,
                                'impressions' => (int) ($reach * rand(120, 200) / 100),
                                'reach' => $reach,
                                'engagement' => $engagement,
                                'clicks' => (int) ($engagement * rand(5, 20) / 100),
                                'conversions' => (int) ($engagement * rand(1, 5) / 100),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
