<?php

namespace App\Modules\Admin\Resources;

use App\Http\Resources\Admin\AdminUserResource;
use App\Models\Interest;
use App\Models\User;
use App\Modules\Admin\AdminResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserResource extends AdminResource
{
    public function model(): string
    {
        return User::class;
    }

    public function route(): string
    {
        return 'users';
    }

    public function permission(): string
    {
        return 'users';
    }

    public function listColumns(): array
    {
        return ['id', 'phone', 'first_name', 'last_name', 'user_type', 'profile_complete', 'roles', 'created_at'];
    }

    public function searchable(): array
    {
        return ['phone', 'first_name', 'last_name'];
    }

    public function sortable(): array
    {
        return ['id', 'created_at', 'updated_at', 'first_name', 'last_name', 'phone'];
    }

    public function defaultSort(): string
    {
        return '-created_at';
    }

    public function with(): array
    {
        return ['roles', 'interests'];
    }

    public function fields(): array
    {
        // Interests are dynamic — query every time so the chip picker
        // immediately reflects edits from the /admin/interests page.
        // The interests table is small enough that a fresh fetch per
        // schema call is cheap.
        $interestOptions = Interest::orderBy('name')->get()
            ->map(fn($i) => ['value' => (string)$i->id, 'label' => $i->name])
            ->all();

        return [
            ['name' => 'first_name', 'label' => 'First name', 'type' => 'text'],
            ['name' => 'last_name', 'label' => 'Last name', 'type' => 'text'],
            // Phone is the user's auth identity (OTP login uses it). Showing
            // it for context, but the admin must NOT be able to change it
            // from the panel — see rules() / beforeSave() which strip phone
            // even if a request body tries to set it.
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'readonly' => true,
                'helperText' => 'Read-only — phone is the user\'s auth identity. To change it, the user must re-verify via OTP.'],
            ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => [
                ['value' => 'male', 'label' => 'male'],
                ['value' => 'female', 'label' => 'female'],
            ]],
            ['name' => 'dob', 'label' => 'Date of birth', 'type' => 'date'],
            ['name' => 'user_type', 'label' => 'User type', 'type' => 'select', 'options' => [
                ['value' => '', 'label' => '—'],
                ['value' => 'attendee', 'label' => 'attendee'],
                ['value' => 'business', 'label' => 'business'],
                ['value' => 'admin', 'label' => 'admin'],
            ]],
            ['name' => 'profile_complete', 'label' => 'Profile complete', 'type' => 'checkbox'],
            ['name' => 'roles', 'label' => 'Roles', 'type' => 'multi-select', 'options' => [
                ['value' => 'admin', 'label' => 'admin'],
                ['value' => 'super_admin', 'label' => 'super_admin'],
                ['value' => 'editor', 'label' => 'editor'],
                ['value' => 'viewer', 'label' => 'viewer'],
            ], 'helperText' => 'Assigning admin grants access to this panel.'],
            ['name' => 'interests', 'label' => 'Interests', 'type' => 'tag-picker',
                'options' => $interestOptions,
                'helperText' => $interestOptions === []
                    ? 'No interests in the catalog yet — add some on the Interests page first.'
                    : 'Click × on a chip to remove. Use the dropdown to add a new one.'],
        ];
    }

    public function rules(Request $request, ?Model $existing = null): array
    {
        return [
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            // 'phone' is intentionally NOT in this list — admins can't
            // change it from the panel. beforeSave() strips it from $data
            // as defence-in-depth in case the field appears in the body.
            'gender' => 'sometimes|nullable|in:male,female',
            'dob' => 'sometimes|nullable|date',
            'user_type' => 'sometimes|nullable|in:attendee,business,admin',
            'profile_complete' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
            // Interests come back from the multi-select as string ids; Postgres
            // coerces them on the `exists` check.
            'interests' => 'sometimes|array',
            'interests.*' => 'string|exists:interests,id',
        ];
    }

    public function transform(Model $model): array
    {
        // Reuse the existing API resource shape so the panel keeps
        // working without changes.
        return (new AdminUserResource($model))->resolve();
    }

    public function beforeSave(Model $model, array $data, Request $request): array
    {
        // Strip:
        //  - roles + interests: pivot relations, handled in afterSave()
        //  - phone: read-only on the admin panel (auth identity); even if a
        //    request body somehow includes it, never write it via fill().
        unset($data['roles'], $data['interests'], $data['phone']);
        return $data;
    }

    public function afterSave(Model $model, array $data, Request $request): void
    {
        // Only sync when the client actually sent the field — PATCH-y updates
        // that omit a relation must not silently strip it.
        if ($request->has('roles')) {
            $roles = $request->input('roles', []);
            $model->syncRoles(is_array($roles) ? $roles : []);
        }

        if ($request->has('interests')) {
            $ids = collect($request->input('interests', []))
                ->filter(fn($v) => $v !== '' && $v !== null)
                ->map(fn($v) => (int)$v)
                ->unique()
                ->values()
                ->all();
            $model->interests()->sync($ids);
        }
    }

    public function beforeDelete(Model $model, Request $request): void
    {
        // Self-protection — same guard the bespoke UsersController had.
        if (auth()->id() === $model->id) {
            throw ValidationException::withMessages([
                'id' => __('messages.cannot_delete_self'),
            ]);
        }
    }

    public function canCreate(): bool
    {
        // Users are created through the OTP login flow, not through the panel.
        return false;
    }
}
