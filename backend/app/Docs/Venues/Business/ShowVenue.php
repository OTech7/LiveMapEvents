<?php

namespace App\Docs\Venues\Business;

/**
 * @OA\Get(
 *     path="/api/v1/business/venues/{venue}",
 *     summary="Get a single venue",
 *     tags={"Business / Venues"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="venue",
 *         in="path",
 *         required=true,
 *         description="Venue ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Venue fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Venue"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden — not the venue owner"),
 *     @OA\Response(response=404, description="Venue not found")
 * )
 */
class ShowVenue
{
}
