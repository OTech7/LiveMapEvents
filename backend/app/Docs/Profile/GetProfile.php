<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/api/v1/profile",
 *     summary="Get user profile",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Profile fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", ref="#/components/schemas/User")
 *                 )
 *             }
 *         )
 *     )
 * )
 */
class GetProfile {}