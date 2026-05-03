<?php

namespace App\Services;

use App\Exceptions\ProfileAlreadyCompletedException;
use App\Exceptions\ProfileCompletionException;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    /**
     * Complete user profile
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

            return $user->fresh();
        });
    }

    /**
     * Update profile after completion
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {

            $updateData = [];

            if (isset($data['first_name'])) {
                $updateData['first_name'] = $data['first_name'];
            }

            if (isset($data['last_name'])) {
                $updateData['last_name'] = $data['last_name'];
            }

            if (isset($data['gender'])) {
                $updateData['gender'] = $data['gender'];
            }

            if (isset($data['dob'])) {
                $updateData['dob'] = $data['dob'];
            }

            if (isset($data['phone'])) {
                $updateData['phone'] = $data['phone'];
            }

            if (isset($data['avatar_url'])) {
                $updateData['avatar_url'] = $data['avatar_url'];
            }

            if (isset($data['lat']) && isset($data['lng'])) {
                $updateData['location'] = $this->makePoint(
                    $data['lat'],
                    $data['lng']
                );
            }

            $user->update($updateData);

            return $user->fresh();
        });
    }

    /**
     * Check if profile is complete
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

    /**
     * Create spatial POINT
     */
    private function makePoint(float $lat, float $lng): Point
    {
        if ($lat < -90 || $lat > 90) {
            throw new ProfileCompletionException('Invalid latitude');
        }

        if ($lng < -180 || $lng > 180) {
            throw new ProfileCompletionException('Invalid longitude');
        }

         return Point::makeGeodetic($lng,$lat);
    }

}
