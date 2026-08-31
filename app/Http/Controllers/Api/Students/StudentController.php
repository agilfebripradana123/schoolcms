<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Students\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Menampilkan semua siswa.
     */
    public function index()
    {
        $students = Student::latest()->get();

        return response()->json([
            'message' => 'Data siswa berhasil diambil.',
            'data' => $students,
        ]);
    }

    /**
     * Menampilkan detail siswa.
     */
    public function show($id)
    {
        $student = Student::with(['user', 'schoolClass', 'parent', 'guardians'])
            ->find($id);

        if (!$student) {
            return response()->json([
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail siswa berhasil diambil.',
            'data' => $student,
        ]);
    }

    /**
     * Menambahkan siswa.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'nisn' => ['required', 'string', 'max:20', 'unique:students,nisn'],
            'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
            'name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Siswa berhasil ditambahkan.',
            'data' => $student,
        ], 201);
    }

    /**
     * Mengubah data siswa.
     */
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'nisn' => [
                'required',
                'string',
                'max:20',
                'unique:students,nisn,' . $student->id,
            ],
            'nis' => [
                'required',
                'string',
                'max:20',
                'unique:students,nis,' . $student->id,
            ],
            'name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'string', 'max:255'],
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Data siswa berhasil diperbarui.',
            'data' => $student,
        ]);
    }

    /**
     * Menghapus siswa.
     */
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $student->delete();

        return response()->json([
            'message' => 'Siswa berhasil dihapus.',
        ]);
    }
}