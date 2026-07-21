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
       Schema::create('update_address_logs', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('company_id'); // Para sa foreign key
                    $table->string('previous_address');             // O $table->text() kung mahaba
                    $table->string('current_address');              // O $table->text() kung mahaba
                    $table->foreignId('updated_by')->constrained('users'); // Kumokonekta sa users table
                    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_update_address_logs');
    }
};
