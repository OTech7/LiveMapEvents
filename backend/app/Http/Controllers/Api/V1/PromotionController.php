<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\NearbyPromotionsRequest;
use App\Http\Resources\PromotionClaimResource;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use App\Services\PromotionClaimService;
use App\Services\PromotionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService      $promotionService,
        protected PromotionClaimService $claimService
    )
    {
    }

    public function nearby(NearbyPromotionsRequest $request): JsonResponse
    {
        $promotions = $this->promotionService->getNearby($request->validated());

        return ApiResponse::success(
            'messages.nearby_promotions_fetched_successfully',
            PromotionResource::collection($promotions)
        );
    }

    public function show(Promotion $promotion): JsonResponse
    {
        $promotion->load('venue');

        $myClaim = $this->claimService->getActiveClaimForUser($promotion, auth()->user());

        return ApiResponse::success(
            'messages.promotion_fetched_successfully',
            [
                'promotion' => PromotionResource::make($promotion),
                'my_claim' => $myClaim ? PromotionClaimResource::make($myClaim) : null,
                'is_active_now' => $promotion->isActiveNow(),
                'is_upcoming_today' => $promotion->isUpcomingToday(),
            ]
        );
    }

    public function claim(Promotion $promotion): JsonResponse
    {
        $claim = $this->claimService->claim($promotion, auth()->user());

        return ApiResponse::success(
            'messages.promotion_claimed_successfully',
            [
                'claim' => PromotionClaimResource::make($claim),
                'promotion' => PromotionResource::make($promotion),
            ],
            201
        );
    }

    public function myClaims(): JsonResponse
    {
        $claims = $this->claimService->getMyClaims(auth()->user());

        return ApiResponse::success(
            'messages.my_claims_fetched_successfully',
            PromotionClaimResource::collection($claims)
        );
    }
}
