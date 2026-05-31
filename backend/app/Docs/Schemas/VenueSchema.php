<?php

namespace App\Docs\Schemas;

/**
 * @OA\Schema(
 *     schema="Venue",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="The Rooftop Bar"),
 *     @OA\Property(property="type", type="string", example="bar"),
 *     @OA\Property(property="address", type="string", nullable=true, example="10 Straight Street"),
 *     @OA\Property(property="city", type="string", nullable=true, example="Damascus"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Back entrance only. Parking available."),
 *     @OA\Property(property="lat", type="number", format="float", nullable=true, example=33.5138),
 *     @OA\Property(property="lng", type="number", format="float", nullable=true, example=36.2765),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="is_verified", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-05-26T10:00:00+00:00"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-05-26T10:00:00+00:00")
 * )
 */
class VenueSchema
{
}
