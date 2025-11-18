<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    /**
     * Display a listing of classrooms
     */
    public function index(Request $request)
    {
        $query = Classroom::with(['teacher', 'students']);

        // Filter by grade level
        if ($request->has('grade_level')) {
            $query->byGradeLevel($request->grade_level);
        }

        // Filter by academic year
        if ($request->has('academic_year')) {
            $query->byAcademicYear($request->academic_year);
        }

        // Filter by shift
        if ($request->has('shift')) {
            $query->byShift($request->shift);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $classrooms = $query->paginate($request->get('per_page', 15));

        return response()->jsonSuccess(ClassroomResource::collection($classrooms));
    }

    /**
     * Store a newly created classroom
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_khmer' => ['nullable', 'string'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'section' => ['nullable', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:9'],
            'shift' => ['required', 'string', 'in:morning,afternoon,evening,night,weekend'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'description' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $validated['is_active'] = true;

            $classroom = Classroom::create($validated);

            DB::commit();

            return response()->jsonSuccess(ClassroomResource::make($classroom), 201, 'Classroom created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified classroom
     */
    public function show($id)
    {
        $classroom = Classroom::with(['teacher', 'students', 'subjects'])->findOrFail($id);

        return response()->jsonSuccess(ClassroomResource::make($classroom));
    }

    /**
     * Update the specified classroom
     */
    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'name_khmer' => ['nullable', 'string'],
            'grade_level' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'section' => ['nullable', 'string', 'max:50'],
            'academic_year' => ['sometimes', 'string', 'max:9'],
            'shift' => ['sometimes', 'string', 'in:morning,afternoon,evening,night,weekend'],
            'room_number' => ['nullable', 'string', 'max:50'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::beginTransaction();

        try {
            $classroom->update($validated);

            DB::commit();

            return response()->jsonSuccess(ClassroomResource::make($classroom), 200, 'Classroom updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified classroom
     */
    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);

        DB::beginTransaction();

        try {
            $classroom->delete();

            DB::commit();

            return response()->jsonSuccess([], 200, 'Classroom deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }
}
