<?php

namespace App\Services;

use App\Exceptions\ProfileAlreadyCompletedException;
use App\Exceptions\ProfileCompletionException;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileService
{
    /**
     * Complete user profile for the first time.
     */
    public function completeProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Re-fetch with row-level lock so two concurrent requests
            // can't both pass the "not yet complete" check.
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($user->profile_complete) {
                throw new ProfileAlreadyCompletedException();
            }

            $location = $this->makePoint($data['lat'], $data['lng']);

            $update = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
                'location' => $location,
                'profile_complete' => true,
            ];

            // Only set the phone if the user doesn't already have one
            // (Google sign-in case). Phone-OTP users already have it.
            if (empty($user->phone) && !empty($data['phone'])) {
                $update['phone'] = $data['phone'];
            }

            $user->update($update);

            Log::info('profile_completed', ['user_id' => $user->id]);

            return $user->fresh();
        });
    }

    /**
     * Update profile fields after initial completion.
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'phone' => $data['phone'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
            ], fn($v) => $v !== null);

            if (isset($data['lat'], $data['lng'])) {
                $updateData['location'] = $this->makePoint($data['lat'], $data['lng']);
            }

            $user->update($updateData);

            return $user->fresh();
        });
    }

    /**
     * Store the uploaded avatar file and persist the URL on the user.
     */
    public function uploadAvatar(User $user, UploadedFile $file): string
    {
        $path = $file->store('avatars', 'public');

        $user->update(['avatar_url' => $path]);

        Log::info('avatar_uploaded', ['user_id' => $user->id, 'path' => $path]);

        return $path;
    }

    /**
     * Persist the user's discovery radius and notification preference.
     */
    public function updateDiscoverySettings(User $user, array $data): void
    {
        $user->update([
            'discovery_radius' => $data['radius'],
            'notify_nearby' => $data['notifications'] ?? $user->notify_nearby,
            'discovery_settings_complete' => true,
        ]);
    }

    /**
     * Check if profile is complete.
     */
    public function isProfileComplete(User $user): bool
    {
        return
            !empty($user->first_name) &&
            !empty($user->last_name) &&
            !empty($user->gender) &&
            !empty($user->dob) &&
            !empty($user->phone) &&
            !empty($user->location);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function makePoint(float $lat, float $lng): Point
    {
        if ($lat < -90 || $lat > 90) {
            throw new ProfileCompletionException('Invalid latitude');
        }

        if ($lng < -180 || $lng > 180) {
            throw new ProfileCompletionException('Invalid longitude');
        }

        return Point::makeGeodetic($lng, $lat);
    }
}
