<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/v1/profile/interests/{interest}",
 *     summary="Add a single interest to the authenticated user",
 *     description="Adds the interest identified by its slug to the user's selection. Idempotent — re-adding an already-selected interest is a no-op.",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="interest",
 *         in="path",
 *         required=true,
 *         description="Slug of the interest (e.g. 'music', 'sports')",
 *         @OA\Schema(type="string", example="music")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Interest added successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Interest added successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/Interest"),
 *             @OA\Property(property="errors", nullable=true, example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Interest not found",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class AddInterest
{
}
