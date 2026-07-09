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
        Schema::create('removed_psc_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->nullable();
            $table->integer('prev_psc_num')->nullable(); // Idinagdag ang nullable kung pwedeng walang laman
            $table->integer('removed_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('removed_psc');
    }
};
