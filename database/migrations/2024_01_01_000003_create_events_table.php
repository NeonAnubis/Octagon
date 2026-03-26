<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('ticketmaster_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('venue');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country')->default('US');
            $table->string('market')->nullable();
            $table->dateTime('event_date');
            $table->dateTime('on_sale_date')->nullable();
            $table->integer('total_capacity')->default(0);
            $table->integer('total_sold')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->string('fight_card_tier')->nullable(); // main_event, co_main, prelim
            $table->json('fight_card')->nullable();
            $table->string('status')->default('upcoming'); // upcoming, on_sale, sold_out, completed
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
