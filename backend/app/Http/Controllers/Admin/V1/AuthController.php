<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Modules\Admin\AdminResources;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * Identity endpoint for the admin panel. Returns:
     *   - the authenticated user
     *   - their roles + permissions
     *   - is_admin (for the legacy "logged in but not admin" page)
     *   - has_panel_access (admin / super_admin / editor / viewer)
     *   - resources[] — what the user can see/do per resource, used by the
     *     sidebar to render only accessible links and the AutoForm to hide
     *     buttons the user can't trigger.
     *
     * Reachable by any authenticated user — does NOT require an admin role.
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();

        $panelRoles = ['admin', 'super_admin', 'editor', 'viewer'];

        return ApiResponse::success(
            data: [
                'user' => new AdminUserResource($user),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'is_admin' => $user->hasRole('admin'),
                'has_panel_access' => $user->hasAnyRole($panelRoles),
                'resources' => AdminResources::accessibleTo($user),
            ]
        );
    }

    public function logout(): JsonResponse
    {
        auth()->user()?->currentAccessToken()?->delete();

        return ApiResponse::success('messages.logout_success');
    }
}
