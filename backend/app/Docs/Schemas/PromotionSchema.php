<?php

namespace App\Docs\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Promotion",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Morning Coffee Deal"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="discount_type", type="string", enum={"percentage","fixed"}, example="percentage"),
 *     @OA\Property(property="discount_value", type="number", format="float", example=10.00),
 *     @OA\Property(property="recurrence_type", type="string", enum={"one_time","recurring"}, example="recurring"),
 *     @OA\Property(property="days_of_week", type="array", nullable=true, @OA\Items(type="integer"), example={1,5}),
 *     @OA\Property(property="start_time", type="string", example="10:00:00"),
 *     @OA\Property(property="end_time", type="string", example="12:00:00"),
 *     @OA\Property(property="valid_from", type="string", format="date", example="2026-05-10"),
 *     @OA\Property(property="valid_to", type="string", format="date", nullable=true),
 *     @OA\Property(property="max_total_redemptions", type="integer", nullable=true),
 *     @OA\Property(property="max_per_user_redemptions", type="integer", example=1),
 *     @OA\Property(property="terms", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="status", type="string", nullable=true, enum={"active","upcoming"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class PromotionSchema
{
}
