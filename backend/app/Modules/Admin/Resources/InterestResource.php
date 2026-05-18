<?php

namespace App\Modules\Admin\Resources;

use App\Models\Interest;
use App\Modules\Admin\AdminResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InterestResource extends AdminResource
{
    public function model(): string
    {
        return Interest::class;
    }

    public function route(): string
    {
        return 'interests';
    }

    public function permission(): string
    {
        return 'interests';
    }

    public function listColumns(): array
    {
        return ['id', 'name', 'slug', 'created_at'];
    }

    public function searchable(): array
    {
        return ['name', 'slug'];
    }

    public function sortable(): array
    {
        return ['id', 'name', 'slug', 'created_at', 'updated_at'];
    }

    public function defaultSort(): string
    {
        return 'name';
    }

    public function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => 'Slug', 'type' => 'text',
                'helperText' => 'URL-safe identifier. Auto-generated from the name if left blank on create.'],
        ];
    }

    public function rules(Request $request, ?Model $existing = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('interests', 'slug')->ignore($existing?->id)],
        ];
    }

    public function beforeSave(Model $model, array $data, Request $request): array
    {
        // Auto-generate slug from name if it wasn't provided. Also normalises
        // any user-supplied slug to lower-case-with-dashes.
        if (empty($data['slug'] ?? null)) {
            $data['slug'] = Str::slug($data['name'] ?? $model->name ?? '');
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }
        return $data;
    }

    public function transform(Model $model): array
    {
        /** @var Interest $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ];
    }
}
