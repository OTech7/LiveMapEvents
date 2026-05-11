<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/business/promotions",
 *     summary="List all promotions for the authenticated business owner",
 *     tags={"Business / Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="venue_id", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Promotions fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Promotion")))
 *             }
 *         )
 *     )
 * )
 */
class ListPromotions
{
}
