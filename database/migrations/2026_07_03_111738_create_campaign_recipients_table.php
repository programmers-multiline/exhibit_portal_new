<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_id');
            $table->string('email');
            $table->string('status')->default('sent'); // sent, delivered, opened, clicked, unsubscribed
            $table->timestamp('action_at')->nullable();
            $table->timestamps();
            
            $table->unique(['campaign_id', 'email']); // Iwas duplicate rows
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
