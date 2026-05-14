<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OtpVerificationResult;
use App\Enums\OtpVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\OTPService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        protected OTPService  $otpService,
        protected AuthService $authService
    )
    {
    }

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        try {
            $this->otpService->send($request->string('phone')->toString());
        } catch (RuntimeException $exception) {
            report($exception);

            return ApiResponse::error(
                message: 'messages.otp_provider_unreachable',
                status: Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return ApiResponse::success(message: 'messages.otp_sent', status: Response::HTTP_OK);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->verify(
            $request->string('phone')->toString(),
            $request->string('otp')->toString()
        );

        if ($result->status === OtpVerificationStatus::VERIFIED) {
            $userData = $this->authService->loginWithPhone(
                $request->string('phone')->toString()
            );

            return ApiResponse::success(
                message: 'messages.otp_verified',
                data: [
                    'token' => $userData['token'],
                    'user' => new UserResource($userData['user']),
                    'profile_complete' => $userData['profile_complete'],
                    'interests_complete' => $userData['interests_complete'],
                    'discovery_settings_complete' => $userData['discovery_settings_complete'],
                ],
                status: Response::HTTP_OK
            );
        }

        return $this->otpErrorResponse($result);
    }

    public function googleLogin(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginWithGoogle($request->id_token);

        return ApiResponse::success(
            message: 'messages.login_success',
            data: [
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
                'profile_complete' => $result['profile_complete'],
                'interests_complete' => $result['interests_complete'],
                'discovery_settings_complete' => $result['discovery_settings_complete'],
            ]
        );
    }

    public function me(): JsonResponse
    {
        return ApiResponse::success(data: UserResource::make(auth()->user()));
    }

    public function logout(): JsonResponse
    {
        auth()->user()?->currentAccessToken()?->delete();

        return ApiResponse::success('messages.logout_success');
    }

    private function otpErrorResponse(OtpVerificationResult $result): JsonResponse
    {
        return match ($result->status) {
            OtpVerificationStatus::INVALID => ApiResponse::error(
                message: 'messages.invalid_otp',
                errors: [
                    'status' => $result->status->value,
                    'remaining_attempts' => $result->remainingAttempts,
                ],
                status: 422
            ),

            OtpVerificationStatus::EXPIRED => ApiResponse::error(
                message: 'messages.otp_expired',
                errors: [
                    'status' => $result->status->value,
                ],
                status: 422
            ),

            OtpVerificationStatus::MAX_ATTEMPTS_REACHED => ApiResponse::error(
                message: 'messages.otp_max_attempts_reached',
                errors: [
                    'status' => $result->status->value,
                    'remaining_attempts' => 0,
                ],
                status: 429
            ),
        };
    }
}
