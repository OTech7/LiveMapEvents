<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\StorePromotionRequest;
use App\Http\Requests\Promotions\UpdatePromotionRequest;
use App\Http\Resources\PromotionClaimResource;
use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use App\Services\PromotionService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(protected PromotionService $promotionService)
    {
    }

    public function index(Request $request)
    {
        $promotions = $this->promotionService->getForOwner(
            auth()->user(),
            $request->integer('venue_id') ?: null
        );

        return ApiResponse::success(
            'messages.promotions_fetched_successfully',
            PromotionResource::collection($promotions)
        );
    }

    public function store(StorePromotionRequest $request)
    {
        $promotion = $this->promotionService->create(auth()->user(), $request->validated());

        return ApiResponse::success(
            'messages.promotion_created_successfully',
            PromotionResource::make($promotion->load('venue')),
            201
        );
    }

    public function show(Promotion $promotion)
    {
        return ApiResponse::success(
            'messages.promotion_fetched_successfully',
            PromotionResource::make($promotion->load('venue'))
        );
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $promotion = $this->promotionService->update(auth()->user(), $promotion, $request->validated());

        return ApiResponse::success(
            'messages.promotion_updated_successfully',
            PromotionResource::make($promotion)
        );
    }

    public function destroy(Promotion $promotion)
    {
        $this->authorize('delete', $promotion);

        $this->promotionService->delete(auth()->user(), $promotion);

        return ApiResponse::success('messages.promotion_deleted_successfully');
    }

    public function claims(Promotion $promotion)
    {
        $this->authorize('viewClaims', $promotion);

        $claims = $promotion->claims()
            ->with('user')
            ->latest('claimed_at')
            ->paginate(50);

        return ApiResponse::success(
            'messages.promotion_claims_fetched_successfully',
            PromotionClaimResource::collection($claims)
        );
    }
}
