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
        Schema::create('shooter_target_mappings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shooter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained()->cascadeOnDelete();

            $table->date('assigned_date')->index();

            $table->enum('status', [
                'assigned',
                'sent',
                'failed'
            ])->default('assigned');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            // 👇 SHORT, MANUAL INDEX NAME (IMPORTANT)
            $table->unique(
                ['shooter_id', 'target_id', 'assigned_date'],
                'uq_shooter_target_day'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shooter_target_mappings');
    }
};
