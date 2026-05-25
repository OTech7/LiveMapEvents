<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Modules\Admin\AdminResource;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        try {
            $model->fill($data)->save();
        } catch (QueryException $e) {
            Log::error('admin_resource_create_db_error', [
                'resource' => $admin_resource->permission(),
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);
            return ApiResponse::error($this->friendlyDbError($e), null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $admin_resource->afterSave($model, $data, $request);

        Log::info('admin_resource_created', [
            'resource' => $admin_resource->permission(),
            'id' => $model->getKey(),
            'admin_id' => auth()->id(),
        ]);

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

        try {
            $model->fill($data)->save();
        } catch (QueryException $e) {
            Log::error('admin_resource_update_db_error', [
                'resource' => $admin_resource->permission(),
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);
            return ApiResponse::error($this->friendlyDbError($e), null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $admin_resource->afterSave($model, $data, $request);

        Log::info('admin_resource_updated', [
            'resource' => $admin_resource->permission(),
            'id' => $model->getKey(),
            'admin_id' => auth()->id(),
        ]);

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

        Log::info('admin_resource_deleted', [
            'resource' => $admin_resource->permission(),
            'id' => $model->getKey(),
            'admin_id' => auth()->id(),
        ]);

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

    /**
     * Convert a raw QueryException into a human-readable message.
     * Avoids leaking SQLSTATE details to the API consumer while still giving
     * admins enough context to understand what went wrong.
     */
    protected function friendlyDbError(QueryException $e): string
    {
        $msg = $e->getMessage();

        // PostgreSQL / MySQL not-null violation
        if (str_contains($msg, 'null value in column') || str_contains($msg, 'cannot be null')) {
            if (preg_match('/null value in column "([^"]+)"/', $msg, $m)) {
                return "The field \"{$m[1]}\" is required and cannot be empty.";
            }
            return 'A required field is missing. Please fill in all required fields.';
        }

        // Unique constraint violation
        if (str_contains($msg, 'unique constraint') || str_contains($msg, 'Duplicate entry')) {
            if (preg_match('/unique constraint "([^"]+)"/', $msg, $m)) {
                return "A record with this value already exists (constraint: {$m[1]}).";
            }
            return 'A record with this value already exists.';
        }

        // Foreign key violation
        if (str_contains($msg, 'foreign key constraint') || str_contains($msg, 'a foreign key constraint fails')) {
            return 'The referenced record does not exist. Please check the selected values.';
        }

        // Generic fallback — log has the full detail, user gets a safe message
        return 'A database error occurred. Please check your input and try again.';
    }
}
