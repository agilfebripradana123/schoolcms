<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\System\StoreSettingRequest;
use App\Http\Requests\Api\System\UpdateSettingRequest;
use App\Http\Resources\System\SettingResource;
use App\Models\System\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('key', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        $settings = $query
            ->orderBy('group')
            ->orderBy('sort_order')
            ->paginate($request->input('per_page', 15));

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

        if (! $setting) {
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
        $data = $request->validated();
        $shouldEncrypt = $data['is_encrypted'] || Setting::isSecretType($data['type'] ?? '');

        if ($shouldEncrypt && ! empty($data['value'])) {
            $data['value'] = Crypt::encryptString((string) $data['value']);
            $data['is_encrypted'] = true;
        }

        $setting = Setting::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Setting created successfully',
            'data' => new SettingResource($setting),
        ], 201);
    }

    public function update(UpdateSettingRequest $request, int $id): JsonResponse
    {
        $setting = Setting::find($id);

        if (! $setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
                'data' => null,
            ], 404);
        }

        $data = $request->validated();

        $willEncrypt = ((bool) ($data['is_encrypted'] ?? $setting->is_encrypted))
            || Setting::isSecretType($data['type'] ?? $setting->type ?? '');

        // Secret handling: only change the value when a new non-empty value is
        // supplied. An empty/absent value keeps the existing (encrypted) secret.
        if ($willEncrypt) {
            $data['is_encrypted'] = true;
            if (array_key_exists('value', $data)) {
                if ($data['value'] === null || $data['value'] === '') {
                    unset($data['value']);
                } else {
                    $data['value'] = Crypt::encryptString((string) $data['value']);
                }
            }
        }

        $setting->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => new SettingResource($setting->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $setting = Setting::find($id);

        if (! $setting) {
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
