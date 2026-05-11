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
use App\Services\ProfileService;
use App\Support\ApiResponse;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profileService)
    {
    }

    public function completeProfile(CompleteProfileRequest $request)
    {
        $user = auth()->user();

        $user = $this->profileService->completeProfile($user, $request->validated());

        return ApiResponse::success('messages.profile_completed_successfully', UserResource::make($user));
    }

    public function show()
    {
        return ApiResponse::success('messages.profile_fetched_successfully', UserResource::make(auth()->user()));
    }

    public function getInterests()
    {
        $interests = Interest::query()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            'messages.interests_fetched_successfully',
            InterestResource::collection($interests)
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $this->profileService->updateProfile(auth()->user(), $request->validated());

        return ApiResponse::success('messages.profile_updated_successfully', UserResource::make($user));
    }

    public function myInterests()
    {
        $user = auth()->user();

        return ApiResponse::success(
            'messages.interests_fetched_successfully',
            InterestResource::collection($user->interests)
        );
    }

    public function updateInterests(UpdateInterestsRequest $request)
    {
        $user = auth()->user();

        $interestIds = Interest::whereIn('slug', $request->interests)->pluck('id')->toArray();

        $user->interests()->sync($interestIds);

        return ApiResponse::success('messages.interests_updated_successfully');
    }

    public function addInterest(Interest $interest)
    {
        $user = auth()->user();

        $user->interests()->syncWithoutDetaching([$interest->id]);

        return ApiResponse::success('messages.interests_updated_successfully');
    }

    public function removeInterest(Interest $interest)
    {
        $user = auth()->user();

        $user->interests()->detach($interest->id);

        return ApiResponse::success('messages.interests_updated_successfully');
    }

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        $user = auth()->user();

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar_url' => $path]);

        return ApiResponse::success('messages.avatar_uploaded_successfully', ['avatar_url' => $path]);
    }

    public function updateDiscoverySettings(UpdateDiscoverySettingsRequest $request)
    {
        $user = auth()->user();

        $user->update([
            'discovery_radius' => $request->radius,
            'notify_nearby' => $request->notifications ?? $user->notify_nearby,
            'discovery_settings_complete' => true,
        ]);

        return ApiResponse::success('messages.discovery_settings_updated');
    }
}
