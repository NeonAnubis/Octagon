# Octagon

AI-powered analytics platform for combat sports ticket sales and marketing intelligence.

## Why This Platform Exists

Combat sports promotions sell thousands of tickets across dozens of sections and price tiers for every event. Marketing spend is split across Meta, Google, email, and organic social. The problem is that these two worlds (ticket sales and marketing performance) live in completely separate systems. By the time someone pulls a report, compares it to ad spend, and decides what to do, the window to act has already closed.

Octagon was built to eliminate that gap. It connects Ticketmaster sales data with every marketing channel in one place, runs the numbers in real time, and uses AI to tell you what to do next in plain English. No pivot tables, no switching between dashboards. One platform, one answer.

## Development Challenges and Solutions

### Connecting to a remote PostgreSQL database with high-latency writes

The database is hosted on Aiven Cloud, which means every insert goes over the network. During initial data seeding, inserting thousands of campaign metric rows one at a time took over 6 minutes. This was solved by accepting the tradeoff for seeders (bulk insert is not critical path) and ensuring the production sync commands use `updateOrCreate` with proper unique constraints so incremental syncs stay fast.

### Ambiguous column references in joined queries

Several analytics queries join `campaign_metrics` with `campaigns`, and both tables have an `event_id` column. PostgreSQL throws an "ambiguous column" error when you filter on `event_id` without specifying the table. This surfaced across three different service classes. Every joined query was audited and all ambiguous references were prefixed with the full table name (`campaign_metrics.event_id`).

### Fitting a full stack into Render's 512 MB free tier

The production image runs PHP-FPM, Nginx, and the Laravel Scheduler inside a single container. On a 512 MB memory limit, the default PHP-FPM config (which spawns 5+ workers at ~40 MB each) would exhaust memory on startup. The fix was switching PHP-FPM to `pm = static` with only 3 workers, reducing OPcache memory from 128 MB to 64 MB, and capping PHP's per-process memory at 128 MB. The multi-stage Docker build also keeps the final image small by discarding Node.js and Composer after their build steps.

### Building predictive models without Python

The client's spec called for predictive sellthrough modeling, which typically relies on Python libraries like scikit-learn or Prophet. Since the stack is Laravel/PHP, the forecasting engine was built natively using a logistic growth projection model with velocity decay. It takes recent daily ticket velocity, projects forward with a decay factor, and caps at section capacity. Confidence scores are calculated from three weighted inputs: data point density (30%), time proximity to event (40%), and current progress toward sellout (30%). For more sophisticated forecasting, the OpenAI API serves as the ML layer, receiving structured sales data as context and returning predictions.

### Making the AI assistant actually useful

The initial AI integration returned generic responses because it had no awareness of the actual data. The solution was building a rich context pipeline that pulls the current event's sections, sellthrough percentages, pricing, marketing channel ROAS, recent velocity, and active alerts, then injects all of it into the system prompt. The AI now references specific section numbers, exact percentages, and dollar amounts in its answers. When no OpenAI key is configured, the service falls back to intelligent mock responses so the platform remains functional.

## Features

### Ticket Sales Analytics

- **Sales velocity dashboard.** Sellthrough pace by section, price tier, and date. Includes historical comparison curves from completed events at the same venue or market.
- **Predictive sellthrough modeling.** Forecasts final attendance and revenue by section using logistic growth projections. Outputs confidence scores and demand scores per section.
- **Soft-section alerts.** Automatically flags underperforming sections by comparing current sellthrough against historical averages at the same days-to-event window. Severity levels: info, warning, critical. Each alert includes a specific AI-generated recommendation.
- **Demand scoring.** Assigns a 0-100 demand score to each section based on sellthrough rate, velocity trend, and days-to-event urgency.
- **Section heatmap.** Visual grid of all sections color-coded by health status (on track, soft, warning, critical, sold out), grouped by price tier.

### Marketing Intelligence

- **Cross-channel attribution.** Aggregates spend, impressions, clicks, conversions, and revenue across Meta Ads, Google Ads, Klaviyo email, Instagram, X/Twitter, and YouTube. Computes ROAS, CPA, and CTR per channel.
- **Campaign timing recommendations.** Analyzes each event's marketing phase (awareness, consideration, urgency, fight week) based on days-to-event. Compares current sellthrough against historical benchmarks and recommends specific spend actions with priority levels.
- **Audience targeting suggestions.** Compares your top-converting campaigns against underperformers to surface what's different. Outputs channel, geo, demographic, retargeting, and lookalike audience recommendations.
- **Spend optimization.** Tracks week-over-week spend vs. conversion trends per channel. Flags diminishing returns, rising CPAs, and zero-conversion spend. Recommends ROAS-weighted budget reallocation with exact percentage shifts.

### AI Assistant

- Natural language chat powered by OpenAI GPT-4o.
- Context-aware: every response is grounded in the platform's actual data (sections, prices, velocity, marketing metrics, active alerts).
- Maintains conversation history for follow-up questions.
- Generates standalone recommendations ranked by priority and category (pricing, marketing, timing).
- Quick-prompt buttons for the most common questions.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel (PHP 8.4) |
| Frontend | React 19, TailwindCSS 4, Alpine.js |
| Database | PostgreSQL (Aiven Cloud) |
| Charts | Recharts |
| AI | OpenAI GPT-4o |
| Build | Vite |
| Scheduling | Laravel Scheduler |
| Deployment | Docker, Render |

## Data Integrations

| Source | Service | What It Pulls |
|--------|---------|---------------|
| Ticketmaster | `TicketmasterService` | Events, venues, sections, pricing, inventory status |
| Meta / Instagram Ads | `MetaAdsService` | Campaigns, ad sets, spend, impressions, clicks, conversions, audience breakdowns |
| Google Ads | `GoogleAdsService` | Campaigns via GAQL, daily metrics, search term reports, demographic insights |
| Klaviyo | `KlaviyoService` | Email campaigns, sends, opens, clicks, revenue attribution, subscriber lists |
| Instagram (organic) | `SocialMediaService` | Post reach, impressions, engagement, likes, comments |
| X / Twitter | `SocialMediaService` | Tweet impressions, engagement, retweets, replies |
| YouTube | `SocialMediaService` | Video views, watch time, subscribers, engagement |

All integrations gracefully handle missing API credentials. When a key is not configured, the platform continues operating with the remaining data sources.

## Setup

### Requirements

- PHP 8.2+
- Node.js 18+
- PostgreSQL
- Composer

### Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

Edit `.env` with your database credentials and API keys. The only required keys are the `DB_*` variables and `OPENAI_API_KEY`. All other integration keys (Ticketmaster, Meta, Google, Klaviyo) are optional and can be added as they become available.

### Running

```bash
php artisan serve     # Backend at localhost:8000
npm run dev           # Vite dev server with hot reload
```

### Automated Data Sync

Add to crontab for continuous data ingestion:

```
* * * * * cd /path/to/octagon && php artisan schedule:run >> /dev/null 2>&1
```

This triggers:

- `octagon:sync-ticketmaster` every 15 minutes
- `octagon:sync-marketing` every hour
- `octagon:generate-alerts` every 30 minutes

### Manual Commands

```bash
php artisan octagon:sync-ticketmaster --keyword=UFC
php artisan octagon:sync-marketing --source=meta
php artisan octagon:generate-alerts
```

## Project Structure

```
app/
  Console/Commands/              Data sync and alert generation
  Http/Controllers/Api/          7 REST API controllers, 16 endpoints
  Models/                        9 Eloquent models
  Services/
    AnalyticsService             Core sales and marketing analytics
    ForecastService              Predictive sellthrough modeling
    AiService                    OpenAI chat and recommendations
    MarketingIntelligenceService Timing, audience, spend optimization
    Integrations/                Ticketmaster, Meta, Google, Klaviyo, Social

resources/js/
  pages/                         10 React dashboard pages
  components/                    Sidebar, StatCard, SectionHeatmap, LoadingSpinner

resources/views/
  landing.blade.php              Public landing page (Alpine.js)
  dashboard.blade.php            React app shell
```

## API Endpoints

All endpoints prefixed with `/api/v1/`.

**Sales:** `/dashboard`, `/events`, `/events/{id}`, `/events/{id}/velocity`, `/events/{id}/forecast`, `/events/{id}/historical`

**Marketing:** `/marketing`, `/marketing/campaigns`, `/marketing/timing`, `/marketing/audience`, `/marketing/spend-optimization`

**Intelligence:** `/alerts`, `/alerts/{id}/resolve` (POST), `/ai/chat` (POST), `/ai/recommendations`

**System:** `/integrations`

## Results

The platform ships with seeded data from 6 UFC events (4 upcoming, 2 completed) across 70 venue sections, 126 marketing campaigns, 2,572 daily metric records, and 60 active alerts. Every feature works end-to-end against this data:

- The sales velocity dashboard renders sellthrough curves with historical comparison overlays.
- The forecast engine predicts final attendance with confidence scores per section.
- The alert system detects 24 soft sections and 12 spend efficiency issues from the demo data.
- The AI assistant answers questions using real data ("Section 301 is at 53% sellthrough vs. 65% historical average at 13 days out").
- The spend optimization engine identifies which channels to scale and which to pause, with exact reallocation percentages.
- The campaign timing engine correctly classifies each event into its marketing phase and generates phase-appropriate recommendations.

## Design

Three colors: black (#0a0a0a), white (#fafafa), light blue (#38bdf8). Inter font. Dark-first design built for data density. Responsive across desktop and mobile.
