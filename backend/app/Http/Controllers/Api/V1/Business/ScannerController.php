<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\RedeemVoucherRequest;
use App\Http\Resources\PromotionClaimResource;
use App\Services\PromotionClaimService;
use App\Support\ApiResponse;

class ScannerController extends Controller
{
    public function __construct(protected PromotionClaimService $claimService)
    {
    }

    public function redeem(RedeemVoucherRequest $request)
    {
        $claim = $this->claimService->redeem(
            $request->validated('voucher_code'),
            auth()->user()
        );

        return ApiResponse::success(
            'messages.voucher_redeemed_successfully',
            PromotionClaimResource::make($claim)
        );
    }
}
