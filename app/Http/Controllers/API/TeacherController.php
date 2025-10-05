<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers
     */
    public function index(Request $request)
    {
        $query = Teacher::query();

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by employment type
        if ($request->has('employment_type')) {
            $query->byEmploymentType($request->employment_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $teachers = $query->paginate($request->get('per_page', 15));

        return response()->jsonSuccess(TeacherResource::collection($teachers));
    }

    /**
     * Store a newly created teacher
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'education_level' => ['nullable', 'string'],
            'specialization' => ['nullable', 'string'],
            'employment_type' => ['required', 'string', 'in:full_time,part_time,contract'],
            'hire_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'bank_account' => ['nullable', 'string'],
            'id_card_number' => ['nullable', 'string'],
            'photo' => ['nullable', 'string'],
            'cv' => ['nullable', 'string'],
            'certificates' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $validated['is_active'] = true;

            $teacher = Teacher::create($validated);

            DB::commit();

            return response()->jsonSuccess(TeacherResource::make($teacher), 201, 'Teacher created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified teacher
     */
    public function show($id)
    {
        $teacher = Teacher::with(['subjects'])->findOrFail($id);

        return response()->jsonSuccess(TeacherResource::make($teacher));
    }

    /**
     * Update the specified teacher
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['sometimes', 'date'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'education_level' => ['nullable', 'string'],
            'specialization' => ['nullable', 'string'],
            'employment_type' => ['sometimes', 'string', 'in:full_time,part_time,contract'],
            'hire_date' => ['sometimes', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'bank_account' => ['nullable', 'string'],
            'id_card_number' => ['nullable', 'string'],
            'photo' => ['nullable', 'string'],
            'cv' => ['nullable', 'string'],
            'certificates' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $teacher->update($validated);

            DB::commit();

            return response()->jsonSuccess(TeacherResource::make($teacher), 200, 'Teacher updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified teacher
     */
    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        DB::beginTransaction();

        try {
            $teacher->delete();

            DB::commit();

            return response()->jsonSuccess([], 200, 'Teacher deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }
}
