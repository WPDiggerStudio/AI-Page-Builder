<?php

namespace BraCalculator\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use BraCalculator\App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

/**
 * Notification Controller
 *
 * Handles notification CRUD operations.
 *
 * @package BraCalculator\App\Http\Controllers
 */
class NotificationController {
    /**
     * Get all notifications.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse {
        $query = Notification::query();

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by read status
        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific notification.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse {
        $notification = Notification::findOrFail($id);

        return response()->json([
            'data' => $notification,
        ]);
    }

    /**
     * Create a new notification.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer',
            'type' => 'required|in:info,success,warning,error',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'link' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $notification = Notification::create($validator->validated());

        return response()->json([
            'data' => $notification,
            'message' => 'Notification created successfully',
        ], 201);
    }

    /**
     * Update a notification.
     *
     * @param Request $request
     * @param int $id
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse {
        $notification = Notification::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:info,success,warning,error',
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'link' => 'nullable|url',
            'read' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $notification->update($validator->validated());

        return response()->json([
            'data' => $notification->fresh(),
            'message' => 'Notification updated successfully',
        ]);
    }

    /**
     * Delete a notification.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function markAsRead(int $id): JsonResponse {
        $notification = Notification::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'data' => $notification->fresh(),
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark a notification as unread.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function markAsUnread(int $id): JsonResponse {
        $notification = Notification::findOrFail($id);
        $notification->markAsUnread();

        return response()->json([
            'data' => $notification->fresh(),
            'message' => 'Notification marked as unread',
        ]);
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $count = Notification::where('user_id', $request->user_id)
            ->unread()
            ->update([
                'read' => true,
                'read_at' => Carbon::now(),
            ]);

        return response()->json([
            'message' => "{$count} notifications marked as read",
        ]);
    }

    /**
     * Get unread count for a user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse {
        $userId = $request->input('user_id', get_current_user_id());

        $count = Notification::where('user_id', $userId)
            ->unread()
            ->count();

        return response()->json([
            'data' => [
                'user_id' => $userId,
                'unread_count' => $count,
            ],
        ]);
    }
}
