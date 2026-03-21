<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use App\Support\ApiResponse;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profileService) {}

    public function completeProfile(CompleteProfileRequest $request) {

        $user = auth()->user();
        $user = $this->profileService->completeProfile(
            $user,
            $request->validated()
        );
        return ApiResponse::success(__('messages.profile_completed_successfully'), UserResource::make($user));
    }
}