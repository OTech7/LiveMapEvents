<?php

namespace App\Modules\Admin;

/**
 * Registry of all admin panel resources.
 *
 * Adding a new resource = subclass AdminResource and add the FQCN here.
 * routes/admin.php uses Route::bind('admin_resource') → find($value), so
 * any slug returned by a resource's route() becomes a working URL.
 */
final class AdminResources
{
    /** Single source of truth — order here = order in the sidebar. */
    public const RESOURCES = [
        \App\Modules\Admin\Resources\UserResource::class,
        \App\Modules\Admin\Resources\InterestResource::class,
    ];

    /** Instantiate every resource. */
    public static function all(): array
    {
        return array_map(fn($cls) => app($cls), self::RESOURCES);
    }

    /** Find a resource by its URL slug, or null. */
    public static function find(string $route): ?AdminResource
    {
        foreach (self::all() as $r) {
            if ($r->route() === $route) {
                return $r;
            }
        }
        return null;
    }

    /**
     * For /me — describe what the calling user can see/do per resource.
     * Powers the sidebar (only show links the user has .view on) and the
     * AutoForm (hide buttons the user can't trigger).
     */
    public static function accessibleTo($user): array
    {
        $out = [];
        foreach (self::all() as $r) {
            $perm = $r->permission();
            $out[] = [
                'route' => $r->route(),
                'label' => $r->label(),
                'label_plural' => $r->labelPlural(),
                'permissions' => [
                    'view' => $user?->can("{$perm}.view") ?? false,
                    'create' => $user?->can("{$perm}.create") ?? false,
                    'update' => $user?->can("{$perm}.update") ?? false,
                    'delete' => $user?->can("{$perm}.delete") ?? false,
                ],
            ];
        }
        return $out;
    }
}
