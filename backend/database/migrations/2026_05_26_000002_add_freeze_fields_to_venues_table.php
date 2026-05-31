<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->timestamp('frozen_at')->nullable()->after('notes');
            $table->string('freeze_reason')->nullable()->after('frozen_at');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['frozen_at', 'freeze_reason']);
            $table->boolean('is_active')->default(true);
        });
    }
};
