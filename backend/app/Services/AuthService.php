<?php

namespace App\Services;

use App\Actions\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(
        protected OTPService $otpService,
        protected GenerateToken $generateToken,
        protected GoogleAuthService $googleAuthService
    ) {}

    public function loginWithPhone(string $phone): array
    {
        $user = User::firstOrCreate(['phone' => $phone]);

        $token = $this->generateToken->handle($user);

        Log::info('auth_phone_login', [
            'user_id' => $user->id,
            'is_new' => $user->wasRecentlyCreated,
        ]);

        return [
            'token' => $token,
            'profile_complete' => $user->profile_complete,
            'interests_complete' => $user->interests()->exists(),
            'discovery_settings_complete' => $user->discovery_settings_complete,
            'user' => $user,
        ];
    }

    public function loginWithGoogle(string $idToken): array
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

        return [
            'token' => $token,
            'profile_complete' => $user->profile_complete,
            'interests_complete' => $user->interests()->exists(),
            'discovery_settings_complete' => $user->discovery_settings_complete,
            'user' => $user,
        ];
    }
}
