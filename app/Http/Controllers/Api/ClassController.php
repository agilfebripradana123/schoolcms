<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Menampilkan semua kelas.
     */
    public function index()
    {
        $classes = SchoolClass::latest()->get();

        return response()->json([
            'message' => 'Data kelas berhasil diambil.',
            'data' => $classes,
        ]);
    }

    /**
     * Menampilkan detail kelas.
     */
    public function show($id)
    {
        $class = SchoolClass::find($id);

        if (!$class) {
            return response()->json([
                'message' => 'Data kelas tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail kelas berhasil diambil.',
            'data' => $class,
        ]);
    }

    /**
     * Menambahkan kelas.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'integer'],
            'level' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        $class = SchoolClass::create($validated);

        return response()->json([
            'message' => 'Kelas berhasil ditambahkan.',
            'data' => $class,
        ], 201);
    }

    /**
     * Mengubah data kelas.
     */
    public function update(Request $request, $id)
    {
        $class = SchoolClass::find($id);

        if (!$class) {
            return response()->json([
                'message' => 'Data kelas tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'integer'],
            'level' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        $class->update($validated);

        return response()->json([
            'message' => 'Kelas berhasil diperbarui.',
            'data' => $class,
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
                'message' => 'Data kelas tidak ditemukan.',
            ], 404);
        }

        $class->delete();

        return response()->json([
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }
}