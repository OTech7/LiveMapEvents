<?php

namespace App\Services;

use Google_Client;
use Illuminate\Validation\ValidationException;

class GoogleAuthService
{
    public function verify(string $idToken): array
    {
        $client = new Google_Client([
            'client_id'=>config('services.google.client_id')
        ]);

        $payload = $client->verifyIdToken($idToken);

        if(!$payload){
            throw ValidationException::withMessages([
                'token' => ['Invalid Google token']
            ]);
        }

        return $payload;
    }
}