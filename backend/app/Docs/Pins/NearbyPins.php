<?php

namespace App\Docs\Pins;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/pins/nearby",
 *     summary="Get nearby pins",
 *     tags={"Pins"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="lat",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="lng",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="radius",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="types[]",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="array", @OA\Items(type="string"))
 *     ),
 *     @OA\Parameter(
 *         name="categories[]",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="array", @OA\Items(type="integer"))
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Nearby pins fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="array",
 *                         @OA\Items(type="object")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */
class NearbyPins {}