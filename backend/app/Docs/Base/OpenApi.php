<?php

namespace App\Docs\Base;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Live Map Events API",
 *         version="1.0.0",
 *         description="API documentation for Live Map Events backend"
 *     ),
 *     @OA\Server(
 *         url="https://api.live-events-map.tech",
 *         description="Production"
 *     ),
 *     @OA\Server(
 *         url="http://localhost:8000",
 *         description="Local development (artisan serve)"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class OpenApi
{
}