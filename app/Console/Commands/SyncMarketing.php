<?php

namespace App\Console\Commands;

use App\Services\Integrations\GoogleAdsService;
use App\Services\Integrations\KlaviyoService;
use App\Services\Integrations\MetaAdsService;
use App\Services\Integrations\SocialMediaService;
use Illuminate\Console\Command;

class SyncMarketing extends Command
{
    protected $signature = 'octagon:sync-marketing {--source= : Specific source to sync (meta, google, klaviyo, social)}';
    protected $description = 'Sync marketing campaign data from all connected platforms';

    public function handle(): int
    {
        $source = $this->option('source');
        $results = [];

        if (!$source || $source === 'meta') {
            $meta = app(MetaAdsService::class);
            if ($meta->isConfigured()) {
                $this->info('Syncing Meta Ads...');
                // Account ID would come from config or user input
                $results['meta'] = 'configured — run with account ID';
            } else {
                $this->warn('Meta Ads: not configured');
            }
        }

        if (!$source || $source === 'google') {
            $google = app(GoogleAdsService::class);
            if ($google->isConfigured()) {
                $this->info('Syncing Google Ads...');
                $results['google'] = 'configured — run with customer ID';
            } else {
                $this->warn('Google Ads: not configured');
            }
        }

        if (!$source || $source === 'klaviyo') {
            $klaviyo = app(KlaviyoService::class);
            if ($klaviyo->isConfigured()) {
                $this->info('Syncing Klaviyo...');
                $count = $klaviyo->syncCampaigns();
                $results['klaviyo'] = "{$count} campaigns synced";
            } else {
                $this->warn('Klaviyo: not configured');
            }
        }

        if (!$source || $source === 'social') {
            $this->info('Social media sync requires platform-specific credentials.');
            $results['social'] = 'requires manual credential configuration';
        }

        $this->table(['Source', 'Result'], collect($results)->map(fn ($v, $k) => [$k, $v])->toArray());

        return Command::SUCCESS;
    }
}
