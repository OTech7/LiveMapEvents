<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Support\ApiResponse;

class AuthController extends Controller
{
    /**
     * Identity endpoint for the admin panel. Returns the authenticated
     * user plus their roles/permissions so the panel can decide what UI
     * to show. Does NOT require the `admin` role — we want to be able
     * to display "logged in but not authorized" cleanly.
     */
    public function me()
    {
        $user = auth()->user();

        return ApiResponse::success(
            data: [
                'user' => new AdminUserResource($user),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'is_admin' => $user->hasRole('admin'),
            ]
        );
    }

    public function logout()
    {
        auth()->user()?->currentAccessToken()?->delete();

        return ApiResponse::success('messages.logout_success');
    }
}
