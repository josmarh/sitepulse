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
        Schema::create('monitoring_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('check_type', 30)->default('uptime');

            $table->string('status', 20)->default('pending');

            $table->unsignedSmallInteger('http_status_code')
                ->nullable();

            $table->unsignedInteger('response_time_ms')
                ->nullable();

            $table->text('error_message')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamp('checked_at')->nullable();

            $table->timestamps();

            $table->index(['website_id', 'checked_at']);
            $table->index(['check_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_checks');
    }
};
