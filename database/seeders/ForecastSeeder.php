<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Forecast;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ForecastSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::where('status', '!=', 'completed')->get();

        foreach ($events as $event) {
            // Event-level forecast
            $sellthrough = $event->total_capacity > 0
                ? ($event->total_sold / $event->total_capacity)
                : 0;
            $predictedSellthrough = min(0.99, $sellthrough + rand(5, 25) / 100);
            $predictedAttendance = (int) ($event->total_capacity * $predictedSellthrough);
            $avgTicketPrice = $event->total_sold > 0
                ? ($event->total_revenue / $event->total_sold)
                : 150;

            Forecast::create([
                'event_id' => $event->id,
                'predicted_attendance' => $predictedAttendance,
                'predicted_revenue' => round($predictedAttendance * $avgTicketPrice, 2),
                'confidence' => rand(70, 95) / 100 * 100,
                'predicted_sellthrough' => round($predictedSellthrough * 100, 2),
                'demand_score' => rand(55, 98),
                'forecast_date' => now(),
                'model_params' => [
                    'model' => 'logistic_regression',
                    'features' => ['days_to_event', 'current_sellthrough', 'velocity_trend', 'fight_card_strength', 'market_size'],
                    'r_squared' => rand(80, 96) / 100,
                ],
            ]);

            // Section-level forecasts
            foreach ($event->sections as $section) {
                $secSellthrough = $section->capacity > 0
                    ? ($section->sold / $section->capacity)
                    : 0;
                $secPredicted = min(0.99, $secSellthrough + rand(3, 20) / 100);

                Forecast::create([
                    'event_id' => $event->id,
                    'section_id' => $section->id,
                    'predicted_attendance' => (int) ($section->capacity * $secPredicted),
                    'predicted_revenue' => round($section->capacity * $secPredicted * $section->current_price, 2),
                    'confidence' => rand(65, 92) / 100 * 100,
                    'predicted_sellthrough' => round($secPredicted * 100, 2),
                    'demand_score' => rand(40, 95),
                    'forecast_date' => now(),
                ]);
            }
        }
    }
}
