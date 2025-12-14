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
        Schema::create('shooters', function (Blueprint $table) {
            $table->id();
        
            $table->string('name');
            $table->string('email')->unique();
            $table->text('description')->nullable();

            $table->integer('daily_quota')->default(200);
            $table->integer('sent_today')->default(0);
            $table->date('last_quota_date')->nullable();

            $table->text('refresh_token')->nullable(); // encrypted
            $table->enum('status', ['active', 'paused', 'disabled'])->default('paused');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shooters');
    }
};
