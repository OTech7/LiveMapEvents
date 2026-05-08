<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manages a specific user's interests from the admin panel.
 *
 * Routes (defined before the generic {admin_resource} catch-alls in admin.php):
 *   GET /api/admin/v1/users/{user}/interests   → index()
 *   PUT /api/admin/v1/users/{user}/interests   → sync()
 *
 * Both actions re-use the existing `users.view` / `users.update` permissions
 * so no new permissions need to be seeded.
 */
class UserInterestsController extends Controller
{
    /**
     * GET /api/admin/v1/users/{user}/interests
     *
     * Returns the user's current interests, ordered by name.
     */
    public function index(User $user): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('users.view'),
            Response::HTTP_FORBIDDEN,
            'Missing permission: users.view'
        );

        $interests = $user->interests()
            ->orderBy('name')
            ->get(['interests.id', 'interests.name', 'interests.slug']);

        return ApiResponse::success(data: $interests);
    }

    /**
     * PUT /api/admin/v1/users/{user}/interests
     *
     * Replaces the user's entire interest set atomically.
     * Body: { "interest_ids": [1, 3, 7] }  — empty array clears all interests.
     */
    public function sync(Request $request, User $user): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('users.update'),
            Response::HTTP_FORBIDDEN,
            'Missing permission: users.update'
        );

        $validated = $request->validate([
            'interest_ids' => 'present|array',
            'interest_ids.*' => 'integer|exists:interests,id',
        ]);

        $user->interests()->sync($validated['interest_ids']);

        // Return the refreshed list so the frontend can update in place.
        $interests = $user->interests()
            ->orderBy('name')
            ->get(['interests.id', 'interests.name', 'interests.slug']);

        return ApiResponse::success('messages.interests_updated_successfully', $interests);
    }
}
