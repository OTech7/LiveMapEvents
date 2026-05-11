<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_code', 12)->unique();
            $table->enum('status', ['claimed', 'redeemed', 'expired'])->default('claimed');
            $table->timestamp('claimed_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['promotion_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_claims');
    }
};
