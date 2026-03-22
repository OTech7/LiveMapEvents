<?php

namespace App\Enums;

enum OtpSendStatus: string
{
    case SENT = 'sent';
    case COOLDOWN_ACTIVE = 'cooldown_active';
}