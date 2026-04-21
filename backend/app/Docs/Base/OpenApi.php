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
 *         url="http://localhost:8000",
 *         description="Local Server"
 *     ),
 *     @OA\Server(
 *         url="http://live-events-map.tech:8080",
 *         description="Live Server"
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