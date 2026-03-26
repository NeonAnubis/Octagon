<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->integer('sold');
            $table->integer('available');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('velocity_hourly', 8, 2)->default(0);
            $table->decimal('velocity_daily', 8, 2)->default(0);
            $table->decimal('sellthrough_pct', 5, 2)->default(0);
            $table->integer('days_to_event')->default(0);
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['event_id', 'captured_at']);
            $table->index(['section_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_snapshots');
    }
};
