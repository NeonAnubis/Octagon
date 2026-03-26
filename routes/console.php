<?php

use Illuminate\Support\Facades\Schedule;

// Sync Ticketmaster sales data every 15 minutes
Schedule::command('octagon:sync-ticketmaster')->everyFifteenMinutes();

// Sync marketing data every hour
Schedule::command('octagon:sync-marketing')->hourly();

// Generate alerts every 30 minutes
Schedule::command('octagon:generate-alerts')->everyThirtyMinutes();
