<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterestResource;
use App\Services\InterestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InterestController extends Controller
{
    public function __construct(protected InterestService $interestService)
    {
    }

    /**
     * List the global interest catalog.
     * Used by the mobile app to render the interest picker
     * during profile completion.
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            'messages.interests_fetched_successfully',
            InterestResource::collection($this->interestService->getAll())
        );
    }
}
