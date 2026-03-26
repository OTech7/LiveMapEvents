<?php

namespace App\Docs\Schemas;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=true
 *     )
 * )
 */
class ErrorResponse {}