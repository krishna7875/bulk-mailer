<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shooter_target_mappings', function (Blueprint $table) {
            $table->foreignId('email_template_id')
                  ->nullable()
                  ->after('target_id')
                  ->constrained('email_templates')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shooter_target_mappings', function (Blueprint $table) {
            $table->dropForeign(['email_template_id']);
            $table->dropColumn('email_template_id');
        });
    }
};
