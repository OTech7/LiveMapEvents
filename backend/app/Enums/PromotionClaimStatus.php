<?php

namespace App\Enums;

enum PromotionClaimStatus: string
{
    case CLAIMED = 'claimed';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';
}
