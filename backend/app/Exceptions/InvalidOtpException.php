<?php

namespace App\Exceptions;

use Exception;

class InvalidOtpException extends Exception
{
    protected $message = 'messages.invalid_otp';
}