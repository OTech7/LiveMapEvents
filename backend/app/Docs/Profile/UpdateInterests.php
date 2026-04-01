<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Put(
 *     path="/api/v1/profile/interests",
 *     summary="Update user interests",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"interests"},
 *             @OA\Property(
 *                 property="interests",
 *                 type="array",
 *                 minItems=3,
 *                 maxItems=10,
 *                 @OA\Items(
 *                     type="string",
 *                     example="music"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Interests updated successfully",
 *         @OA\JsonContent(
 *             ref="#/components/schemas/ApiResponse"
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
class UpdateInterests {}