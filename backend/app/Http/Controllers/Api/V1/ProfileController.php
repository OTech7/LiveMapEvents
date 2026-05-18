<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Profile\UpdateDiscoverySettingsRequest;
use App\Http\Requests\Profile\UpdateInterestsRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Resources\InterestResource;
use App\Http\Resources\UserResource;
use App\Models\Interest;
use App\Services\InterestService;
use App\Services\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService  $profileService,
        protected InterestService $interestService,
    )
    {
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->completeProfile(auth()->user(), $request->validated());

        return ApiResponse::success('messages.profile_completed_successfully', UserResource::make($user));
    }

    public function show(): JsonResponse
    {
        return ApiResponse::success('messages.profile_fetched_successfully', UserResource::make(auth()->user()));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile(auth()->user(), $request->validated());

        return ApiResponse::success('messages.profile_updated_successfully', UserResource::make($user));
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $avatarUrl = $this->profileService->uploadAvatar(auth()->user(), $request->file('avatar'));

        return ApiResponse::success('messages.avatar_uploaded_successfully', ['avatar_url' => $avatarUrl]);
    }

    public function updateDiscoverySettings(UpdateDiscoverySettingsRequest $request): JsonResponse
    {
        $this->profileService->updateDiscoverySettings(auth()->user(), $request->validated());

        return ApiResponse::success('messages.discovery_settings_updated');
    }

    // ─── Interests ────────────────────────────────────────────────────────────

    public function myInterests(): JsonResponse
    {
        $interests = $this->interestService->getForUser(auth()->user());

        return ApiResponse::success('messages.interests_fetched_successfully', InterestResource::collection($interests));
    }

    public function updateInterests(UpdateInterestsRequest $request): JsonResponse
    {
        $this->interestService->syncBySlug(auth()->user(), $request->interests);

        return ApiResponse::success('messages.interests_updated_successfully');
    }

    public function addInterest(Interest $interest): JsonResponse
    {
        $this->interestService->attach(auth()->user(), $interest);

        return ApiResponse::success('messages.interests_updated_successfully');
    }

    public function removeInterest(Interest $interest): JsonResponse
    {
        $this->interestService->detach(auth()->user(), $interest);

        return ApiResponse::success('messages.interests_updated_successfully');
    }
}
