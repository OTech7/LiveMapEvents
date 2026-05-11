<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterestResource;
use App\Models\Interest;
use App\Support\ApiResponse;

class InterestController extends Controller
{
    /**
     * List the global interest catalog.
     * Used by the mobile app to render the interest picker
     * during profile completion.
     */
    public function index()
    {
        $interests = Interest::query()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            'messages.interests_fetched_successfully',
            InterestResource::collection($interests)
        );
    }
}
