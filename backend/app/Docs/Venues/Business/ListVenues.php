<?php

namespace App\Docs\Venues\Business;

/**
 * @OA\Get(
 *     path="/api/v1/business/venues",
 *     summary="List all venues owned by the authenticated user",
 *     tags={"Business / Venues"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Venues fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Venue")),
 *                         @OA\Property(property="current_page", type="integer", example=1),
 *                         @OA\Property(property="total", type="integer", example=3)
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
class ListVenues
{
}
