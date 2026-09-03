<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Communication\StoreUserNotificationRequest;
use App\Http\Requests\Api\Communication\UpdateUserNotificationRequest;
use App\Http\Resources\Communication\UserNotificationResource;
use App\Models\Communication\UserNotification;
use Illuminate\Http\JsonResponse;

class UserNotificationController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = UserNotification::query()->with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read') ? 1 : 0);
        }

        $notifications = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => UserNotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    public function my(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = UserNotification::where('user_id', $request->user()->id);

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read') ? 1 : 0);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'My notifications retrieved successfully',
            'data' => UserNotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    public function unreadCount(\Illuminate\Http\Request $request): JsonResponse
    {
        $count = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', 0)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Unread count retrieved successfully',
            'data' => ['unread_count' => $count],
        ]);
    }

    /**
     * Mark a single notification as read. Scoped strictly to the authenticated
     * user — a notification owned by another user returns 404.
     */
    public function markAsRead(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $notification = UserNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
                'data' => null,
            ], 404);
        }

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => new UserNotificationResource($notification->fresh()),
        ]);
    }

    public function markAllAsRead(\Illuminate\Http\Request $request): JsonResponse
    {
        $updated = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', 0)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'data' => ['updated' => $updated],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $notification = UserNotification::with('user')->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification retrieved successfully',
            'data' => new UserNotificationResource($notification),
        ]);
    }

    public function store(StoreUserNotificationRequest $request): JsonResponse
    {
        $notification = UserNotification::create($request->validated());
        $notification->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => new UserNotificationResource($notification),
        ], 201);
    }

    public function update(UpdateUserNotificationRequest $request, int $id): JsonResponse
    {
        $notification = UserNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        if (!empty($validated['is_read']) && !array_key_exists('read_at', $validated)) {
            $validated['read_at'] = now();
        }

        $notification->update($validated);
        $notification->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully',
            'data' => new UserNotificationResource($notification),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $notification = UserNotification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
                'data' => null,
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
            'data' => null,
        ]);
    }
}
