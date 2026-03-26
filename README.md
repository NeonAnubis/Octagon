# Octagon — AI-Powered Fight Analytics Platform

Real-time ticket sales intelligence meets marketing attribution, powered by AI.

## Overview

Octagon connects Ticketmaster ticket sales data with your full marketing stack (Meta Ads, Google Ads, Klaviyo, organic social) and surfaces actionable intelligence across both. It answers two questions in real time: **how are tickets selling** and **what is marketing doing about it**.

## Features

### Ticket Sales Analytics
- **Sales velocity dashboard** — sellthrough pace by section, price tier, and date with historical comparison against prior events
- **Predictive sellthrough modeling** — forecast final attendance and revenue by section using logistic growth projections
- **Automated soft-section alerts** — flag underperforming sections with severity levels (info/warning/critical) and AI-generated recommendations
- **Demand forecasting** — demand scores by fight card and market with confidence intervals
- **AI recommendations** — plain-English pricing actions when sections go soft

### Marketing Intelligence
- **Attribution modeling** — correlate marketing channel activity with ticket sales velocity across Meta, Google, Email, and organic channels
- **Campaign timing recommendations** — AI-powered suggestions for when to push spend based on sales pace, days-to-event, and marketing phase (awareness, consideration, urgency, fight week)
- **Audience targeting suggestions** — surface audience signals from top-converting campaigns vs. underperformers
- **Spend optimization alerts** — flag when spend is not tracking against velocity, recommend ROAS-weighted budget reallocation

### AI Assistant
- Natural language Q&A powered by OpenAI GPT-4o
- Context-aware: knows current sales data, marketing metrics, alerts, and forecasts
- Conversation history for follow-up questions
- Pre-built quick prompts for common questions

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel (PHP 8.4) |
| Frontend | React 19, TailwindCSS 4, Alpine.js |
| Database | PostgreSQL |
| Charts | Recharts |
| AI | OpenAI GPT-4o API |
| Build | Vite |
| Scheduling | Laravel Scheduler (artisan) |

## Data Integrations

| Source | Service Class | Status |
|--------|--------------|--------|
| Ticketmaster | `TicketmasterService` | Discovery + Inventory APIs |
| Meta / Instagram Ads | `MetaAdsService` | Marketing API v21 |
| Google Ads | `GoogleAdsService` | Google Ads API v17 (GAQL) |
| Klaviyo | `KlaviyoService` | Klaviyo API v3 |
| Instagram (organic) | `SocialMediaService` | Graph API |
| X / Twitter | `SocialMediaService` | API v2 |
| YouTube | `SocialMediaService` | Data API v3 + Analytics |

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

# Edit .env with your database and API credentials
# Required: DB_*, OPENAI_API_KEY
# Optional: TICKETMASTER_API_KEY, META_ADS_ACCESS_TOKEN, GOOGLE_ADS_DEVELOPER_TOKEN, KLAVIYO_API_KEY

php artisan migrate
php artisan db:seed   # Seeds realistic UFC demo data
npm run build
```

### Running

```bash
php artisan serve     # Backend at localhost:8000
npm run dev           # Vite dev server with HMR (development)
```

### Scheduled Tasks

```
* * * * * cd /path/to/octagon && php artisan schedule:run >> /dev/null 2>&1
```

Runs automatically:
- `octagon:sync-ticketmaster` — every 15 minutes
- `octagon:sync-marketing` — every hour
- `octagon:generate-alerts` — every 30 minutes

### Manual Commands

```bash
php artisan octagon:sync-ticketmaster --keyword=UFC
php artisan octagon:sync-marketing --source=meta
php artisan octagon:generate-alerts
```

## Project Structure

```
app/
  Console/Commands/           Artisan data sync commands
  Http/Controllers/Api/       REST API endpoints (7 controllers)
  Models/                     Eloquent models (9 models)
  Services/
    AnalyticsService          Core analytics engine
    ForecastService           Predictive modeling
    AiService                 OpenAI integration
    MarketingIntelligenceService  Timing, audience, spend optimization
    Integrations/             Ticketmaster, Meta, Google, Klaviyo, Social

resources/js/
  pages/                      10 React dashboard pages
  components/                 Reusable UI components
```

## API Endpoints

All endpoints prefixed with `/api/v1/`.

**Sales:** `/dashboard`, `/events`, `/events/{id}`, `/events/{id}/velocity`, `/events/{id}/forecast`, `/events/{id}/historical`

**Marketing:** `/marketing`, `/marketing/campaigns`, `/marketing/timing`, `/marketing/audience`, `/marketing/spend-optimization`

**Intelligence:** `/alerts`, `/ai/chat` (POST), `/ai/recommendations`

**System:** `/integrations`

## Design

- **Colors:** Black (#0a0a0a), White (#fafafa), Light Blue (#38bdf8)
- **Font:** Inter
- **Dark-first** design optimized for data density
- **Responsive** across desktop and mobile
