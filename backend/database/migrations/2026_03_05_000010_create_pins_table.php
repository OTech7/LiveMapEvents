<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->geometry('location','POINT',4326)->nullable();
            $table->spatialIndex('location');
            $table->boolean('has_promotion')->default(false);
            $table->string('label')->nullable();
            $table->timestamps();
            });
            
            DB::statement("
            ALTER TABLE pins 
            ADD CONSTRAINT pins_venue_or_event_check 
            CHECK (venue_id IS NOT NULL OR event_id IS NOT NULL)
            ");
        }

    public function down(): void
    {
        Schema::dropIfExists('pins');
    }
};

