<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Students\StoreAttendanceRequest;
use App\Http\Requests\Api\Students\UpdateAttendanceRequest;
use App\Http\Resources\Students\AttendanceResource;
use App\Models\Students\Attendance;

class AttendanceController extends Controller
{
    /**
     * Menampilkan semua data absensi.
     */
    public function index()
    {
        $attendances = Attendance::latest('date')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Attendances retrieved successfully',
            'data' => AttendanceResource::collection($attendances),
        ]);
    }

    /**
     * Menambahkan data absensi.
     */
    public function store(StoreAttendanceRequest $request)
    {
        $attendance = Attendance::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Attendance created successfully',
            'data' => new AttendanceResource($attendance),
        ], 201);
    }

    /**
     * Menampilkan detail absensi.
     */
    public function show($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance retrieved successfully',
            'data' => new AttendanceResource($attendance),
        ]);
    }

    /**
     * Mengubah data absensi.
     */
    public function update(UpdateAttendanceRequest $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found',
                'data' => null,
            ], 404);
        }

        $attendance->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => new AttendanceResource($attendance),
        ]);
    }

    /**
     * Menghapus data absensi.
     */
    public function destroy($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance not found',
                'data' => null,
            ], 404);
        }

        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully',
            'data' => null,
        ]);
    }
}