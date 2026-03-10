<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('interaction_type');
            $table->integer('duration_seconds')->nullable();
            $table->timestampTz('interacted_at')->useCurrent();
            $table->timestamps();
            $table->index(['event_id', 'interaction_type', 'interacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interactions');
    }
};

