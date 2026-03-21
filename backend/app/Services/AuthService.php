<?php

namespace App\Services;

use App\Models\User;
use App\Actions\GenerateToken;
use App\Services\GoogleAuthService;
use App\Services\OTPService;

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

        return [
            'token' => $token,
            'profile_complete' => $user->profile_complete,
            'user' => $user
        ];
    }

    public function loginWithGoogle(string $idToken): array
    {
        $googleUser = $this->googleAuthService->verify($idToken);

        $user = User::firstOrCreate(
            ['google_id'=>$googleUser['sub']],
            [
                'first_name'=>$googleUser['given_name'] ?? '',
                'last_name'=>$googleUser['family_name'] ?? '',
                'avatar_url'=>$googleUser['picture'] ?? null,
                'dob' => $googleUser['dob'] ?? '7/5/2003',
                'profile_complete' => false

            ]
        );

        $token = $this->generateToken->handle($user);

        return [
            'token'=>$token,
            'profile_complete'=>$user->profile_complete,
            'user'=>$user
        ];
    }

}