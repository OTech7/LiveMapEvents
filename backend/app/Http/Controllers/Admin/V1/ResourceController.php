<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Modules\Admin\AdminResource;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic CRUD controller for admin resources.
 *
 * The {admin_resource} route parameter is resolved by Route::bind() in
 * routes/admin.php — by the time we get here, $admin_resource is already an
 * AdminResource instance (or 404 has been thrown).
 *
 * Per-action authorisation happens here rather than as middleware on the
 * route, because the required permission depends on the bound resource
 * (users.view vs interests.view, etc.). Doing it in the controller keeps
 * routes/admin.php static and `route:cache`-friendly.
 */
class ResourceController extends Controller
{
    /** GET /api/admin/v1/{admin_resource}/schema */
    public function schema(AdminResource $admin_resource)
    {
        // Schema is reachable to anyone with at least .view OR any panel role.
        // The sidebar needs to know what resources exist to render correctly.
        $u = auth()->user();
        $perm = $admin_resource->permission();
        abort_unless(
            $u && (
                $u->hasAnyRole(['admin', 'super_admin', 'editor', 'viewer'])
                || $u->can("{$perm}.view")
            ),
            Response::HTTP_FORBIDDEN
        );

        return ApiResponse::success(data: $admin_resource->schema());
    }

    /** GET /api/admin/v1/{admin_resource} */
    public function index(Request $request, AdminResource $admin_resource)
    {
        $this->authorizeAction($admin_resource, 'view');

        $perPage = max(1, min((int)$request->query('per_page', 25), 100));
        $q = trim((string)$request->query('q', ''));
        $sort = (string)$request->query('sort', $admin_resource->defaultSort());

        $query = $admin_resource->query($request);

        $searchable = $admin_resource->searchable();
        if ($q !== '' && !empty($searchable)) {
            $query->where(function ($w) use ($searchable, $q) {
                foreach ($searchable as $i => $col) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $w->{$method}($col, 'ilike', "%{$q}%");
                }
            });
        }

        // Resolve sort — fall back to default if the requested column isn't allowed.
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        if (!in_array($column, $admin_resource->sortable(), true)) {
            $default = $admin_resource->defaultSort();
            $direction = str_starts_with($default, '-') ? 'desc' : 'asc';
            $column = ltrim($default, '-');
        }
        $query->orderBy($column, $direction);

        $page = $query->paginate($perPage);

        $items = collect($page->items())
            ->map(fn($m) => $admin_resource->transform($m))
            ->all();

        return ApiResponse::success(data: [
            'items' => $items,
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'total_pages' => $page->lastPage(),
            ],
        ]);
    }

    /** GET /api/admin/v1/{admin_resource}/{key} */
    public function show(Request $request, AdminResource $admin_resource, $key)
    {
        $this->authorizeAction($admin_resource, 'view');

        $model = $this->find($admin_resource, $key);
        return ApiResponse::success(data: $admin_resource->transform($model));
    }

    /** POST /api/admin/v1/{admin_resource} */
    public function store(Request $request, AdminResource $admin_resource)
    {
        $this->authorizeAction($admin_resource, 'create');

        if (!$admin_resource->canCreate()) {
            return ApiResponse::error('messages.forbidden', null, Response::HTTP_FORBIDDEN);
        }

        $cls = $admin_resource->model();
        $model = new $cls;

        $data = $request->validate($admin_resource->rules($request, null));
        $data = $admin_resource->beforeSave($model, $data, $request);

        $model->fill($data)->save();
        $admin_resource->afterSave($model, $data, $request);

        if (!empty($admin_resource->with())) {
            $model->load($admin_resource->with());
        }

        return ApiResponse::success(
            data: $admin_resource->transform($model),
            status: Response::HTTP_CREATED
        );
    }

    /** PUT /api/admin/v1/{admin_resource}/{key} */
    public function update(Request $request, AdminResource $admin_resource, $key)
    {
        $this->authorizeAction($admin_resource, 'update');

        $model = $this->find($admin_resource, $key);

        $data = $request->validate($admin_resource->rules($request, $model));
        $data = $admin_resource->beforeSave($model, $data, $request);

        $model->fill($data)->save();
        $admin_resource->afterSave($model, $data, $request);

        if (!empty($admin_resource->with())) {
            $model->load($admin_resource->with());
        }

        return ApiResponse::success(data: $admin_resource->transform($model));
    }

    /** DELETE /api/admin/v1/{admin_resource}/{key} */
    public function destroy(Request $request, AdminResource $admin_resource, $key)
    {
        $this->authorizeAction($admin_resource, 'delete');

        if (!$admin_resource->canDelete()) {
            return ApiResponse::error('messages.forbidden', null, Response::HTTP_FORBIDDEN);
        }

        $model = $this->find($admin_resource, $key);
        $admin_resource->beforeDelete($model, $request);
        $model->delete();

        return ApiResponse::success('messages.deleted');
    }

    /**
     * Locate a model by the resource's routeKey() (id, slug, …).
     * Eager-loads any relations the resource declared in with().
     */
    protected function find(AdminResource $admin_resource, $key)
    {
        $cls = $admin_resource->model();
        try {
            return $cls::with($admin_resource->with())
                ->where($admin_resource->routeKey(), $key)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(Response::HTTP_NOT_FOUND, 'Not found');
        }
    }

    /**
     * 403 the request if the caller lacks the resource's per-action permission.
     * Equivalent to slapping `permission:<base>.<action>` middleware on the
     * route, but works with our static / cacheable route file.
     */
    protected function authorizeAction(AdminResource $admin_resource, string $action): void
    {
        $u = auth()->user();
        $perm = $admin_resource->permission() . '.' . $action;
        abort_unless($u && $u->can($perm), Response::HTTP_FORBIDDEN, "Missing permission: {$perm}");
    }
}
