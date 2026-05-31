<?php

namespace App\Docs\Venues\Business;

/**
 * @OA\Post(
 *     path="/api/v1/business/venues",
 *     summary="Create a new venue",
 *     tags={"Business / Venues"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name","type"},
 *             @OA\Property(property="name", type="string", maxLength=120, example="The Rooftop Bar"),
 *             @OA\Property(property="type", type="string", maxLength=60, example="bar"),
 *             @OA\Property(property="address", type="string", nullable=true, maxLength=255, example="10 Straight Street"),
 *             @OA\Property(property="city", type="string", nullable=true, maxLength=100, example="Damascus"),
 *             @OA\Property(property="notes", type="string", nullable=true, maxLength=2000, example="Back entrance only. Parking available behind the building."),
 *             @OA\Property(property="lat", type="number", format="float", nullable=true, example=33.5138),
 *             @OA\Property(property="lng", type="number", format="float", nullable=true, example=36.2765)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Venue created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Venue"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
class CreateVenue
{
}
