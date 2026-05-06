<?php

namespace App\Docs\Interests;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/interests",
 *     summary="List the global interest catalog",
 *     description="Returns the full list of interests available for users to choose from (Music, Sport, Food, etc.). Read-only — used by the mobile app to render the interest picker during profile completion.",
 *     tags={"Interests"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Interests fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Interests fetched successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Interest")
 *             ),
 *             @OA\Property(property="errors", nullable=true, example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class ListInterests
{
}
