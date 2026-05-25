<?php

namespace App\Docs\Events\Business;

/**
 * @OA\Get(
 *     path="/api/v1/business/events/{event}",
 *     summary="Get a single event",
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
 *         description="Event fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Event"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden — not the venue owner"),
 *     @OA\Response(response=404, description="Event not found")
 * )
 */
class ShowEvent
{
}
