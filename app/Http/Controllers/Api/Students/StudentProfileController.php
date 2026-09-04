<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Resources\Students\StudentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');
        $student->load(['parent','guardians','schoolClass']);

        return response()->json([
            'success' => true,
            'message' => 'Student profile retrieved successfully',
            'data' => new StudentResource($student),
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate(['photo' => 'required|image|max:2048|mimes:jpg,jpeg,png,webp']);
        $student = $request->attributes->get('student_profile');
        if($student->photo) Storage::disk('public')->delete($student->photo);
        $path = $request->file('photo')->store('students/photos','public');
        $student->update(['photo' => $path]);
        $student->load(['parent','guardians','schoolClass']);
        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui',
            'data' => new StudentResource($student),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'nik' => 'sometimes|nullable|string|size:16|regex:/^[0-9]+$/',
            'religion' => 'sometimes|nullable|string|max:50',
            'birth_place' => 'sometimes|nullable|string|max:100',
            'birth_date' => 'sometimes|nullable|date',
            'address' => 'sometimes|nullable|string|max:1000',
            'rt' => 'sometimes|nullable|string|max:5',
            'rw' => 'sometimes|nullable|string|max:5',
            'hamlet' => 'sometimes|nullable|string|max:100',
            'village' => 'sometimes|nullable|string|max:100',
            'district' => 'sometimes|nullable|string|max:100',
            'postal_code' => 'sometimes|nullable|string|max:10',
            'residence_type' => 'sometimes|nullable|string|max:100',
            'transportation' => 'sometimes|nullable|string|max:100',
            'telephone' => 'sometimes|nullable|string|max:20',
            'family_card_number' => 'sometimes|nullable|string|max:30',
            'birth_certificate_registration_number' => 'sometimes|nullable|string|max:100',
            'skhun' => 'sometimes|nullable|string|max:100',
            'previous_school' => 'sometimes|nullable|string|max:150',
            'national_exam_number' => 'sometimes|nullable|string|max:50',
            'diploma_serial_number' => 'sometimes|nullable|string|max:100',
            'special_needs' => 'sometimes|nullable|string|max:150',
            'birth_order' => 'sometimes|nullable|integer|min:1',
            'sibling_count' => 'sometimes|nullable|integer|min:0',
            'weight' => 'sometimes|nullable|numeric|min:0',
            'height' => 'sometimes|nullable|numeric|min:0',
            'head_circumference' => 'sometimes|nullable|numeric|min:0',
            'school_distance' => 'sometimes|nullable|numeric|min:0',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'bank_name' => 'sometimes|nullable|string|max:100',
            'bank_account_number' => 'sometimes|nullable|string|max:50',
            'bank_account_holder' => 'sometimes|nullable|string|max:150',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|email|max:100',
            'parent.father_name' => 'sometimes|nullable|string|max:100',
            'parent.mother_name' => 'sometimes|nullable|string|max:100',
            'parent.father_occupation' => 'sometimes|nullable|string|max:100',
            'parent.mother_occupation' => 'sometimes|nullable|string|max:100',
            'parent.phone' => 'sometimes|nullable|string|max:20',
            'parent.address' => 'sometimes|nullable|string',
        ]);
        $studentData = collect($validated)->except('parent')->all();
        if(!empty($studentData)) $student->update($studentData);
        if(isset($validated['parent'])){
            $parent = $student->parent()->first();
            if($parent) $parent->update($validated['parent']);
            else $student->parent()->create(array_merge(['student_id'=>$student->id], $validated['parent']));
        }
        $student->load(['parent','guardians','schoolClass']);
        return response()->json(['success'=>true,'message'=>'Profil berhasil diperbarui','data'=>new StudentResource($student)]);
    }
}
