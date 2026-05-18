<?php

namespace App\Exceptions;

use Exception;

class ProfileAlreadyCompletedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'messages.profile_already_completed',
            409
        );
    }
}
