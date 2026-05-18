<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/v1/business/promotions",
 *     summary="Create a new promotion",
 *     tags={"Business / Promotions"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"venue_id","title","discount_type","discount_value","recurrence_type","start_time","end_time","valid_from"},
 *             @OA\Property(property="venue_id", type="integer", example=1),
 *             @OA\Property(property="title", type="string", example="Morning Coffee Deal"),
 *             @OA\Property(property="description", type="string", nullable=true),
 *             @OA\Property(property="discount_type", type="string", enum={"percentage","fixed"}, example="percentage"),
 *             @OA\Property(property="discount_value", type="number", example=10),
 *             @OA\Property(property="recurrence_type", type="string", enum={"one_time","recurring"}, example="recurring"),
 *             @OA\Property(property="days_of_week", type="array", @OA\Items(type="integer"), example={1,5}),
 *             @OA\Property(property="start_time", type="string", example="10:00"),
 *             @OA\Property(property="end_time", type="string", example="12:00"),
 *             @OA\Property(property="valid_from", type="string", format="date", example="2026-05-10"),
 *             @OA\Property(property="valid_to", type="string", format="date", nullable=true),
 *             @OA\Property(property="max_total_redemptions", type="integer", nullable=true),
 *             @OA\Property(property="max_per_user_redemptions", type="integer", example=1),
 *             @OA\Property(property="terms", type="string", nullable=true),
 *             @OA\Property(property="is_active", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Promotion created successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Promotion"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
class CreatePromotion
{
}
