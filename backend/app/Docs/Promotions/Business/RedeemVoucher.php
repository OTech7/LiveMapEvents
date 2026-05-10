<?php

namespace App\Docs\Promotions\Business;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/api/v1/business/scanner/redeem",
 *     summary="Redeem a customer voucher by QR code",
 *     description="Called by the business owner's scanner page. Returns valid:true on success or valid:false with a reason.",
 *     tags={"Business / Scanner"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"voucher_code"},
 *             @OA\Property(property="voucher_code", type="string", example="ABCD1234")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Voucher redeemed successfully",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/PromotionClaim"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=404, description="Invalid voucher code"),
 *     @OA\Response(response=422, description="Already redeemed or expired"),
 *     @OA\Response(response=403, description="Wrong venue")
 * )
 */
class RedeemVoucher
{
}
