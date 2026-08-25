<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCalendarRequest;
use App\Http\Requests\Api\UpdateCalendarRequest;
use App\Http\Resources\CalendarResource;
use App\Models\Calendar;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Calendar::query()->with('academicYear');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('title', 'LIKE', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('event_date')) {
            $query->whereDate('event_date', $request->input('event_date'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        $events = $query->orderBy('event_date', 'asc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Calendar events retrieved successfully',
            'data' => CalendarResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $event = Calendar::with('academicYear')->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Calendar event retrieved successfully',
            'data' => new CalendarResource($event),
        ]);
    }

    public function store(StoreCalendarRequest $request): JsonResponse
    {
        $event = Calendar::create($request->validated());
        $event->load('academicYear');

        return response()->json([
            'success' => true,
            'message' => 'Calendar event created successfully',
            'data' => new CalendarResource($event),
        ], 201);
    }

    public function update(UpdateCalendarRequest $request, int $id): JsonResponse
    {
        $event = Calendar::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found',
                'data' => null,
            ], 404);
        }

        $event->update($request->validated());
        $event->load('academicYear');

        return response()->json([
            'success' => true,
            'message' => 'Calendar event updated successfully',
            'data' => new CalendarResource($event),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = Calendar::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Calendar event not found',
                'data' => null,
            ], 404);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Calendar event deleted successfully',
            'data' => null,
        ]);
    }
}
