<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('predicted_attendance');
            $table->decimal('predicted_revenue', 12, 2);
            $table->decimal('confidence', 5, 2)->default(0);
            $table->decimal('predicted_sellthrough', 5, 2)->default(0);
            $table->integer('demand_score')->default(0); // 1-100
            $table->json('model_params')->nullable();
            $table->date('forecast_date');
            $table->timestamps();

            $table->index(['event_id', 'forecast_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
