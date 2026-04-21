<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable PostGIS extension (required for geometry/location columns)
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique()->nullable();
            $table->string('google_id')->unique()->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male' , 'female'])->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('profile_complete')->default(false);
            $table->enum('user_type', ['attendee','business','admin'])->default('attendee');
            $table->geometry('location','POINT',4326)->nullable();
            $table->spatialIndex('location');
            $table->integer('discovery_radius')->default(500);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
