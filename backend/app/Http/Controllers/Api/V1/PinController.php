<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pins\NearbyPinsRequest;
use App\Services\PinService;
use App\Support\ApiResponse;

class PinController extends Controller
{
    public function __construct(protected PinService $pinService) {}

    public function nearby(NearbyPinsRequest $request)
    {
        $pins = $this->pinService->getNearby($request->validated());

        return ApiResponse::success('messages.nearby_pins_fetched_successfully',$pins);
    }
}