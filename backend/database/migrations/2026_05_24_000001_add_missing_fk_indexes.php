<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes on foreign-key columns and a composite "active window" index
 * on promotions.
 *
 * Background: PostgreSQL does NOT automatically index foreign-key columns
 * (only MySQL does). Without these, every "lookup rows belonging to X"
 * query falls back to a sequential scan once the tables grow.
 *
 * Columns already covered by a unique constraint or composite index on
 * their left-most position are intentionally skipped.
 */
return new class extends Migration {
    public function up(): void
    {
        // venues.owner_id — frequently filtered in PromotionService::getForOwner()
        Schema::table('venues', function (Blueprint $table) {
            $table->index('owner_id');
        });

        // checkins.user_id — "my checkins" lookup
        Schema::table('checkins', function (Blueprint $table) {
            $table->index('user_id');
        });

        // loyalty_accounts.venue_id — venue-wide loyalty queries
        Schema::table('loyalty_accounts', function (Blueprint $table) {
            $table->index('venue_id');
        });

        // loyalty_transactions: both FKs unindexed
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->index('loyalty_account_id');
            $table->index('checkin_id');
        });

        // vibe_stories: all FKs unindexed
        Schema::table('vibe_stories', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('venue_id');
            $table->index('event_id');
            $table->index('removed_by');
        });

        // business_verifications: both FKs unindexed
        Schema::table('business_verifications', function (Blueprint $table) {
            $table->index('venue_id');
            $table->index('reviewed_by');
        });

        // pins: only spatial index exists today
        Schema::table('pins', function (Blueprint $table) {
            $table->index('venue_id');
            $table->index('event_id');
        });

        // user_interests.interest_id — left-most of unique is user_id, so
        // interest_id alone isn't covered.
        Schema::table('user_interests', function (Blueprint $table) {
            $table->index('interest_id');
        });

        // saved_events.event_id — same reason as above
        Schema::table('saved_events', function (Blueprint $table) {
            $table->index('event_id');
        });

        // promotions: composite for nearby/active queries
        // PromotionService::getNearby() filters is_active + valid_from + valid_to
        Schema::table('promotions', function (Blueprint $table) {
            $table->index(
                ['is_active', 'valid_from', 'valid_to'],
                'promotions_active_window_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['owner_id']);
        });

        Schema::table('checkins', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('loyalty_accounts', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
        });

        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropIndex(['loyalty_account_id']);
            $table->dropIndex(['checkin_id']);
        });

        Schema::table('vibe_stories', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['event_id']);
            $table->dropIndex(['removed_by']);
        });

        Schema::table('business_verifications', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['reviewed_by']);
        });

        Schema::table('pins', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['event_id']);
        });

        Schema::table('user_interests', function (Blueprint $table) {
            $table->dropIndex(['interest_id']);
        });

        Schema::table('saved_events', function (Blueprint $table) {
            $table->dropIndex(['event_id']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropIndex('promotions_active_window_idx');
        });
    }
};
