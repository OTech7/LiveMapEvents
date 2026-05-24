<?php

namespace App\Console\Commands;

use App\Enums\PromotionClaimStatus;
use App\Models\PromotionClaim;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Bulk-expire promotion claims whose expires_at has passed.
 *
 * Previously this was done lazily inside PromotionClaimService::getMyClaims()
 * on every wallet view — O(claims) per page view. Now a single bulk UPDATE
 * runs hourly via the scheduler (registered in routes/console.php).
 */
class ExpireStaleClaims extends Command
{
    protected $signature = 'claims:expire-stale';
    protected $description = 'Mark claimed promotion vouchers as expired once their expires_at has passed.';

    public function handle(): int
    {
        $affected = PromotionClaim::query()
            ->where('status', PromotionClaimStatus::CLAIMED->value)
            ->where('expires_at', '<', now())
            ->update(['status' => PromotionClaimStatus::EXPIRED->value]);

        Log::info('expired_claims_batch', ['count' => $affected]);

        $this->info("claims:expire-stale — expired: {$affected}");

        return self::SUCCESS;
    }
}
