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
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('url', 2048);
            $table->string('host', 253)->index();

            $table->unsignedSmallInteger('check_interval_minutes')
                ->default(5);

            $table->unsignedSmallInteger('timeout_seconds')
                ->default(10);

            $table->boolean('is_active')->default(true);

            $table->string('current_status', 20)
                ->default('unknown');

            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'next_check_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
