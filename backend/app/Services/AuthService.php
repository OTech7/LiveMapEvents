<?php

namespace App\Services;

use App\Actions\GenerateToken;
use App\DTOs\AuthTokenResponse;
use App\Models\User;
use Illuminate\Support\Facades\Log;

// Note: OTPService is injected via the controller layer (AuthController),
// not here — AuthService only handles post-verification login logic.

class AuthService
{
    public function __construct(
        protected GenerateToken $generateToken,
        protected GoogleAuthService $googleAuthService
    )
    {
    }

    public function loginWithPhone(string $phone): AuthTokenResponse
    {
        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['profile_complete' => false],
        );

        $token = $this->generateToken->handle($user);

        Log::info('auth_phone_login', [
            'user_id' => $user->id,
            'is_new' => $user->wasRecentlyCreated,
        ]);

        return new AuthTokenResponse(
            token: $token,
            user: $user,
            profileComplete: (bool)$user->profile_complete,
            interestsComplete: $user->interests()->exists(),
            discoverySettingsComplete: (bool)$user->discovery_settings_complete,
        );
    }

    public function loginWithGoogle(string $idToken): AuthTokenResponse
    {
        $googleUser = $this->googleAuthService->verify($idToken);

        $user = User::firstOrCreate(
            ['google_id' => $googleUser['sub']],
            [
                'first_name' => $googleUser['given_name'] ?? '',
                'last_name' => $googleUser['family_name'] ?? '',
                'avatar_url' => $googleUser['picture'] ?? null,
                'dob' => $googleUser['dob'] ?? null,
                'profile_complete' => false,
            ]
        );

        $token = $this->generateToken->handle($user);

        Log::info('auth_google_login', [
            'user_id' => $user->id,
            'is_new' => $user->wasRecentlyCreated,
        ]);

        return new AuthTokenResponse(
            token: $token,
            user: $user,
            profileComplete: (bool)$user->profile_complete,
            interestsComplete: $user->interests()->exists(),
            discoverySettingsComplete: (bool)$user->discovery_settings_complete,
        );
    }
}
