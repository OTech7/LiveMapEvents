<?php

namespace App\Enums\OTP;

enum OtpSendStatus: string
{
    case SENT = 'sent';
    case COOLDOWN_ACTIVE = 'cooldown_active';
}