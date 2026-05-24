<?php

use App\Console\Commands\ExpireStaleClaims;
use App\Console\Commands\SyncPromotionStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Promotion status sync ─────────────────────────────────────────────────
// Runs every day at 00:05 to auto-expire promotions past their valid_to date
// and auto-activate promotions whose valid_from date has been reached.
Schedule::command(SyncPromotionStatus::class)->dailyAt('00:05');

// ── Stale claim expiry ────────────────────────────────────────────────────
// Hourly bulk UPDATE that flips CLAIMED → EXPIRED for vouchers past their
// expires_at. Replaces the per-request lazy-expiry block formerly in
// PromotionClaimService::getMyClaims().
Schedule::command(ExpireStaleClaims::class)->hourly();
