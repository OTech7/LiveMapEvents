<?php

namespace App\Docs\Promotions;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/promotions/{promotion}",
 *     summary="Show a single promotion with the user's current claim",
 *     tags={"Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Promotion fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", type="object",
 *                         @OA\Property(property="promotion", ref="#/components/schemas/Promotion"),
 *                         @OA\Property(property="my_claim", ref="#/components/schemas/PromotionClaim", nullable=true),
 *                         @OA\Property(property="is_active_now", type="boolean"),
 *                         @OA\Property(property="is_upcoming_today", type="boolean")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */
class ShowPromotion
{
}
