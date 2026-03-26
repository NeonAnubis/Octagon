<?php

namespace App\Console\Commands;

use App\Services\Integrations\TicketmasterService;
use Illuminate\Console\Command;

class SyncTicketmaster extends Command
{
    protected $signature = 'octagon:sync-ticketmaster {--keyword=UFC : Search keyword}';
    protected $description = 'Sync events and sales data from Ticketmaster API';

    public function handle(TicketmasterService $service): int
    {
        if (!$service->isConfigured()) {
            $this->warn('Ticketmaster API key not configured. Set TICKETMASTER_API_KEY in .env');
            return Command::FAILURE;
        }

        $this->info('Syncing Ticketmaster data...');
        $result = $service->fullSync($this->option('keyword'));

        $this->info("Events synced: {$result['events_synced']}");
        $this->info("Snapshots captured: {$result['snapshots_captured']}");

        return Command::SUCCESS;
    }
}
