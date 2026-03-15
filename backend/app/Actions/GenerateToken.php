<?php

namespace App\Actions;

use App\Models\User;

class GenerateToken
{
    public function handle(User $user): string
    {
        return $user
            ->createToken('mobile')
            ->plainTextToken;
    }
}