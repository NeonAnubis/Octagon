<?php

namespace App\Services\Integrations;

use App\Models\Event;
use App\Models\SalesSnapshot;
use App\Models\Section;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketmasterService
{
    private string $baseUrl = 'https://app.ticketmaster.com/discovery/v2';
    private string $inventoryUrl = 'https://app.ticketmaster.com/inventory-status/v1';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.ticketmaster.api_key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Search for events by keyword (e.g., "UFC")
     */
    public function searchEvents(string $keyword = 'UFC', int $size = 50): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/events.json", [
            'apikey' => $this->apiKey,
            'keyword' => $keyword,
            'classificationName' => 'Mixed Martial Arts',
            'size' => $size,
            'sort' => 'date,asc',
        ]);

        if (!$response->successful()) {
            Log::error('Ticketmaster search failed', ['status' => $response->status()]);
            return [];
        }

        return $response->json('_embedded.events', []);
    }

    /**
     * Get detailed event info including venue and pricing
     */
    public function getEventDetails(string $eventId): ?array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/events/{$eventId}.json", [
            'apikey' => $this->apiKey,
        ]);

        if (!$response->successful()) {
            Log::error('Ticketmaster event details failed', ['event_id' => $eventId]);
            return null;
        }

        return $response->json();
    }

    /**
     * Get inventory/availability status for an event
     */
    public function getInventoryStatus(string $eventId): ?array
    {
        $response = Http::timeout(30)->get("{$this->inventoryUrl}/availability", [
            'apikey' => $this->apiKey,
            'events' => $eventId,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Sync a Ticketmaster event into the database
     */
    public function syncEvent(array $tmEvent): Event
    {
        $venue = $tmEvent['_embedded']['venues'][0] ?? [];
        $priceRanges = $tmEvent['priceRanges'] ?? [];

        $event = Event::updateOrCreate(
            ['ticketmaster_id' => $tmEvent['id']],
            [
                'name' => $tmEvent['name'],
                'slug' => Str::slug($tmEvent['name']),
                'venue' => $venue['name'] ?? 'TBD',
                'city' => $venue['city']['name'] ?? '',
                'state' => $venue['state']['stateCode'] ?? null,
                'country' => $venue['country']['countryCode'] ?? 'US',
                'market' => $venue['market']['name'] ?? $venue['city']['name'] ?? null,
                'event_date' => $tmEvent['dates']['start']['dateTime'] ?? now(),
                'on_sale_date' => $tmEvent['sales']['public']['startDateTime'] ?? null,
                'status' => $this->mapStatus($tmEvent['dates']['status']['code'] ?? ''),
                'image_url' => collect($tmEvent['images'] ?? [])->sortByDesc('width')->first()['url'] ?? null,
                'total_capacity' => $venue['generalInfo']['generalRule'] ?? 0,
            ]
        );

        // Sync price tiers as sections if price ranges are available
        if (!empty($priceRanges)) {
            $this->syncPriceRanges($event, $priceRanges);
        }

        return $event;
    }

    /**
     * Sync price ranges into sections
     */
    private function syncPriceRanges(Event $event, array $priceRanges): void
    {
        foreach ($priceRanges as $range) {
            $tier = $this->classifyPriceTier($range['min'] ?? 0);

            Section::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'name' => $range['type'] ?? $tier . ' Level',
                    'price_tier' => $tier,
                ],
                [
                    'base_price' => $range['min'] ?? 0,
                    'current_price' => $range['max'] ?? $range['min'] ?? 0,
                ]
            );
        }
    }

    /**
     * Take a sales snapshot for all active events
     */
    public function captureSnapshots(): int
    {
        $count = 0;
        $events = Event::where('status', 'on_sale')
            ->whereNotNull('ticketmaster_id')
            ->with('sections')
            ->get();

        foreach ($events as $event) {
            $inventory = $this->getInventoryStatus($event->ticketmaster_id);

            if (!$inventory) continue;

            foreach ($event->sections as $section) {
                $previousSnapshot = SalesSnapshot::where('section_id', $section->id)
                    ->latest('captured_at')
                    ->first();

                $currentSold = $section->sold;
                $previousSold = $previousSnapshot?->sold ?? 0;
                $hoursSince = $previousSnapshot
                    ? $previousSnapshot->captured_at->diffInHours(now())
                    : 1;

                SalesSnapshot::create([
                    'event_id' => $event->id,
                    'section_id' => $section->id,
                    'sold' => $currentSold,
                    'available' => $section->capacity - $currentSold - $section->held,
                    'revenue' => $section->revenue,
                    'velocity_hourly' => $hoursSince > 0
                        ? round(($currentSold - $previousSold) / $hoursSince, 2)
                        : 0,
                    'velocity_daily' => $hoursSince > 0
                        ? round(($currentSold - $previousSold) / ($hoursSince / 24), 2)
                        : 0,
                    'sellthrough_pct' => $section->capacity > 0
                        ? round(($currentSold / $section->capacity) * 100, 2)
                        : 0,
                    'days_to_event' => $event->days_to_event,
                    'captured_at' => now(),
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Full sync: search, import events, capture snapshots
     */
    public function fullSync(string $keyword = 'UFC'): array
    {
        $tmEvents = $this->searchEvents($keyword);
        $synced = 0;

        foreach ($tmEvents as $tmEvent) {
            $this->syncEvent($tmEvent);
            $synced++;
        }

        $snapshots = $this->captureSnapshots();

        return [
            'events_synced' => $synced,
            'snapshots_captured' => $snapshots,
        ];
    }

    private function mapStatus(string $code): string
    {
        return match ($code) {
            'onsale' => 'on_sale',
            'offsale' => 'upcoming',
            'cancelled', 'canceled' => 'completed',
            'postponed' => 'upcoming',
            'rescheduled' => 'on_sale',
            default => 'upcoming',
        };
    }

    private function classifyPriceTier(float $price): string
    {
        if ($price >= 500) return 'vip';
        if ($price >= 200) return 'floor';
        if ($price >= 100) return 'lower';
        return 'upper';
    }
}
