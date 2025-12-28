<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();

            // Template identity
            $table->string('name');                // Internal name (e.g. "Welcome Template")
            $table->string('subject');             // Can contain variables
            $table->longText('body');              // HTML or plain text

            // Template state
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');

            // Attachment (AUTO-filled, never manual)
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedInteger('attachment_size')->nullable(); // bytes

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

