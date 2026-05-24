<?php

namespace App\DTOs;

use App\Models\User;

final readonly class AuthTokenResponse
{
    public function __construct(
        public string $token,
        public User   $user,
        public bool   $profileComplete,
        public bool   $interestsComplete,
        public bool   $discoverySettingsComplete,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'user' => $this->user,
            'profile_complete' => $this->profileComplete,
            'interests_complete' => $this->interestsComplete,
            'discovery_settings_complete' => $this->discoverySettingsComplete,
        ];
    }
}
