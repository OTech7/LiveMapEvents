<?php

namespace App\Docs\Promotions;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/promotions/nearby",
 *     summary="Get active and upcoming promotions near a location",
 *     tags={"Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="lat", in="query", required=true, @OA\Schema(type="number", example=24.7136)),
 *     @OA\Parameter(name="lng", in="query", required=true, @OA\Schema(type="number", example=46.6753)),
 *     @OA\Parameter(name="radius", in="query", required=false, description="Search radius in meters (default 5000, max 50000)", @OA\Schema(type="number", example=5000)),
 *     @OA\Response(
 *         response=200,
 *         description="Nearby promotions fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Promotion")))
 *             }
 *         )
 *     )
 * )
 */
class NearbyPromotions
{
}
