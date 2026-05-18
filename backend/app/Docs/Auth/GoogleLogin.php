<?php

namespace App\Docs\Auth;

use OpenApi\Annotations as OA;
/**
 * @OA\Post(
 *     path="/api/v1/auth/google",
 *     summary="Login with Google",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_token"},
 *             @OA\Property(property="id_token", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Logged in",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(property="data", ref="#/components/schemas/AuthResponse")
 *                 )
 *             }
 *         )
 *     )
 * )
 */
class GoogleLogin {}