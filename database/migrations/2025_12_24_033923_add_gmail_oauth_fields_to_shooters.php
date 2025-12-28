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
        Schema::table('shooters', function (Blueprint $table) {
            $table->text('gmail_access_token')->nullable();
            $table->text('gmail_refresh_token')->nullable();
            $table->timestamp('gmail_token_expires_at')->nullable();
            $table->timestamp('gmail_connected_at')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shooters', function (Blueprint $table) {
            //
        });
    }
};
