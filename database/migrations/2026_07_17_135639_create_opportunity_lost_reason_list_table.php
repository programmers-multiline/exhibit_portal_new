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
            Schema::create('opportunity_lost_reason_list', function (Blueprint $table) {
            $table->id();
            $table->string('type_reason'); // Binago: ginawang string at ipinasok ang column name
            $table->unsignedBigInteger('type_status')->default(1);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunity_lost_reason_list');
    }
};
