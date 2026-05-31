<?php

namespace App\Modules\Admin\Resources;

use App\Models\User;
use App\Models\Venue;
use App\Modules\Admin\AdminResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class VenueAdminResource extends AdminResource
{
    public function model(): string
    {
        return Venue::class;
    }

    public function route(): string
    {
        return 'venues';
    }

    public function permission(): string
    {
        return 'venues';
    }

    public function label(): string
    {
        return 'Venue';
    }

    public function labelPlural(): string
    {
        return 'Venues';
    }

    public function listColumns(): array
    {
        return [
            'id',
            'owner_name',
            'name',
            'type',
            'city',
            'address',
            'is_frozen',
            'frozen_at',
            'is_verified',
            'created_at',
        ];
    }

    public function searchable(): array
    {
        return ['name', 'address', 'city'];
    }

    public function sortable(): array
    {
        return ['id', 'name', 'city', 'frozen_at', 'is_verified', 'created_at'];
    }

    public function defaultSort(): string
    {
        return '-created_at';
    }

    public function with(): array
    {
        return ['owner:id,first_name,last_name,phone'];
    }

    public function fields(): array
    {
        $ownerOptions = User::orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone'])
            ->map(fn($u) => [
                'value' => (string)$u->id,
                'label' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))
                    ?: ('User #' . $u->id) . ' (' . $u->phone . ')',
            ])
            ->all();

        return [
            ['name' => 'owner_id', 'label' => 'Owner', 'type' => 'select', 'required' => true, 'options' => $ownerOptions],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'bar', 'label' => 'Bar'],
                ['value' => 'restaurant', 'label' => 'Restaurant'],
                ['value' => 'cafe', 'label' => 'Café'],
                ['value' => 'club', 'label' => 'Club'],
                ['value' => 'gallery', 'label' => 'Gallery'],
                ['value' => 'hotel', 'label' => 'Hotel'],
                ['value' => 'park', 'label' => 'Park'],
                ['value' => 'other', 'label' => 'Other'],
            ]],
            ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
            ['name' => 'city', 'label' => 'City', 'type' => 'text'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'helperText' => 'Internal notes visible only to admins'],
            ['name' => 'is_verified', 'label' => 'Verified', 'type' => 'checkbox'],
            // ── Freeze controls ──────────────────────────────────────────────
            ['name' => 'is_frozen', 'label' => 'Freeze venue', 'type' => 'checkbox', 'helperText' => 'Hides venue from discovery and blocks new events/promotions'],
            ['name' => 'freeze_reason', 'label' => 'Freeze reason', 'type' => 'text', 'helperText' => 'Required when freezing. Visible to admins only.'],
        ];
    }

    public function query(Request $request): Builder
    {
        return Venue::with($this->with());
    }

    public function transform(Model $model): array
    {
        $data = $model->toArray();
        $ownerName = trim(($model->owner?->first_name ?? '') . ' ' . ($model->owner?->last_name ?? ''));
        $data['owner_name'] = $ownerName ?: ($model->owner?->phone ?? '—');
        // Virtual field for the checkbox — computed from frozen_at
        $data['is_frozen'] = $model->isFrozen();
        return $data;
    }

    /**
     * Convert the virtual `is_frozen` checkbox into a real frozen_at timestamp.
     * Checking the box freezes the venue (records when); unchecking clears it.
     */
    public function beforeSave(Model $model, array $data, Request $request): array
    {
        if (array_key_exists('is_frozen', $data)) {
            $shouldFreeze = (bool)$data['is_frozen'];
            unset($data['is_frozen']); // not a real column

            if ($shouldFreeze && !$model->isFrozen()) {
                $data['frozen_at'] = now();
            } elseif (!$shouldFreeze) {
                $data['frozen_at'] = null;
                $data['freeze_reason'] = null;
            }
        }

        return $data;
    }

    public function rules(Request $request, ?Model $existing = null): array
    {
        return [
            'owner_id' => $existing ? 'sometimes|integer|exists:users,id' : 'required|integer|exists:users,id',
            'name' => 'sometimes|string|max:120',
            'type' => 'sometimes|string|max:60',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'is_verified' => 'boolean',
            'is_frozen' => 'boolean',
            'freeze_reason' => 'nullable|string|max:500',
        ];
    }

    public function canCreate(): bool
    {
        return true;
    }

    public function canDelete(): bool
    {
        return true;
    }
}
