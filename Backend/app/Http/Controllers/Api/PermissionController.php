<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Get all available permissions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::active();

        // Filter by category if provided
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $permissions = $query->orderBy('category')->orderBy('display_name')->get();

        // Group by category
        $grouped = $permissions->groupBy('category')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'display_name' => $item->display_name,
                    'description' => $item->description,
                ];
            })->values();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'all' => $permissions->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'display_name' => $item->display_name,
                        'description' => $item->description,
                        'category' => $item->category,
                    ];
                }),
                'grouped' => $grouped,
            ],
        ]);
    }

    /**
     * Get specific permission details.
     */
    public function show(int $id): JsonResponse
    {
        $permission = Permission::active()->find($id);

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی یافت نشد.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'category' => $permission->category,
            ],
        ]);
    }
}
