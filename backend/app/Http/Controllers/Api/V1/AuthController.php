<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OtpVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\OTPService;
use App\Support\ApiResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(protected OTPService $otpService,protected AuthService $authService) {}

    public function requestOtp(RequestOtpRequest $request)
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

        return ApiResponse::success(message: 'messages.otp_sent',status: Response::HTTP_OK);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $result = $this->otpService->verify($request->string('phone')->toString(),$request->string('otp')->toString());

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
                ],
                status: Response::HTTP_OK
            );
        }

        return match ($result->status) {
            OtpVerificationStatus::INVALID => ApiResponse::error(
                message: 'messages.invalid_otp',
                errors: [
                    'status' => $result->status->value,
                    'remaining_attempts' => $result->remainingAttempts,
                ],
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            ),

            OtpVerificationStatus::EXPIRED => ApiResponse::error(
                message: 'messages.otp_expired',
                errors: [
                    'status' => $result->status->value,
                ],
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            ),

            OtpVerificationStatus::MAX_ATTEMPTS_REACHED => ApiResponse::error(
                message: 'messages.otp_max_attempts_reached',
                errors: [
                    'status' => $result->status->value,
                    'remaining_attempts' => 0,
                ],
                status: Response::HTTP_TOO_MANY_REQUESTS
            ),
        };
    }
    
    public function googleLogin(GoogleLoginRequest $request)
    {
        $result = $this->authService->loginWithGoogle($request->id_token);

        return ApiResponse::success('', data: [
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
            'profile_complete' => $result['profile_complete'],
        ]);
    }

    public function me()
    {
        return ApiResponse::success(data: UserResource::make(auth()->user()));
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return ApiResponse::success('messages.logout_success');
    }
}   
