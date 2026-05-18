<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/profile/interests",
 *     summary="Get the authenticated user's selected interests",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Interests fetched successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Your interests fetched successfully"),
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
class GetMyInterests
{
}
