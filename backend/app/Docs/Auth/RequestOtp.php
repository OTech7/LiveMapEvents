<?php

namespace App\Docs\Auth;
use OpenApi\Annotations as OA;
/**
 * @OA\Post(
 *     path="/api/v1/auth/phone/request-otp",
 *     summary="Send OTP",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"phone"},
 *             @OA\Property(property="phone", type="string", example="+31612345678")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="OTP sent",
 *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
 *     )
 * )
 */
class RequestOtp {}