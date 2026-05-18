<?php

namespace App\Docs\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Interest",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Music"),
 *     @OA\Property(property="slug", type="string", example="music")
 * )
 */
class InterestSchema {}
