<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Online event support
            $table->boolean('is_online_event')->default(false)->after('is_free');
            $table->string('online_event_url')->nullable()->after('is_online_event');

            // Attendance control
            $table->unsignedInteger('rsvp_limit')->nullable()->after('online_event_url')
                ->comment('Max attendees. NULL = unlimited.');
            $table->unsignedTinyInteger('guest_limit')->default(0)->after('rsvp_limit')
                ->comment('Max guests each attendee may bring.');

            // Publishing workflow
            $table->string('publish_status')->default('published')->after('guest_limit')
                ->comment('published | draft');

            $table->index('publish_status');
            $table->index('is_online_event');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['publish_status']);
            $table->dropIndex(['is_online_event']);
            $table->dropColumn([
                'is_online_event',
                'online_event_url',
                'rsvp_limit',
                'guest_limit',
                'publish_status',
            ]);
        });
    }
};
