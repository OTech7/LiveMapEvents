<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Delete(
 *     path="/api/v1/profile/interests/{interest}",
 *     summary="Remove a single interest from the authenticated user",
 *     description="Detaches the interest (by slug) from the user's selection. Idempotent — removing an interest the user doesn't have selected is a no-op.",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="interest",
 *         in="path",
 *         required=true,
 *         description="Slug of the interest to remove",
 *         @OA\Schema(type="string", example="music")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Interest removed successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
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
class RemoveInterest
{
}
