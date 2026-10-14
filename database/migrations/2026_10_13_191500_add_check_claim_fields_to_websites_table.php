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
        Schema::table('websites', function (Blueprint $table) {
            $table->timestamp('check_claimed_at')->nullable()->index();
            $table->uuid('check_claim_token')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropUnique(['check_claim_token']);
            $table->dropIndex(['check_claimed_at']);
            $table->dropColumn([
                'check_claimed_at',
                'check_claim_token',
            ]);
        });
    }
};
