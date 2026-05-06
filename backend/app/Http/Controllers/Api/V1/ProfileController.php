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
    public function __construct(protected ProfileService $profileService) {}

    public function completeProfile(CompleteProfileRequest $request)
    {
        $user = auth()->user();

        $user = $this->profileService->completeProfile($user, $request->validated());

        return ApiResponse::success('messages.profile_completed_successfully', UserResource::make($user));
    }

    public function show()
    {
        $user = auth()->user()->load('interests');

        return ApiResponse::success('messages.profile_fetched_successfully', UserResource::make($user));
    }

    /**
     * Get the authenticated user's selected interests.
     * GET /profile/interests
     */
    public function myInterests()
    {
        $interests = auth()->user()
            ->interests()
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            'messages.my_interests_fetched_successfully',
            InterestResource::collection($interests)
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $this->profileService->updateProfile(auth()->user(), $request->validated());

        return ApiResponse::success('messages.profile_updated_successfully', UserResource::make($user));
    }

    /**
     * Replace all of the authenticated user's interests in one call.
     * PUT /profile/interests   body: { interests: ["music","sport",...] }
     */
    public function updateInterests(UpdateInterestsRequest $request)
    {
        $user = auth()->user();

        $interestIds = Interest::whereIn('slug', $request->interests)->pluck('id')->toArray();

        $user->interests()->sync($interestIds);

        return ApiResponse::success('messages.interests_updated_successfully');
    }

    /**
     * Add a single interest to the authenticated user.
     * POST /profile/interests/{interest}   (bound by slug)
     */
    public function addInterest(Interest $interest)
    {
        $user = auth()->user();

        // syncWithoutDetaching is idempotent — re-adding the same interest
        // is a no-op rather than an error.
        $user->interests()->syncWithoutDetaching([$interest->id]);

        return ApiResponse::success(
            'messages.interest_added_successfully',
            InterestResource::make($interest)
        );
    }

    /**
     * Remove a single interest from the authenticated user.
     * DELETE /profile/interests/{interest}   (bound by slug)
     */
    public function removeInterest(Interest $interest)
    {
        $user = auth()->user();

        $user->interests()->detach($interest->id);

        return ApiResponse::success('messages.interest_removed_successfully');
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
        ]);

        return ApiResponse::success('messages.discovery_settings_updated');
    }
}
