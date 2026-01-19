<?php

namespace BraCalculator\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Controller
 *
 * Handles API requests.
 *
 * @package BraCalculator\App\Http\Controllers
 */
class ApiController {
    /**
     * API health check.
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse {
        return response()->json([
            'status' => 'ok',
            'version' => WPJARVIS_VERSION,
            'timestamp' => time(),
        ]);
    }

    /**
     * Get all items.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse {
        return response()->json([
            'data' => [],
            'message' => 'Items retrieved successfully',
        ]);
    }

    /**
     * Get a specific item.
     *
     * @param int $id The item ID
     *
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse {
        return response()->json([
            'data' => ['id' => $id],
            'message' => 'Item retrieved successfully',
        ]);
    }

    /**
     * Create a new item.
     *
     * @param Request $request The HTTP request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return response()->json([
            'data' => $validated,
            'message' => 'Item created successfully',
        ], 201);
    }

    /**
     * Update an existing item.
     *
     * @param Request $request The HTTP request
     * @param int $id The item ID
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return response()->json([
            'data' => array_merge(['id' => $id], $validated),
            'message' => 'Item updated successfully',
        ]);
    }

    /**
     * Delete an item.
     *
     * @param int $id The item ID
     *
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse {
        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }
}
