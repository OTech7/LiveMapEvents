<?php

namespace App\Modules\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Base class for admin-panel resources.
 *
 * One resource = one PHP file. The generic ResourceController uses these
 * methods to serve list / show / store / update / destroy / schema for any
 * model without bespoke controllers.
 *
 * Adding a new resource:
 *   1. Subclass AdminResource and override model() / route() / permission().
 *   2. Add the FQCN to AdminResources::RESOURCES.
 *   3. Re-run AdminRoleSeeder so the four CRUD permissions exist.
 *
 * The frontend AutoForm reads schema() to render inputs and validate.
 */
abstract class AdminResource
{
    /** Eloquent model class FQCN, e.g. App\Models\User::class */
    abstract public function model(): string;

    /** URL slug used under /api/admin/v1/, e.g. 'users' */
    abstract public function route(): string;

    /** Permission base, e.g. 'users' → users.view / users.create / users.update / users.delete */
    abstract public function permission(): string;

    /** Singular UI label */
    public function label(): string
    {
        return Str::headline(Str::singular($this->route()));
    }

    /** Plural UI label */
    public function labelPlural(): string
    {
        return Str::headline($this->route());
    }

    /** Route binding key (default 'id'; override to 'slug' etc.) */
    public function routeKey(): string
    {
        return 'id';
    }

    /** Columns shown in the list view (order matters). Format: ['id', 'phone', ...]. */
    public function listColumns(): array
    {
        return [];
    }

    /** Columns to LIKE-search across when ?q= is given. */
    public function searchable(): array
    {
        return [];
    }

    /** Columns the UI may sort by. */
    public function sortable(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    /** Default sort, "-col" for desc, "col" for asc. */
    public function defaultSort(): string
    {
        return '-id';
    }

    /**
     * Editable fields exposed to the AutoForm.
     * Each entry: ['name' => ..., 'label' => ..., 'type' => ..., 'options' => [...], ...]
     */
    public function fields(): array
    {
        return [];
    }

    /** Eager-loaded relations on show + list. */
    public function with(): array
    {
        return [];
    }

    /** Validation rules for store/update. $existing is null on store. */
    public function rules(Request $request, ?Model $existing = null): array
    {
        return [];
    }

    /** Base query for list/show. Override to add joins or scope. */
    public function query(Request $request): Builder
    {
        $cls = $this->model();
        $q = $cls::query();
        if (!empty($this->with())) {
            $q->with($this->with());
        }
        return $q;
    }

    /** Transform a single model for the API response. */
    public function transform(Model $model): array
    {
        return $model->toArray();
    }

    /** Hook: mutate $data before fill(). Strip non-column virtual fields here. */
    public function beforeSave(Model $model, array $data, Request $request): array
    {
        return $data;
    }

    /** Hook: after $model->save(). Use for related-data sync (e.g. roles). */
    public function afterSave(Model $model, array $data, Request $request): void
    {
    }

    /** Hook: throw to prevent deletion (e.g. self-delete guard). */
    public function beforeDelete(Model $model, Request $request): void
    {
    }

    /** Whether store is allowed at all (false → POST returns 403). */
    public function canCreate(): bool
    {
        return true;
    }

    /** Whether destroy is allowed at all. */
    public function canDelete(): bool
    {
        return true;
    }

    /** JSON schema for the panel's AutoForm. */
    public function schema(): array
    {
        return [
            'route' => $this->route(),
            'label' => $this->label(),
            'label_plural' => $this->labelPlural(),
            'permission' => $this->permission(),
            'route_key' => $this->routeKey(),
            'list_columns' => $this->listColumns(),
            'searchable' => $this->searchable(),
            'sortable' => $this->sortable(),
            'default_sort' => $this->defaultSort(),
            'fields' => $this->fields(),
            'can_create' => $this->canCreate(),
            'can_delete' => $this->canDelete(),
        ];
    }
}
