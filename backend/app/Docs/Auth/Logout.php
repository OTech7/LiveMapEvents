<?php

namespace App\Docs\Auth;
use OpenApi\Annotations as OA;
/**
 * @OA\Post(
 *     path="/api/v1/auth/logout",
 *     summary="Logout",
 *     tags={"Auth"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Logged out",
 *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
 *     )
 * )
 */
class Logout {}