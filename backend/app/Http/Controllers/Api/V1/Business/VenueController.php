<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venues\StoreVenueRequest;
use App\Http\Requests\Venues\UpdateVenueRequest;
use App\Http\Resources\VenueResource;
use App\Models\Venue;
use App\Services\VenueService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    public function __construct(protected VenueService $venueService)
    {
    }

    /**
     * GET /v1/business/venues
     *
     * List all venues owned by the authenticated user.
     */
    public function index(): JsonResponse
    {
        $venues = $this->venueService->getForOwner(auth()->user());

        return ApiResponse::success(
            'messages.venues_fetched_successfully',
            VenueResource::collection($venues)->response()->getData(true)
        );
    }

    /**
     * POST /v1/business/venues
     *
     * Create a new venue owned by the authenticated user.
     */
    public function store(StoreVenueRequest $request): JsonResponse
    {
        $venue = $this->venueService->create(auth()->user(), $request->validated());

        return ApiResponse::success(
            'messages.venue_created_successfully',
            VenueResource::make($venue),
            201
        );
    }

    /**
     * GET /v1/business/venues/{venue}
     *
     * Show a single venue. Restricted to the owner.
     */
    public function show(Venue $venue): JsonResponse
    {
        $this->authorize('view', $venue);

        return ApiResponse::success(
            'messages.venue_fetched_successfully',
            VenueResource::make($venue)
        );
    }

    /**
     * PUT /v1/business/venues/{venue}
     *
     * Update a venue. Restricted to the owner.
     */
    public function update(UpdateVenueRequest $request, Venue $venue): JsonResponse
    {
        $this->authorize('update', $venue);

        $venue = $this->venueService->update($venue, $request->validated());

        return ApiResponse::success(
            'messages.venue_updated_successfully',
            VenueResource::make($venue)
        );
    }

    /**
     * DELETE /v1/business/venues/{venue}
     *
     * Delete a venue. Restricted to the owner.
     * Blocked if the venue has upcoming published events.
     */
    public function destroy(Venue $venue): JsonResponse
    {
        $this->authorize('delete', $venue);

        $this->venueService->delete($venue);

        return ApiResponse::success('messages.venue_deleted_successfully');
    }
}
