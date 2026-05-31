<?php

namespace App\Docs\Venues\Business;

/**
 * @OA\Put(
 *     path="/api/v1/business/venues/{venue}",
 *     summary="Update a venue",
 *     description="All fields are optional — only the fields you send will be updated.",
 *     tags={"Business / Venues"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="venue",
 *         in="path",
 *         required=true,
 *         description="Venue ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", maxLength=120, example="The Rooftop Bar — Renovated"),
 *             @OA\Property(property="type", type="string", maxLength=60, example="club"),
 *             @OA\Property(property="address", type="string", nullable=true, maxLength=255, example="12 New Street"),
 *             @OA\Property(property="city", type="string", nullable=true, maxLength=100, example="Aleppo"),
 *             @OA\Property(property="notes", type="string", nullable=true, maxLength=2000, example="Now open until 2am on weekends."),
 *             @OA\Property(property="lat", type="number", format="float", nullable=true, example=36.2021),
 *             @OA\Property(property="lng", type="number", format="float", nullable=true, example=37.1343)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Venue updated successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Venue"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden — not the venue owner"),
 *     @OA\Response(response=404, description="Venue not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
class UpdateVenue
{
}
