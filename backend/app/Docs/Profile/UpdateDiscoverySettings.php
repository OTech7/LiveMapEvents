<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Put(
 *     path="/api/v1/profile/discovery-settings",
 *     summary="Update discovery settings (radius & notifications)",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"radius"},
 *             example={
 *                 "radius": 400,
 *                 "notifications": true
 *             },
 *             @OA\Property(
 *                 property="radius",
 *                 type="integer",
 *                 minimum=100,
 *                 maximum=5000,
 *                 example=500,
 *                 description="Discovery radius in meters"
 *             ),
 *             @OA\Property(
 *                 property="notifications",
 *                 type="boolean",
 *                 example=true,
 *                 description="Enable nearby notifications"
 *             )
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=200,
 *         description="Discovery settings updated successfully",
 *         @OA\JsonContent(
 *             ref="#/components/schemas/ApiResponse"
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */
class UpdateDiscoverySettings {}