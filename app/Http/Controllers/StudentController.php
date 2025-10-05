<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of students
     */
    public function index(Request $request)
    {
        $query = Student::with(['class', 'creator']);

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by class
        if ($request->has('class_id')) {
            $query->byClass($request->class_id);
        }

        // Filter by shift
        if ($request->has('shift')) {
            $query->byShift($request->shift);
        }

        // Filter by academic year
        if ($request->has('academic_year')) {
            $query->byAcademicYear($request->academic_year);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->paginate($request->get('per_page', 15));

        return response()->jsonSuccess(StudentResource::collection($students));
    }

    /**
     * Store a newly created student
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['required', 'date'],
            'place_of_birth' => ['nullable', 'string'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'student_type' => ['required', 'string', 'in:regular,monk'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'parent_name' => ['required', 'string'],
            'parent_phone' => ['required', 'string', 'max:20'],
            'parent_occupation' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'shift' => ['required', 'string', 'in:morning,afternoon,evening,night,weekend'],
            'registration_date' => ['required', 'date'],
            'academic_year' => ['required', 'string', 'max:9'],
            'previous_school' => ['nullable', 'string'],
            'photo' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $validated['created_by'] = $request->user()->id;
            $validated['status'] = 'active';

            $student = Student::create($validated);

            DB::commit();

            return response()->jsonSuccess(StudentResource::make($student), 201, 'Student created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified student
     */
    public function show($id)
    {
        $student = Student::with(['class', 'subjects', 'payments', 'creator'])->findOrFail($id);

        return response()->jsonSuccess(StudentResource::make($student));
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['sometimes', 'date'],
            'place_of_birth' => ['nullable', 'string'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'student_type' => ['sometimes', 'string', 'in:regular,monk'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'parent_name' => ['sometimes', 'string'],
            'parent_phone' => ['sometimes', 'string', 'max:20'],
            'parent_occupation' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'shift' => ['sometimes', 'string', 'in:morning,afternoon,evening,night,weekend'],
            'academic_year' => ['sometimes', 'string', 'max:9'],
            'previous_school' => ['nullable', 'string'],
            'photo' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended,graduated'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $validated['updated_by'] = $request->user()->id;
            $student->update($validated);

            DB::commit();

            return response()->jsonSuccess(StudentResource::make($student), 200, 'Student updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        DB::beginTransaction();

        try {
            $student->delete();

            DB::commit();

            return response()->jsonSuccess([], 200, 'Student deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }
}
