<?php

namespace App\Docs\Schemas;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="first_name", type="string"),
 *     @OA\Property(property="last_name", type="string"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true)
 * )
 */
class UserSchema {}