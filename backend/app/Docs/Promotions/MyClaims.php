<?php

namespace App\Docs\Promotions;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/me/claims",
 *     summary="Get the authenticated user's voucher wallet",
 *     tags={"Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Vouchers fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PromotionClaim")))
 *             }
 *         )
 *     )
 * )
 */
class MyClaims
{
}
