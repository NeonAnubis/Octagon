<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Section;
use App\Models\SalesSnapshot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'name' => 'UFC 310: Pantoja vs. Asakura',
                'venue' => 'T-Mobile Arena',
                'city' => 'Las Vegas', 'state' => 'NV', 'market' => 'Las Vegas',
                'event_date' => Carbon::now()->addDays(14),
                'on_sale_date' => Carbon::now()->subDays(45),
                'total_capacity' => 18500,
                'fight_card_tier' => 'ppv',
                'fight_card' => [
                    ['main_event' => true, 'fighter_1' => 'Alexandre Pantoja', 'fighter_2' => 'Kai Asakura', 'weight_class' => 'Flyweight', 'title_fight' => true],
                    ['main_event' => false, 'fighter_1' => 'Shavkat Rakhmonov', 'fighter_2' => 'Ian Machado Garry', 'weight_class' => 'Welterweight', 'title_fight' => false],
                    ['main_event' => false, 'fighter_1' => 'Ciryl Gane', 'fighter_2' => 'Alexander Volkov', 'weight_class' => 'Heavyweight', 'title_fight' => false],
                ],
                'status' => 'on_sale',
            ],
            [
                'name' => 'UFC Fight Night: Holloway vs. Allen',
                'venue' => 'UFC APEX',
                'city' => 'Las Vegas', 'state' => 'NV', 'market' => 'Las Vegas',
                'event_date' => Carbon::now()->addDays(7),
                'on_sale_date' => Carbon::now()->subDays(30),
                'total_capacity' => 1200,
                'fight_card_tier' => 'fight_night',
                'fight_card' => [
                    ['main_event' => true, 'fighter_1' => 'Max Holloway', 'fighter_2' => 'Arnold Allen', 'weight_class' => 'Featherweight', 'title_fight' => false],
                    ['main_event' => false, 'fighter_1' => 'Cory Sandhagen', 'fighter_2' => 'Rob Font', 'weight_class' => 'Bantamweight', 'title_fight' => false],
                ],
                'status' => 'on_sale',
            ],
            [
                'name' => 'UFC 311: Makhachev vs. Tsarukyan 2',
                'venue' => 'Intuit Dome',
                'city' => 'Inglewood', 'state' => 'CA', 'market' => 'Los Angeles',
                'event_date' => Carbon::now()->addDays(35),
                'on_sale_date' => Carbon::now()->subDays(20),
                'total_capacity' => 18000,
                'fight_card_tier' => 'ppv',
                'fight_card' => [
                    ['main_event' => true, 'fighter_1' => 'Islam Makhachev', 'fighter_2' => 'Arman Tsarukyan', 'weight_class' => 'Lightweight', 'title_fight' => true],
                    ['main_event' => false, 'fighter_1' => 'Merab Dvalishvili', 'fighter_2' => 'Umar Nurmagomedov', 'weight_class' => 'Bantamweight', 'title_fight' => true],
                ],
                'status' => 'on_sale',
            ],
            [
                'name' => 'UFC Fight Night: Moreno vs. Albazi',
                'venue' => 'Arena CDMX',
                'city' => 'Mexico City', 'state' => null, 'market' => 'Mexico City',
                'country' => 'MX',
                'event_date' => Carbon::now()->addDays(50),
                'on_sale_date' => Carbon::now()->subDays(10),
                'total_capacity' => 21000,
                'fight_card_tier' => 'fight_night',
                'fight_card' => [
                    ['main_event' => true, 'fighter_1' => 'Brandon Moreno', 'fighter_2' => 'Amir Albazi', 'weight_class' => 'Flyweight', 'title_fight' => false],
                ],
                'status' => 'on_sale',
            ],
            [
                'name' => 'UFC 308: Topuria vs. Holloway',
                'venue' => 'Etihad Arena',
                'city' => 'Abu Dhabi', 'state' => null, 'market' => 'Abu Dhabi',
                'country' => 'AE',
                'event_date' => Carbon::now()->subDays(14),
                'on_sale_date' => Carbon::now()->subDays(90),
                'total_capacity' => 12000,
                'fight_card_tier' => 'ppv',
                'fight_card' => [
                    ['main_event' => true, 'fighter_1' => 'Ilia Topuria', 'fighter_2' => 'Max Holloway', 'weight_class' => 'Featherweight', 'title_fight' => true],
                ],
                'status' => 'completed',
            ],
            [
                'name' => 'UFC 309: Jones vs. Miocic',
                'venue' => 'Madison Square Garden',
                'city' => 'New York', 'state' => 'NY', 'market' => 'New York',
                'event_date' => Carbon::now()->subDays(7),
                'on_sale_date' => Carbon::now()->subDays(60),
                'total_capacity' => 20000,
                'fight_card_tier' => 'ppv',
                'fight_card' => [
                    ['main_event' => true, 'fighter_1' => 'Jon Jones', 'fighter_2' => 'Stipe Miocic', 'weight_class' => 'Heavyweight', 'title_fight' => true],
                    ['main_event' => false, 'fighter_1' => 'Charles Oliveira', 'fighter_2' => 'Michael Chandler', 'weight_class' => 'Lightweight', 'title_fight' => false],
                ],
                'status' => 'completed',
            ],
        ];

        $priceTiers = [
            'vip'   => ['min' => 500, 'max' => 2500, 'pct' => 0.05],
            'floor' => ['min' => 250, 'max' => 800, 'pct' => 0.15],
            'lower' => ['min' => 120, 'max' => 400, 'pct' => 0.35],
            'upper' => ['min' => 50, 'max' => 200, 'pct' => 0.45],
        ];

        foreach ($events as $eventData) {
            $capacity = $eventData['total_capacity'];
            $isCompleted = $eventData['status'] === 'completed';

            $event = Event::create(array_merge($eventData, [
                'slug' => \Str::slug($eventData['name']),
                'total_sold' => 0,
                'total_revenue' => 0,
            ]));

            $totalSold = 0;
            $totalRevenue = 0;

            foreach ($priceTiers as $tier => $config) {
                $sectionCapacity = (int) ($capacity * $config['pct']);
                $basePrice = rand($config['min'], $config['max']);

                $sectionCount = match ($tier) {
                    'vip' => 1,
                    'floor' => 2,
                    'lower' => rand(3, 5),
                    'upper' => rand(4, 6),
                };

                for ($i = 1; $i <= $sectionCount; $i++) {
                    $secCapacity = (int) ($sectionCapacity / $sectionCount);
                    $sellThrough = $isCompleted
                        ? rand(75, 99) / 100
                        : rand(20, 85) / 100;
                    $sold = (int) ($secCapacity * $sellThrough);
                    $priceVariance = $basePrice * (rand(-10, 15) / 100);
                    $currentPrice = round($basePrice + $priceVariance, 2);
                    $revenue = round($sold * (($basePrice + $currentPrice) / 2), 2);

                    $status = 'available';
                    if ($sellThrough >= 0.95) $status = 'sold_out';
                    elseif ($sellThrough < 0.35) $status = 'critical';
                    elseif ($sellThrough < 0.50) $status = 'warning';
                    elseif ($sellThrough < 0.60) $status = 'soft';

                    $section = Section::create([
                        'event_id' => $event->id,
                        'name' => strtoupper($tier) . ' ' . ($tier === 'vip' ? 'Suite' : 'Section ' . (100 * array_search($tier, array_keys($priceTiers)) + $i)),
                        'price_tier' => $tier,
                        'base_price' => $basePrice,
                        'current_price' => $currentPrice,
                        'capacity' => $secCapacity,
                        'sold' => $sold,
                        'held' => rand(0, (int) ($secCapacity * 0.05)),
                        'revenue' => $revenue,
                        'status' => $status,
                    ]);

                    $totalSold += $sold;
                    $totalRevenue += $revenue;

                    // Generate time-series snapshots
                    $onSaleDate = $event->on_sale_date;
                    $daysOnSale = (int) $onSaleDate->diffInDays(
                        $isCompleted ? $event->event_date : now()
                    );
                    $snapshotInterval = max(1, (int) ($daysOnSale / 20));

                    $runningSold = 0;
                    for ($d = 0; $d <= $daysOnSale; $d += $snapshotInterval) {
                        $progress = $d / max(1, $daysOnSale);
                        // S-curve: slow start, acceleration, plateau
                        $sCurve = 1 / (1 + exp(-10 * ($progress - 0.4)));
                        $snapshotSold = (int) ($sold * $sCurve);
                        $dailyVelocity = $d > 0 ? max(0, ($snapshotSold - $runningSold) / $snapshotInterval) : 0;

                        SalesSnapshot::create([
                            'event_id' => $event->id,
                            'section_id' => $section->id,
                            'sold' => $snapshotSold,
                            'available' => $secCapacity - $snapshotSold,
                            'revenue' => round($snapshotSold * $currentPrice, 2),
                            'velocity_hourly' => round($dailyVelocity / 24, 2),
                            'velocity_daily' => round($dailyVelocity, 2),
                            'sellthrough_pct' => round(($snapshotSold / $secCapacity) * 100, 2),
                            'days_to_event' => max(0, (int) $onSaleDate->copy()->addDays($d)->diffInDays($event->event_date, false)),
                            'captured_at' => $onSaleDate->copy()->addDays($d),
                        ]);

                        $runningSold = $snapshotSold;
                    }
                }
            }

            $event->update([
                'total_sold' => $totalSold,
                'total_revenue' => $totalRevenue,
            ]);
        }
    }
}
