<?php

namespace App\Services\Integrations;

use App\Models\Campaign;
use App\Models\CampaignMetric;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaService
{
    /**
     * ── Instagram Graph API ─────────────────────────────────
     */
    public function getInstagramInsights(string $igUserId, string $accessToken, string $since, string $until): array
    {
        $response = Http::timeout(30)->get("https://graph.facebook.com/v21.0/{$igUserId}/insights", [
            'access_token' => $accessToken,
            'metric' => 'impressions,reach,accounts_engaged,likes,comments,shares,follows',
            'period' => 'day',
            'since' => $since,
            'until' => $until,
        ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    public function getInstagramMedia(string $igUserId, string $accessToken, int $limit = 25): array
    {
        $response = Http::timeout(30)->get("https://graph.facebook.com/v21.0/{$igUserId}/media", [
            'access_token' => $accessToken,
            'fields' => 'id,caption,media_type,timestamp,like_count,comments_count,insights.metric(impressions,reach,engagement)',
            'limit' => $limit,
        ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * ── X / Twitter API v2 ──────────────────────────────────
     */
    public function getTwitterTweetMetrics(string $bearerToken, array $tweetIds): array
    {
        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $bearerToken])
            ->get('https://api.twitter.com/2/tweets', [
                'ids' => implode(',', $tweetIds),
                'tweet.fields' => 'public_metrics,created_at',
            ]);

        return $response->successful() ? $response->json('data', []) : [];
    }

    public function getTwitterUserMetrics(string $bearerToken, string $userId): ?array
    {
        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $bearerToken])
            ->get("https://api.twitter.com/2/users/{$userId}", [
                'user.fields' => 'public_metrics,created_at',
            ]);

        return $response->successful() ? $response->json('data') : null;
    }

    /**
     * ── YouTube Data API v3 ─────────────────────────────────
     */
    public function getYouTubeChannelStats(string $apiKey, string $channelId): ?array
    {
        $response = Http::timeout(30)->get('https://www.googleapis.com/youtube/v3/channels', [
            'key' => $apiKey,
            'id' => $channelId,
            'part' => 'statistics,snippet',
        ]);

        return $response->successful() ? ($response->json('items.0') ?? null) : null;
    }

    public function getYouTubeVideoStats(string $apiKey, array $videoIds): array
    {
        $response = Http::timeout(30)->get('https://www.googleapis.com/youtube/v3/videos', [
            'key' => $apiKey,
            'id' => implode(',', $videoIds),
            'part' => 'statistics,snippet',
        ]);

        return $response->successful() ? $response->json('items', []) : [];
    }

    public function getYouTubeAnalytics(string $accessToken, string $channelId, string $since, string $until): array
    {
        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
            ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                'ids' => "channel=={$channelId}",
                'startDate' => $since,
                'endDate' => $until,
                'metrics' => 'views,likes,comments,shares,subscribersGained,estimatedMinutesWatched',
                'dimensions' => 'day',
            ]);

        return $response->successful() ? $response->json('rows', []) : [];
    }

    /**
     * ── Sync organic social metrics into database ───────────
     */
    public function syncInstagram(string $igUserId, string $accessToken, ?int $eventId = null): int
    {
        $media = $this->getInstagramMedia($igUserId, $accessToken, 50);
        $count = 0;

        foreach ($media as $post) {
            $insights = $post['insights']['data'] ?? [];
            $impressions = collect($insights)->firstWhere('name', 'impressions')['values'][0]['value'] ?? 0;
            $reach = collect($insights)->firstWhere('name', 'reach')['values'][0]['value'] ?? 0;
            $engagement = ($post['like_count'] ?? 0) + ($post['comments_count'] ?? 0);

            $campaign = Campaign::firstOrCreate(
                ['external_id' => $post['id'], 'source' => 'instagram'],
                [
                    'event_id' => $eventId,
                    'name' => 'IG | ' . substr($post['caption'] ?? 'Post', 0, 80),
                    'type' => 'organic',
                    'status' => 'completed',
                    'start_date' => date('Y-m-d', strtotime($post['timestamp'])),
                ]
            );

            CampaignMetric::updateOrCreate(
                ['campaign_id' => $campaign->id, 'date' => date('Y-m-d', strtotime($post['timestamp']))],
                [
                    'event_id' => $eventId,
                    'impressions' => $impressions,
                    'reach' => $reach,
                    'engagement' => $engagement,
                    'clicks' => 0,
                ]
            );

            $count++;
        }

        return $count;
    }

    public function syncTwitter(string $bearerToken, array $tweetIds, ?int $eventId = null): int
    {
        $tweets = $this->getTwitterTweetMetrics($bearerToken, $tweetIds);
        $count = 0;

        foreach ($tweets as $tweet) {
            $metrics = $tweet['public_metrics'] ?? [];

            $campaign = Campaign::firstOrCreate(
                ['external_id' => $tweet['id'], 'source' => 'twitter'],
                [
                    'event_id' => $eventId,
                    'name' => 'X | Tweet ' . $tweet['id'],
                    'type' => 'organic',
                    'status' => 'completed',
                    'start_date' => date('Y-m-d', strtotime($tweet['created_at'] ?? now())),
                ]
            );

            CampaignMetric::updateOrCreate(
                ['campaign_id' => $campaign->id, 'date' => $campaign->start_date],
                [
                    'event_id' => $eventId,
                    'impressions' => $metrics['impression_count'] ?? 0,
                    'reach' => $metrics['impression_count'] ?? 0,
                    'engagement' => ($metrics['like_count'] ?? 0) + ($metrics['retweet_count'] ?? 0) + ($metrics['reply_count'] ?? 0),
                    'clicks' => $metrics['url_link_clicks'] ?? 0,
                ]
            );

            $count++;
        }

        return $count;
    }
}
