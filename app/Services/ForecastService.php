<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Forecast;
use App\Models\SalesSnapshot;

class ForecastService
{
    public function generateForecast(int $eventId): array
    {
        $event = Event::with('sections')->findOrFail($eventId);
        $daysToEvent = $event->days_to_event;

        $predictions = [];

        foreach ($event->sections as $section) {
            $snapshots = SalesSnapshot::where('section_id', $section->id)
                ->orderBy('captured_at')
                ->get();

            if ($snapshots->isEmpty()) continue;

            $currentSellthrough = $section->capacity > 0
                ? $section->sold / $section->capacity
                : 0;

            // Simple logistic growth projection
            $recentVelocity = $snapshots->last()?->velocity_daily ?? 0;
            $daysRemaining = max(1, $daysToEvent);

            // Project additional sales using decaying velocity
            $projectedAdditional = 0;
            $velocity = $recentVelocity;
            for ($d = 0; $d < $daysRemaining; $d++) {
                $decayFactor = 1 + (0.1 * ($daysRemaining - $d) / $daysRemaining);
                $projected = $velocity * $decayFactor;
                $projectedAdditional += $projected;

                $remaining = $section->capacity - $section->sold - $projectedAdditional;
                if ($remaining <= 0) {
                    $projectedAdditional = $section->capacity - $section->sold;
                    break;
                }
            }

            $predictedSold = (int) min($section->capacity, $section->sold + $projectedAdditional);
            $predictedSellthrough = $section->capacity > 0
                ? round(($predictedSold / $section->capacity) * 100, 2)
                : 0;

            $confidence = $this->calculateConfidence($snapshots->count(), $daysRemaining, $currentSellthrough);

            $predictions[] = [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'current_sold' => $section->sold,
                'predicted_sold' => $predictedSold,
                'predicted_sellthrough' => $predictedSellthrough,
                'predicted_revenue' => round($predictedSold * $section->current_price, 2),
                'confidence' => $confidence,
                'demand_score' => $this->calculateDemandScore($currentSellthrough, $recentVelocity, $daysRemaining),
            ];
        }

        $totalPredictedAttendance = collect($predictions)->sum('predicted_sold');
        $totalPredictedRevenue = collect($predictions)->sum('predicted_revenue');

        return [
            'event' => $event->only(['id', 'name', 'event_date', 'total_capacity', 'total_sold']),
            'days_to_event' => $daysToEvent,
            'predicted_attendance' => $totalPredictedAttendance,
            'predicted_revenue' => $totalPredictedRevenue,
            'predicted_sellthrough' => $event->total_capacity > 0
                ? round(($totalPredictedAttendance / $event->total_capacity) * 100, 2)
                : 0,
            'sections' => $predictions,
        ];
    }

    private function calculateConfidence(int $dataPoints, int $daysRemaining, float $currentSellthrough): float
    {
        $dataScore = min(1, $dataPoints / 20) * 30;
        $timeScore = min(1, max(0, 1 - ($daysRemaining / 60))) * 40;
        $progressScore = $currentSellthrough * 30;

        return round($dataScore + $timeScore + $progressScore, 1);
    }

    private function calculateDemandScore(float $sellthrough, float $velocity, int $daysRemaining): int
    {
        $sellthroughScore = $sellthrough * 50;
        $velocityScore = min(50, $velocity * 5);
        $urgencyBonus = $daysRemaining < 14 ? 10 : ($daysRemaining < 30 ? 5 : 0);

        return (int) min(100, $sellthroughScore + $velocityScore + $urgencyBonus);
    }
}
