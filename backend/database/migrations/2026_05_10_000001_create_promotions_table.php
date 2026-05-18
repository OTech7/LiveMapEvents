<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 8, 2);
            $table->enum('recurrence_type', ['one_time', 'recurring'])->default('recurring');
            // ISO days: 1=Monday … 7=Sunday, e.g. [1,5] = Mon+Fri
            $table->json('days_of_week')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->date('valid_from');
            $table->date('valid_to')->nullable();          // null = no end date
            $table->unsignedInteger('max_total_redemptions')->nullable(); // null = unlimited
            $table->unsignedInteger('max_per_user_redemptions')->default(1);
            $table->text('terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['venue_id', 'is_active']);
            $table->index(['valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
