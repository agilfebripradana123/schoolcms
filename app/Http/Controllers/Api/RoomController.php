<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRoomRequest;
use App\Http\Requests\Api\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
            'has_computer' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Room::query();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['has_computer'])) {
            $query->where('has_computer', $validated['has_computer'] ? 1 : 0);
        }

        $perPage = $validated['per_page'] ?? 10;
        $rooms = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Rooms retrieved successfully',
            'data' => RoomResource::collection($rooms),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'last_page' => $rooms->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Room retrieved successfully',
            'data' => new RoomResource($room),
        ]);
    }

    public function store(StoreRoomRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $room = DB::connection('mysql')->transaction(function () use ($validated) {
                $exists = Room::withTrashed()->where('code', $validated['code'])->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                return Room::create($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data' => new RoomResource($room),
        ], 201);
    }

    public function update(UpdateRoomRequest $request, int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        try {
            DB::connection('mysql')->transaction(function () use ($room, $validated) {
                $code = $validated['code'] ?? $room->code;

                $exists = Room::withTrashed()->where('code', $code)
                    ->where('id', '!=', $room->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'code' => ['The code has already been taken.'],
                    ]);
                }

                $room->update($validated);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data' => new RoomResource($room),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found',
                'data' => null,
            ], 404);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully',
            'data' => null,
        ]);
    }
}
