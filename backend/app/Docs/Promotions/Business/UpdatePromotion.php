<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Put(
 *     path="/api/v1/business/promotions/{promotion}",
 *     summary="Update a promotion",
 *     tags={"Business / Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(name="promotion", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="title", type="string"),
 *             @OA\Property(property="discount_type", type="string", enum={"percentage","fixed"}),
 *             @OA\Property(property="discount_value", type="number"),
 *             @OA\Property(property="is_active", type="boolean")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Promotion updated successfully",
 *         @OA\JsonContent(allOf={@OA\Schema(ref="#/components/schemas/ApiResponse"), @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Promotion"))})
 *     ),
 *     @OA\Response(response=403, description="Not your promotion")
 * )
 */
class UpdatePromotion
{
}
