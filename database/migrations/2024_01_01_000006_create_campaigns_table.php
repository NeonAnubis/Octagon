<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('source'); // meta, google, klaviyo, instagram, twitter, youtube
            $table->string('type'); // paid, email, organic
            $table->string('name');
            $table->string('status')->default('active'); // active, paused, completed
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->json('targeting')->nullable();
            $table->timestamps();

            $table->index(['source', 'type']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
