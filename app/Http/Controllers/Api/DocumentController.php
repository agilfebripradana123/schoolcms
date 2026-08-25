<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDocumentRequest;
use App\Http\Requests\Api\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Document::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('title', 'LIKE', "%{$q}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $documents = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Documents retrieved successfully',
            'data' => DocumentResource::collection($documents),
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
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document retrieved successfully',
            'data' => new DocumentResource($document),
        ]);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = Document::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Document created successfully',
            'data' => new DocumentResource($document),
        ], 201);
    }

    public function update(UpdateDocumentRequest $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'data' => null,
            ], 404);
        }

        $document->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully',
            'data' => new DocumentResource($document),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'data' => null,
            ], 404);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
            'data' => null,
        ]);
    }
}
