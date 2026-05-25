<?php

namespace App\Docs\Events\Business;

/**
 * @OA\Put(
 *     path="/api/v1/business/events/{event}",
 *     summary="Update an event",
 *     tags={"Business / Events"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="event",
 *         in="path",
 *         required=true,
 *         description="Event ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string", maxLength=80, example="Updated Meetup Title"),
 *             @OA\Property(property="description", type="string", nullable=true),
 *             @OA\Property(property="category", type="string", nullable=true, example="Technology"),
 *             @OA\Property(property="image_url", type="string", nullable=true),
 *             @OA\Property(property="starts_at", type="string", format="date-time", example="2026-06-20T18:00:00Z"),
 *             @OA\Property(property="ends_at", type="string", format="date-time", nullable=true),
 *             @OA\Property(property="is_online_event", type="boolean"),
 *             @OA\Property(property="online_event_url", type="string", nullable=true),
 *             @OA\Property(property="rsvp_limit", type="integer", nullable=true, example=50),
 *             @OA\Property(property="guest_limit", type="integer", nullable=true),
 *             @OA\Property(property="publish_status", type="string", enum={"published","draft"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Event updated successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Event"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden — not the venue owner"),
 *     @OA\Response(response=404, description="Event not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
class UpdateEvent
{
}
