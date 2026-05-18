<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/business/promotions/{promotion}",
 *     summary="Get a single promotion (owner only)",
 *     tags={"Business / Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Promotion fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Promotion"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=403, description="Not your promotion"),
 *     @OA\Response(response=404, description="Not found")
 * )
 */
class ShowPromotion
{
}
