<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Delete(
 *     path="/api/v1/business/promotions/{promotion}",
 *     summary="Delete (soft) a promotion",
 *     tags={"Business / Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Promotion deleted successfully", @OA\JsonContent(ref="#/components/schemas/ApiResponse")),
 *     @OA\Response(response=403, description="Not your promotion")
 * )
 */
class DeletePromotion
{
}
