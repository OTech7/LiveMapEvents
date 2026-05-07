<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * GET /api/admin/v1/users
     *
     * Query params:
     *   ?q=          search across phone / first_name / last_name
     *   ?per_page=   page size (default 25, max 100)
     *   ?page=
     *   ?sort=       e.g. `-created_at` (descending) or `first_name`
     */
    public function index(Request $request)
    {
        $perPage = min((int)$request->query('per_page', 25), 100);
        $q = trim((string)$request->query('q', ''));
        $sort = (string)$request->query('sort', '-created_at');

        $query = User::query()->with('roles');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('phone', 'ilike', "%{$q}%")
                    ->orWhere('first_name', 'ilike', "%{$q}%")
                    ->orWhere('last_name', 'ilike', "%{$q}%");
            });
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['id', 'created_at', 'updated_at', 'first_name', 'last_name', 'phone'];
        if (!in_array($column, $allowed, true)) {
            $column = 'created_at';
            $direction = 'desc';
        }
        $query->orderBy($column, $direction);

        $page = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            data: [
                'items' => AdminUserResource::collection($page->items()),
                'meta' => [
                    'page' => $page->currentPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'total_pages' => $page->lastPage(),
                ],
            ]
        );
    }

    public function show(User $user)
    {
        $user->load('roles', 'interests');

        return ApiResponse::success(data: new AdminUserResource($user));
    }

    public function update(Request $request, User $user)
    {
        // Validation rules align with the underlying DB constraints in
        // 0001_01_01_000000_create_users_table.php — gender + user_type
        // are enums, phone is unique. Don't widen these without first
        // updating the migration / DB enum.
        $data = $request->validate([
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'phone' => ['sometimes', 'nullable', 'string', 'max:32',
                \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($user->id)],
            'gender' => 'sometimes|nullable|in:male,female',
            'dob' => 'sometimes|nullable|date',
            'user_type' => 'sometimes|nullable|in:attendee,business,admin',
            'profile_complete' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        $user->fill($data)->save();

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        $user->load('roles');

        return ApiResponse::success(
            message: 'messages.success',
            data: new AdminUserResource($user)
        );
    }

    public function destroy(User $user)
    {
        // Self-protection — don't let an admin delete their own account
        // through the panel; that's a great way to lock yourself out.
        if (auth()->id() === $user->id) {
            return ApiResponse::error(
                message: 'messages.cannot_delete_self',
                status: 422
            );
        }

        $user->delete();

        return ApiResponse::success('messages.deleted');
    }
}
