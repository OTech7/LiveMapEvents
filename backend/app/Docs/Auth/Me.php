<?php

namespace App\Docs\Auth;
use OpenApi\Annotations as OA;
/**
 * @OA\Get(
 *     path="/api/v1/auth/me",
 *     summary="Get current user",
 *     tags={"Auth"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="User data",
 *         @OA\JsonContent(ref="#/components/schemas/User")
 *     )
 * )
 */
class Me {}