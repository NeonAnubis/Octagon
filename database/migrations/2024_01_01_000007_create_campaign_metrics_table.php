<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('revenue_attributed', 10, 2)->default(0);
            $table->decimal('cpm', 8, 2)->default(0);
            $table->decimal('cpc', 8, 2)->default(0);
            $table->decimal('cpa', 8, 2)->default(0);
            $table->decimal('roas', 8, 2)->default(0);
            $table->decimal('ctr', 6, 4)->default(0);
            $table->integer('reach')->default(0);
            $table->integer('engagement')->default(0);
            // Email specific
            $table->integer('sends')->default(0);
            $table->integer('opens')->default(0);
            $table->integer('unique_clicks')->default(0);
            $table->decimal('open_rate', 6, 4)->default(0);
            $table->decimal('click_rate', 6, 4)->default(0);
            $table->timestamps();

            $table->unique(['campaign_id', 'date']);
            $table->index(['event_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_metrics');
    }
};
