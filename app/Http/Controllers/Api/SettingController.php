<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSettingRequest;
use App\Http\Requests\Api\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Setting::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('key', 'LIKE', "%{$search}%");
        }

        $settings = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Settings retrieved successfully',
            'data' => SettingResource::collection($settings),
            'meta' => [
                'current_page' => $settings->currentPage(),
                'per_page' => $settings->perPage(),
                'total' => $settings->total(),
                'last_page' => $settings->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Setting retrieved successfully',
            'data' => new SettingResource($setting),
        ]);
    }

    public function store(StoreSettingRequest $request): JsonResponse
    {
        $setting = Setting::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Setting created successfully',
            'data' => new SettingResource($setting),
        ], 201);
    }

    public function update(UpdateSettingRequest $request, int $id): JsonResponse
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
                'data' => null,
            ], 404);
        }

        $setting->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => new SettingResource($setting),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
                'data' => null,
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully',
            'data' => null,
        ]);
    }
}
