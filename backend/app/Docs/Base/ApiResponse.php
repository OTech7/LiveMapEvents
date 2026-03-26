<?php

namespace App\Docs\Base;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="messages.success"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 */
class ApiResponse {}