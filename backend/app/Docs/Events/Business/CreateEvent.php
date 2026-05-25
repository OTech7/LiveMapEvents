<?php

namespace App\Docs\Events\Business;

/**
 * @OA\Post(
 *     path="/api/v1/business/events",
 *     summary="Create a new event",
 *     tags={"Business / Events"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"venue_id","title","starts_at"},
 *             @OA\Property(property="venue_id", type="integer", example=1),
 *             @OA\Property(property="title", type="string", maxLength=80, example="Monthly Python Meetup"),
 *             @OA\Property(property="description", type="string", nullable=true, example="Join us for our monthly Python meetup!"),
 *             @OA\Property(property="category", type="string", nullable=true, example="Technology"),
 *             @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/image.jpg"),
 *             @OA\Property(property="starts_at", type="string", format="date-time", example="2026-06-15T18:00:00Z"),
 *             @OA\Property(property="ends_at", type="string", format="date-time", nullable=true, example="2026-06-15T21:00:00Z"),
 *             @OA\Property(property="is_online_event", type="boolean", example=false),
 *             @OA\Property(property="online_event_url", type="string", nullable=true, example="https://zoom.us/j/abc123"),
 *             @OA\Property(property="rsvp_limit", type="integer", nullable=true, example=100),
 *             @OA\Property(property="guest_limit", type="integer", nullable=true, example=0),
 *             @OA\Property(property="publish_status", type="string", enum={"published","draft"}, example="published")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Event created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Event"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Venue does not belong to authenticated user"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
class CreateEvent
{
}
