<?php

namespace App\Modules\Admin\Resources;

use App\Models\Event;
use App\Models\Venue;
use App\Modules\Admin\AdminResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EventAdminResource extends AdminResource
{
    public function model(): string
    {
        return Event::class;
    }

    public function route(): string
    {
        return 'events';
    }

    public function permission(): string
    {
        return 'events';
    }

    public function label(): string
    {
        return 'Event';
    }

    public function labelPlural(): string
    {
        return 'Events';
    }

    public function listColumns(): array
    {
        return [
            'id',
            'venue_name',
            'title',
            'category',
            'starts_at',
            'ends_at',
            'publish_status',
            'is_online_event',
            'rsvp_limit',
            'created_at',
        ];
    }

    public function searchable(): array
    {
        return ['title', 'description'];
    }

    public function sortable(): array
    {
        return ['id', 'title', 'starts_at', 'ends_at', 'publish_status', 'created_at'];
    }

    public function defaultSort(): string
    {
        return '-starts_at';
    }

    public function with(): array
    {
        return ['venue:id,name,owner_id'];
    }

    public function fields(): array
    {
        $venueOptions = Venue::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($v) => ['value' => (string)$v->id, 'label' => $v->name])
            ->all();

        return [
            ['name' => 'venue_id', 'label' => 'Venue', 'type' => 'select', 'required' => true, 'options' => $venueOptions],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'category', 'label' => 'Category', 'type' => 'text'],
            ['name' => 'image_url', 'label' => 'Image URL', 'type' => 'text'],
            ['name' => 'starts_at', 'label' => 'Starts at', 'type' => 'datetime', 'required' => true],
            ['name' => 'ends_at', 'label' => 'Ends at', 'type' => 'datetime', 'helperText' => 'Leave empty to default to 3 hours after start'],
            ['name' => 'is_online_event', 'label' => 'Online event', 'type' => 'checkbox'],
            ['name' => 'online_event_url', 'label' => 'Online event URL', 'type' => 'text', 'helperText' => 'Required when "Online event" is checked'],
            ['name' => 'rsvp_limit', 'label' => 'RSVP limit', 'type' => 'text', 'helperText' => 'Leave empty for unlimited'],
            ['name' => 'guest_limit', 'label' => 'Guest limit', 'type' => 'text', 'helperText' => 'Max additional guests per RSVP (0–10)'],
            ['name' => 'publish_status', 'label' => 'Publish status', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'published', 'label' => 'Published'],
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ]],
        ];
    }

    public function query(Request $request): Builder
    {
        return Event::with($this->with());
    }

    public function transform(Model $model): array
    {
        $data = $model->toArray();
        $data['venue_name'] = $model->venue?->name ?? '—';
        return $data;
    }

    public function beforeSave(Model $model, array $data, Request $request): array
    {
        // Default ends_at to 3 hours after starts_at when not provided on create
        if (empty($data['ends_at']) && !empty($data['starts_at'])) {
            $data['ends_at'] = \Carbon\Carbon::parse($data['starts_at'])->addHours(3)->toIso8601String();
        }

        // Events are always free for now
        $data['is_free'] = true;

        return $data;
    }

    public function rules(Request $request, ?Model $existing = null): array
    {
        return [
            'venue_id' => $existing ? 'sometimes|integer|exists:venues,id' : 'required|integer|exists:venues,id',
            'title' => 'sometimes|string|max:80',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'image_url' => 'nullable|url',
            'starts_at' => 'sometimes|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_online_event' => 'boolean',
            'online_event_url' => 'nullable|url|required_if:is_online_event,true',
            'rsvp_limit' => 'nullable|integer|min:1',
            'guest_limit' => 'nullable|integer|min:0|max:10',
            'publish_status' => 'sometimes|in:published,draft,cancelled',
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
