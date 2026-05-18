<?php

namespace App\Docs\Profile;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/v1/profile/avatar",
 *     summary="Upload profile avatar",
 *     tags={"Profile"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"avatar"},
 *                 @OA\Property(
 *                     property="avatar",
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Avatar uploaded successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="avatar_url", type="string")
 *                     )
 *                 )
 *             }
 *         )
 *     )
 * )
 */
class UploadAvatar {}