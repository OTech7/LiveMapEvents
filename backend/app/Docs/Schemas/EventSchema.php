<?php

namespace App\Docs\Schemas;

/**
 * @OA\Schema(
 *     schema="Event",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(
 *         property="venue",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="WeWork Bryant Park"),
 *         @OA\Property(property="type", type="string", example="coworking"),
 *         @OA\Property(property="address", type="string", example="25 W 39th St"),
 *         @OA\Property(property="city", type="string", example="New York"),
 *         @OA\Property(property="lat", type="number", format="float", nullable=true, example=40.7538),
 *         @OA\Property(property="lng", type="number", format="float", nullable=true, example=-73.984)
 *     ),
 *     @OA\Property(property="title", type="string", example="Monthly Python Meetup"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Join us for our monthly Python meetup!"),
 *     @OA\Property(property="category", type="string", nullable=true, example="Technology"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/image.jpg"),
 *     @OA\Property(property="starts_at", type="string", format="date-time", example="2026-06-15T18:00:00+00:00"),
 *     @OA\Property(property="ends_at", type="string", format="date-time", example="2026-06-15T21:00:00+00:00"),
 *     @OA\Property(property="is_online_event", type="boolean", example=false),
 *     @OA\Property(property="online_event_url", type="string", nullable=true, example="https://zoom.us/j/abc123"),
 *     @OA\Property(property="is_free", type="boolean", example=true),
 *     @OA\Property(property="rsvp_limit", type="integer", nullable=true, example=100),
 *     @OA\Property(property="guest_limit", type="integer", example=0),
 *     @OA\Property(property="publish_status", type="string", enum={"published","draft","cancelled"}, example="published"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class EventSchema
{
}
