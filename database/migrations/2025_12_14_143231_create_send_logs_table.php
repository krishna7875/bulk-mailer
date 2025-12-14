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
        Schema::create('send_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shooter_id')->constrained();
            $table->foreignId('target_id')->constrained();
            $table->foreignId('shooter_target_mapping_id')->constrained();

            $table->enum('status', ['sent', 'failed']);
            $table->string('error_code')->nullable();
            $table->text('provider_response')->nullable();

            $table->timestamp('attempted_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_logs');
    }
};
