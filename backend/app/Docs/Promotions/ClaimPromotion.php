<?php

namespace App\Docs\Promotions;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/v1/promotions/{promotion}/claim",
 *     summary="Claim a promotion — generates a unique QR voucher for the user",
 *     tags={"Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=201,
 *         description="Promotion claimed successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", type="object",
 *                         @OA\Property(property="claim", ref="#/components/schemas/PromotionClaim"),
 *                         @OA\Property(property="promotion", ref="#/components/schemas/Promotion")
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(response=422, description="Not active, already claimed, limit reached, etc.")
 * )
 */
class ClaimPromotion
{
}
