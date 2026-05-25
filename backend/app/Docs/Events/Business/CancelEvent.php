<?php

namespace App\Docs\Events\Business;

/**
 * @OA\Post(
 *     path="/api/v1/business/events/{event}/cancel",
 *     summary="Cancel an event",
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
 *         required=false,
 *         @OA\JsonContent(
 *             @OA\Property(property="reason", type="string", nullable=true, maxLength=500, example="Venue unavailable due to flooding")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Event cancelled successfully",
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
 *     @OA\Response(response=409, description="Event is already cancelled")
 * )
 */
class CancelEvent
{
}
