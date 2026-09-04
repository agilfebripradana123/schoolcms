<?php

namespace App\Http\Controllers\Api\Teachers;

use App\Http\Controllers\Controller;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\ClassStudent;
use App\Models\Academic\Grade;
use App\Models\Staff\TeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Teacher self-service: Nilai (Phase 7).
 *
 * Scope: authenticated user -> teacherProfile -> TeacherAssignment
 * (teacher_id + class_id + subject_id + academic_year_id). Grade bersifat
 * komponen (type = tugas/uts/uas) per (class, subject, semester, academic_year).
 * Client tidak pernah mengirim teacher_id sebagai scope.
 */
class TeacherGradeController extends Controller
{
    private function teacher(Request $request)
    {
        return $request->user()?->teacherProfile;
    }

    /**
     * Resolve academic_year_id dari input (id atau nama 'YYYY/YYYY').
     */
    private function resolveAcademicYear(array $input, array &$scope, &$yearName)
    {
        if (!empty($input['academic_year_id'])) {
            $year = AcademicYear::find((int) $input['academic_year_id']);
            if (!$year) {
                return null;
            }
            $scope['academic_year_id'] = $year->id;
            $yearName = $year->name;
            return $year;
        }

        if (!empty($input['academic_year'])) {
            $year = AcademicYear::where('name', $input['academic_year'])->first();
            if (!$year) {
                return null;
            }
            $scope['academic_year_id'] = $year->id;
            $yearName = $year->name;
            return $year;
        }

        // Default ke tahun aktif.
        $year = AcademicYear::where('is_active', true)->first();
        if (!$year) {
            return null;
        }
        $scope['academic_year_id'] = $year->id;
        $yearName = $year->name;
        return $year;
    }

    /**
     * GET /api/teacher/grades?class_id&subject_id&type&semester&academic_year...
     * Roster siswa aktif di kelas + nilai komponen (type) utk scope guru.
     */
    public function roster(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        if (!$teacher) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['tugas', 'uts', 'uas'])],
            'semester' => ['required', Rule::in(['1', '2'])],
            'academic_year' => ['nullable', 'string'],
            'academic_year_id' => ['nullable', 'integer'],
        ]);

        $scope = [
            'teacher_id' => $teacher->id,
            'class_id' => (int) $validated['class_id'],
            'subject_id' => (int) $validated['subject_id'],
        ];

        $year = $this->resolveAcademicYear($validated, $scope, $yearName);

        if (!$year) {
            return response()->json(['success' => false, 'message' => 'Academic year not found', 'data' => null], 404);
        }

        if (!TeacherAssignment::where($scope)->exists()) {
            return response()->json(['success' => false, 'message' => 'Scope not found', 'data' => null], 404);
        }

        $classId = $scope['class_id'];
        $type = $validated['type'];
        $semester = $validated['semester'];

        $enrollments = ClassStudent::where('class_id', $classId)
            ->where('status', 'active')
            ->whereHas('student')
            ->with('student')
            ->get();

        $studentIds = $enrollments->pluck('student_id')->all();

        $grades = Grade::where('class_id', $classId)
            ->where('subject_id', $scope['subject_id'])
            ->where('type', $type)
            ->where('semester', $semester)
            ->where('academic_year', $yearName)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $students = $enrollments->map(function ($enrollment) use ($grades) {
            $student = $enrollment->student;
            $grade = $grades->get($enrollment->student_id);

            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'gender' => $student->gender,
                'score' => $grade?->score !== null ? (float) $grade->score : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Teacher grades roster retrieved successfully',
            'data' => [
                'class_id' => $classId,
                'subject_id' => $scope['subject_id'],
                'type' => $type,
                'semester' => $semester,
                'academic_year' => $yearName,
                'students' => $students,
            ],
        ]);
    }

    /**
     * POST /api/teacher/grades/bulk
     * Simpan nilai massal utk satu (class, subject, type, semester, academic_year).
     * Upsert per (student, subject, class, type, semester, year); atomic.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $teacher = $this->teacher($request);

        if (!$teacher) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'class_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['tugas', 'uts', 'uas'])],
            'semester' => ['required', Rule::in(['1', '2'])],
            'academic_year' => ['nullable', 'string'],
            'academic_year_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'max:200'],
            'items.*.student_id' => ['required', 'integer'],
            'items.*.score' => ['present', 'numeric', 'min:0', 'max:100'],
        ]);

        $scope = [
            'teacher_id' => $teacher->id,
            'class_id' => (int) $validated['class_id'],
            'subject_id' => (int) $validated['subject_id'],
        ];

        $year = $this->resolveAcademicYear($validated, $scope, $yearName);

        if (!$year) {
            return response()->json(['success' => false, 'message' => 'Academic year not found', 'data' => null], 404);
        }

        if (!TeacherAssignment::where($scope)->exists()) {
            return response()->json(['success' => false, 'message' => 'Scope not found', 'data' => null], 404);
        }

        $classId = $scope['class_id'];
        $subjectId = $scope['subject_id'];
        $type = $validated['type'];
        $semester = $validated['semester'];

        $allowedStudentIds = ClassStudent::where('class_id', $classId)
            ->where('status', 'active')
            ->pluck('student_id')
            ->all();

        $allowedSet = array_flip($allowedStudentIds);

        DB::transaction(function () use ($validated, $classId, $subjectId, $type, $semester, $yearName, $allowedSet) {
            foreach ($validated['items'] as $item) {
                $studentId = (int) $item['student_id'];

                if (!isset($allowedSet[$studentId])) {
                    abort(422, "Siswa #{$studentId} bukan anggota kelas yang diizinkan.");
                }

                Grade::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'class_id' => $classId,
                        'type' => $type,
                        'semester' => $semester,
                        'academic_year' => $yearName,
                    ],
                    [
                        'score' => $item['score'],
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Grades saved successfully',
            'data' => null,
        ]);
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Forbidden',
            'data' => null,
        ], 403);
    }
}
