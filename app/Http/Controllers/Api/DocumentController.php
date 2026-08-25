<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Registrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    private const DOCUMENT_TYPES = [
        'kk' => 'document_kk',
        'birth_certificate' => 'document_birth_certificate',
        'diploma' => 'document_diploma',
        'parent_ktp' => 'document_parent_ktp',
        'kip_kks' => 'document_kip_kks',
        'photo' => 'document_photo',
        'other' => 'document_other',
    ];

    private const ALLOWED_MIMES = ['pdf', 'jpg', 'jpeg', 'png'];
    private const MAX_FILE_SIZE = 5120; // 5 MB in KB

    public function index(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        // Ownership check for Siswa
        if (!$this->authorizeAccess($request, $registrant)) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        $documents = [];
        foreach (self::DOCUMENT_TYPES as $type => $column) {
            $documents[$type] = !empty($registrant->{$column});
        }

        $this->logAccess($request, 'ppdb.document.view_metadata', $registrant->id);

        return response()->json([
            'success' => true,
            'message' => 'Document metadata retrieved successfully',
            'data' => $documents,
        ]);
    }

    public function store(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        // Only Admin/Administrator can upload
        if (!$this->isAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'document_type' => ['required', 'string', Rule::in(array_keys(self::DOCUMENT_TYPES))],
            'document' => [
                'required',
                'file',
                'max:' . self::MAX_FILE_SIZE,
                'mimes:pdf,jpg,jpeg,png',
            ],
        ]);

        $documentType = $validated['document_type'];
        $column = self::DOCUMENT_TYPES[$documentType];
        $file = $request->file('document');

        // Additional MIME validation
        $this->validateFileContent($file, $documentType);

        // Delete old file if exists
        if (!empty($registrant->{$column})) {
            $oldPath = $registrant->{$column};
            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        // Generate random filename
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid() . '.' . $extension;
        $path = "ppdb/registrations/{$registrant->id}/{$filename}";

        // Store file
        Storage::disk('local')->put($path, file_get_contents($file));

        // Update database
        $registrant->{$column} = $path;
        $registrant->save();

        // Audit log
        $this->logAccess($request, 'ppdb.document.uploaded', $registrant->id, [
            'document_type' => $documentType,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => [
                'document_type' => $documentType,
                'uploaded' => true,
            ],
        ], 201);
    }

    public function show(\Illuminate\Http\Request $request, int $id, string $type): \Symfony\Component\HttpFoundation\Response
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        // Ownership check for Siswa
        if (!$this->authorizeAccess($request, $registrant)) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        // Validate document type
        if (!isset(self::DOCUMENT_TYPES[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type',
                'data' => null,
            ], 404);
        }

        $column = self::DOCUMENT_TYPES[$type];
        $filePath = $registrant->{$column};

        if (empty($filePath) || !Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
                'data' => null,
            ], 404);
        }

        // Audit log
        $this->logAccess($request, 'ppdb.document.downloaded', $registrant->id, [
            'document_type' => $type,
        ]);

        // Return file
        $fullPath = Storage::disk('local')->path($filePath);
        $mimeType = Storage::disk('local')->mimeType($filePath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $type . '.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"',
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, int $id, string $type): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        // Only Admin/Administrator can delete
        if (!$this->isAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 403);
        }

        // Validate document type
        if (!isset(self::DOCUMENT_TYPES[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type',
                'data' => null,
            ], 404);
        }

        $column = self::DOCUMENT_TYPES[$type];
        $filePath = $registrant->{$column};

        // Delete file if exists
        if (!empty($filePath) && Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }

        // Clear database field
        $registrant->{$column} = null;
        $registrant->save();

        // Audit log
        $this->logAccess($request, 'ppdb.document.deleted', $registrant->id, [
            'document_type' => $type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
            'data' => null,
        ]);
    }

    private function authorizeAccess(\Illuminate\Http\Request $request, Registrant $registrant): bool
    {
        $user = $request->user();

        // Admin/Administrator can access all
        if ($this->isAdmin($request)) {
            return true;
        }

        // Siswa can only access own registration
        if ($user->role->name === 'Siswa') {
            return $registrant->student && $registrant->student->user_id === $user->id;
        }

        return false;
    }

    private function isAdmin(\Illuminate\Http\Request $request): bool
    {
        $user = $request->user();
        return in_array($user->role->name ?? '', ['Admin', 'Administrator']);
    }

    private function validateFileContent(UploadedFile $file, string $documentType): void
    {
        // Check file extension against allowed list
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_MIMES)) {
            throw ValidationException::withMessages([
                'document' => ['The document must be a PDF, JPG, or PNG file.'],
            ]);
        }

        // Block executables
        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'bash', 'js', 'html', 'htm', 'svg'];
        if (in_array($extension, $blockedExtensions)) {
            throw ValidationException::withMessages([
                'document' => ['This file type is not allowed.'],
            ]);
        }

        // MIME content inspection (only for real uploaded files with content)
        if ($file->getError() === UPLOAD_ERR_OK) {
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath) && filesize($realPath) > 0) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $detectedMime = $finfo->file($realPath);

                $allowedMimes = [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                ];

                if (!in_array($detectedMime, $allowedMimes)) {
                    throw ValidationException::withMessages([
                        'document' => ['The file content does not match the declared type.'],
                    ]);
                }
            }
        }
    }

    private function logAccess(\Illuminate\Http\Request $request, string $action, int $modelId, array $metadata = []): void
    {
        try {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => $action,
                'model' => 'Registrant',
                'model_id' => $modelId,
                'description' => json_encode($metadata),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Don't fail the request if audit logging fails
        }
    }
}
