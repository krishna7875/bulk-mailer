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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();

             $table->string('email')->index();
            $table->string('name')->nullable();
            $table->json('metadata')->nullable();

            $table->string('import_batch')->nullable();

            $table->enum('status', [
                'unsent',
                'queued',
                'sent',
                'failed',
                'suppressed'
            ])->default('unsent');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
