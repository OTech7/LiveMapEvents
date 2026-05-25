<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CancelEventRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(protected EventService $eventService)
    {
    }

    /**
     * GET /v1/business/events
     *
     * List all events for venues owned by the authenticated user.
     * Optionally filter by venue_id.
     */
    public function index(Request $request): JsonResponse
    {
        $events = $this->eventService->getForOwner(
            auth()->user(),
            $request->integer('venue_id') ?: null
        );

        return ApiResponse::success(
            'messages.events_fetched_successfully',
            EventResource::collection($events)->response($request)->getData(true)
        );
    }

    /**
     * POST /v1/business/events
     *
     * Create a new event under a venue the authenticated user owns.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create(auth()->user(), $request->validated());

        return ApiResponse::success(
            'messages.event_created_successfully',
            EventResource::make($event->load('venue')),
            201
        );
    }

    /**
     * GET /v1/business/events/{event}
     *
     * Show a single event. Restricted to the venue owner.
     */
    public function show(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        return ApiResponse::success(
            'messages.event_fetched_successfully',
            EventResource::make($event->load('venue'))
        );
    }

    /**
     * PUT /v1/business/events/{event}
     *
     * Update an event. Restricted to the venue owner.
     */
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event = $this->eventService->update($event, $request->validated());

        return ApiResponse::success(
            'messages.event_updated_successfully',
            EventResource::make($event)
        );
    }

    /**
     * DELETE /v1/business/events/{event}
     *
     * Hard-delete an event. Restricted to the venue owner.
     */
    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $this->eventService->delete($event);

        return ApiResponse::success('messages.event_deleted_successfully');
    }

    /**
     * POST /v1/business/events/{event}/cancel
     *
     * Soft-cancel an event (sets publish_status to 'cancelled').
     * Restricted to the venue owner.
     */
    public function cancel(CancelEventRequest $request, Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        $event = $this->eventService->cancel($event, $request->input('reason'));

        return ApiResponse::success(
            'messages.event_cancelled_successfully',
            EventResource::make($event)
        );
    }
}
