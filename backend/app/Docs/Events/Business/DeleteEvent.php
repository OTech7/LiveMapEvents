<?php

namespace App\Docs\Events\Business;

/**
 * @OA\Delete(
 *     path="/api/v1/business/events/{event}",
 *     summary="Delete an event",
 *     tags={"Business / Events"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="event",
 *         in="path",
 *         required=true,
 *         description="Event ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Event deleted successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden — not the venue owner"),
 *     @OA\Response(response=404, description="Event not found")
 * )
 */
class DeleteEvent
{
}
