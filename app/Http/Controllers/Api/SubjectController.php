<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Menampilkan semua mata pelajaran.
     */
    public function index()
    {
        $subjects = Subject::latest()->get();

        return response()->json([
            'message' => 'Data mata pelajaran berhasil diambil.',
            'data' => $subjects,
        ]);
    }

    /**
     * Menambahkan mata pelajaran.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:wajib,pilihan'],
            'description' => ['nullable', 'string'],
        ]);

        $subject = Subject::create($validated);

        return response()->json([
            'message' => 'Mata pelajaran berhasil ditambahkan.',
            'data' => $subject,
        ], 201);
    }

    /**
     * Menampilkan detail mata pelajaran.
     */
    public function show($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'message' => 'Data mata pelajaran tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail mata pelajaran berhasil diambil.',
            'data' => $subject,
        ]);
    }

    /**
     * Mengubah data mata pelajaran.
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'message' => 'Data mata pelajaran tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:subjects,code,' . $subject->id,
            ],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:wajib,pilihan'],
            'description' => ['nullable', 'string'],
        ]);

        $subject->update($validated);

        return response()->json([
            'message' => 'Mata pelajaran berhasil diperbarui.',
            'data' => $subject,
        ]);
    }

    /**
     * Menghapus mata pelajaran.
     */
    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'message' => 'Data mata pelajaran tidak ditemukan.',
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'message' => 'Mata pelajaran berhasil dihapus.',
        ]);
    }
}