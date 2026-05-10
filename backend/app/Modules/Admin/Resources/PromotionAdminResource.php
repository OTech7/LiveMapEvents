<?php

namespace App\Modules\Admin\Resources;

use App\Models\Promotion;
use App\Modules\Admin\AdminResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PromotionAdminResource extends AdminResource
{
    public function model(): string
    {
        return Promotion::class;
    }

    public function route(): string
    {
        return 'promotions';
    }

    public function permission(): string
    {
        return 'promotions';
    }

    public function label(): string
    {
        return 'Promotion';
    }

    public function labelPlural(): string
    {
        return 'Promotions';
    }

    public function listColumns(): array
    {
        return [
            'id',
            'venue_name',
            'title',
            'discount_type',
            'discount_value',
            'recurrence_type',
            'start_time',
            'end_time',
            'valid_from',
            'valid_to',
            'is_active',
            'created_at',
        ];
    }

    public function searchable(): array
    {
        return ['title', 'description'];
    }

    public function sortable(): array
    {
        return ['id', 'title', 'discount_value', 'valid_from', 'valid_to', 'is_active', 'created_at'];
    }

    public function defaultSort(): string
    {
        return '-created_at';
    }

    public function with(): array
    {
        return ['venue:id,name,owner_id'];
    }

    public function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'discount_type', 'label' => 'Discount type', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'percentage', 'label' => 'Percentage (%)'],
                ['value' => 'fixed', 'label' => 'Fixed amount'],
            ]],
            ['name' => 'discount_value', 'label' => 'Discount value', 'type' => 'text', 'required' => true],
            ['name' => 'recurrence_type', 'label' => 'Recurrence', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'recurring', 'label' => 'Recurring (weekly)'],
                ['value' => 'one_time', 'label' => 'One-time'],
            ]],
            ['name' => 'start_time', 'label' => 'Start time', 'type' => 'text', 'helperText' => 'Format: HH:MM (e.g. 10:00)'],
            ['name' => 'end_time', 'label' => 'End time', 'type' => 'text', 'helperText' => 'Format: HH:MM (e.g. 12:00)'],
            ['name' => 'valid_from', 'label' => 'Valid from', 'type' => 'date'],
            ['name' => 'valid_to', 'label' => 'Valid to', 'type' => 'date', 'helperText' => 'Leave empty for no end date'],
            ['name' => 'terms', 'label' => 'Terms & conditions', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    public function query(Request $request): Builder
    {
        return Promotion::with($this->with())->withTrashed(false);
    }

    public function transform(Model $model): array
    {
        $data = $model->toArray();
        // Flatten venue name for the list view column
        $data['venue_name'] = $model->venue?->name ?? '—';
        return $data;
    }

    public function rules(Request $request, ?Model $existing = null): array
    {
        return [
            'title' => 'sometimes|string|max:120',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0.01',
            'recurrence_type' => 'sometimes|in:one_time,recurring',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'nullable|date',
            'terms' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    // Admins can view and edit — but should not create promotions on behalf
    // of businesses (businesses create their own via the API).
    public function canCreate(): bool
    {
        return false;
    }

    // Admins can delete if needed (e.g. abusive promotion).
    public function canDelete(): bool
    {
        return true;
    }
}
