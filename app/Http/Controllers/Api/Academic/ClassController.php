<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Academic\StoreClassRequest;
use App\Http\Requests\Api\Academic\UpdateClassRequest;
use App\Http\Resources\Academic\SchoolClassResource;
use App\Models\Academic\SchoolClass;

class ClassController extends Controller
{
    /**
     * Menampilkan semua kelas.
     */
    public function index()
    {
        $classes = SchoolClass::with('teacher')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Classes retrieved successfully',
            'data' => SchoolClassResource::collection($classes),
        ]);
    }

    /**
     * Menambahkan kelas.
     */
    public function store(StoreClassRequest $request)
    {
        $class = SchoolClass::create($request->validated());

        $class->load('teacher');

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully',
            'data' => new SchoolClassResource($class),
        ], 201);
    }

    /**
     * Menampilkan detail kelas.
     */
    public function show($id)
    {
        $class = SchoolClass::with('teacher')
            ->find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Class retrieved successfully',
            'data' => new SchoolClassResource($class),
        ]);
    }

    /**
     * Mengubah data kelas.
     */
    public function update(UpdateClassRequest $request, $id)
    {
        $class = SchoolClass::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
                'data' => null,
            ], 404);
        }

        $class->update($request->validated());

        $class->load('teacher');

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully',
            'data' => new SchoolClassResource($class),
        ]);
    }

    /**
     * Menghapus kelas.
     */
    public function destroy($id)
    {
        $class = SchoolClass::find($id);

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
                'data' => null,
            ], 404);
        }

        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully',
            'data' => null,
        ]);
    }
}