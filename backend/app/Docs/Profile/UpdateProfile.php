<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Put(
 *     path="/api/v1/profile",
 *     summary="Update user profile",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(property="gender", type="string", enum={"male","female"}),
 *             @OA\Property(property="dob", type="string", format="date"),
 *             @OA\Property(property="phone", type="string"),
 *             @OA\Property(property="avatar_url", type="string"),
 *             @OA\Property(property="lat", type="number"),
 *             @OA\Property(property="lng", type="number")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Profile updated successfully",
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
class UpdateProfile {}