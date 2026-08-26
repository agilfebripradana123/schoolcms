<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Staff\StoreTeacherDocumentRequest;
use App\Http\Requests\Api\Staff\UpdateTeacherDocumentRequest;
use App\Http\Resources\Staff\TeacherDocumentResource;
use App\Models\Staff\TeacherDocument;
use Illuminate\Http\JsonResponse;

class TeacherDocumentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = TeacherDocument::query()->with('teacher');

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->input('document_type'));
        }

        $documents = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Teacher documents retrieved successfully',
            'data' => TeacherDocumentResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'last_page' => $documents->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $document = TeacherDocument::with('teacher')->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher document not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher document retrieved successfully',
            'data' => new TeacherDocumentResource($document),
        ]);
    }

    public function store(StoreTeacherDocumentRequest $request): JsonResponse
    {
        $document = TeacherDocument::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Teacher document created successfully',
            'data' => new TeacherDocumentResource($document->load('teacher')),
        ], 201);
    }

    public function update(UpdateTeacherDocumentRequest $request, int $id): JsonResponse
    {
        $document = TeacherDocument::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher document not found',
                'data' => null,
            ], 404);
        }

        $document->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Teacher document updated successfully',
            'data' => new TeacherDocumentResource($document->load('teacher')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = TeacherDocument::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher document not found',
                'data' => null,
            ], 404);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher document deleted successfully',
            'data' => null,
        ]);
    }
}
