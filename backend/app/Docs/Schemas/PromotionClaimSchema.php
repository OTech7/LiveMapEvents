<?php

namespace App\Docs\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PromotionClaim",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="voucher_code", type="string", example="ABCD1234"),
 *     @OA\Property(property="status", type="string", enum={"claimed","redeemed","expired"}, example="claimed"),
 *     @OA\Property(property="claimed_at", type="string", format="date-time"),
 *     @OA\Property(property="redeemed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time"),
 *     @OA\Property(property="promotion", ref="#/components/schemas/Promotion", nullable=true),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="phone", type="string")
 *     )
 * )
 */
class PromotionClaimSchema
{
}
