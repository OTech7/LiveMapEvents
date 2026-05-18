<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds manually_deactivated to promotions.
 *
 * When true it means an admin/owner explicitly turned the promotion off.
 * The promotions:sync-status scheduler respects this flag and will NOT
 * auto-reactivate the promotion when its valid_from date is reached.
 *
 * false (default) = scheduler manages is_active automatically based on dates.
 * true            = human said "keep this off" — scheduler leaves it alone.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->boolean('manually_deactivated')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('manually_deactivated');
        });
    }
};
