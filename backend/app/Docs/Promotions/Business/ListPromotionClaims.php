<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/business/promotions/{promotion}/claims",
 *     summary="List all user claims for a promotion",
 *     tags={"Business / Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Claims fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PromotionClaim")))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=403, description="Not your promotion")
 * )
 */
class ListPromotionClaims
{
}
