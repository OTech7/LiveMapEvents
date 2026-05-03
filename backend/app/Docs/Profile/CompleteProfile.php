<?php

namespace App\Docs\Profile;
use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/v1/auth/complete-profile",
 *     summary="Complete user profile",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"first_name","last_name","gender","dob","lat","lng"},
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(
 *                 property="phone",
 *                 type="string",
 *                 nullable=true,
 *                 description="Required ONLY for users who do not already have a phone on file (e.g. Google sign-in). Must be omitted for phone-OTP users — their phone is already stored at OTP verification."
 *             ),
 *             @OA\Property(property="gender", type="string", enum={"male","female"}),
 *             @OA\Property(property="dob", type="string", format="date"),
 *             @OA\Property(property="lat", type="number"),
 *             @OA\Property(property="lng", type="number"),
 *             @OA\Property(property="avatar_url", type="string", nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Profile completed",
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
class CompleteProfile {}
