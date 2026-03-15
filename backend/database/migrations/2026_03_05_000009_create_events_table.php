<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('is_free')->default(false);
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->index(['venue_id', 'starts_at']);
            $table->index('category');
            $table->index('starts_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

