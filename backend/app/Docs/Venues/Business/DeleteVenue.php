<?php

namespace App\Docs\Venues\Business;

/**
 * @OA\Delete(
 *     path="/api/v1/business/venues/{venue}",
 *     summary="Delete a venue",
 *     description="Deletes the venue permanently. Blocked if the venue has upcoming published events — cancel or delete those events first.",
 *     tags={"Business / Venues"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="venue",
 *         in="path",
 *         required=true,
 *         description="Venue ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Venue deleted successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Forbidden — not the venue owner"),
 *     @OA\Response(response=404, description="Venue not found"),
 *     @OA\Response(response=422, description="Venue has upcoming published events and cannot be deleted")
 * )
 */
class DeleteVenue
{
}
