<?php

namespace App\Docs\Events\Business;

/**
 * @OA\Get(
 *     path="/api/v1/business/events",
 *     summary="List all events for the authenticated business owner",
 *     tags={"Business / Events"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="venue_id",
 *         in="query",
 *         required=false,
 *         description="Filter events by venue ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Events fetched successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(
 *                     @OA\Property(
 *                         property="data",
 *                         type="object",
 *                         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Event")),
 *                         @OA\Property(property="current_page", type="integer"),
 *                         @OA\Property(property="total", type="integer")
 *                     )
 *                 )
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 */
class ListEvents
{
}
