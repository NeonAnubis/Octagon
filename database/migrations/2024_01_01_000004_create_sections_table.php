<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('price_tier'); // floor, lower, upper, vip
            $table->decimal('base_price', 10, 2);
            $table->decimal('current_price', 10, 2);
            $table->integer('capacity')->default(0);
            $table->integer('sold')->default(0);
            $table->integer('held')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->string('status')->default('available'); // available, soft, warning, critical, sold_out
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
