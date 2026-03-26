<?php

namespace App\Docs\Schemas;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     schema="AuthResponse",
 *     type="object",
 *     @OA\Property(property="token", type="string"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="profile_complete", type="boolean")
 * )
 */
class AuthResponse {}