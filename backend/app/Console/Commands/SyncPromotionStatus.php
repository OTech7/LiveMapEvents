<?php

namespace App\Console\Commands;

use App\Models\Promotion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Automatically manages promotion is_active based on valid_from / valid_to dates.
 *
 * Auto-expire : is_active → false  when valid_to has passed
 * Auto-activate: is_active → true  when valid_from is reached (and not yet expired)
 *
 * Scheduled daily at 00:05 via routes/console.php.
 * Can also be run manually: php artisan promotions:sync-status
 */
class SyncPromotionStatus extends Command
{
    protected $signature = 'promotions:sync-status';
    protected $description = 'Auto-activate and auto-expire promotions based on their date range.';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        // ── Auto-expire ────────────────────────────────────────────────
        // Promotions whose valid_to has passed and are still marked active.
        $expired = Promotion::where('is_active', true)
            ->whereNotNull('valid_to')
            ->where('valid_to', '<', $today)
            ->update(['is_active' => false]);

        // ── Auto-activate ──────────────────────────────────────────────
        // Promotions whose valid_from has been reached and that are still
        // within their valid window (valid_to is null or in the future).
        //
        // IMPORTANT: skips promotions where manually_deactivated = true.
        // That flag means an admin/owner explicitly turned the promotion off,
        // so we must not override their decision. The flag is cleared when they
        // manually turn the promotion back on (is_active → true).
        $activated = Promotion::where('is_active', false)
            ->where('manually_deactivated', false)
            ->where('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $today);
            })
            ->update(['is_active' => true]);

        $this->info("promotions:sync-status — expired: {$expired}, activated: {$activated}");

        return self::SUCCESS;
    }
}
