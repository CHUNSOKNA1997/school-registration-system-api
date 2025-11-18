<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    /**
     * Display a listing of subjects
     */
    public function index(Request $request)
    {
        $query = Subject::query();

        // Filter by grade level
        if ($request->has('grade_level')) {
            $query->byGradeLevel($request->grade_level);
        }

        // Filter by subject type
        if ($request->has('subject_type')) {
            $query->byType($request->subject_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $subjects = $query->paginate($request->get('per_page', 15));

        return response()->jsonSuccess(SubjectResource::collection($subjects));
    }

    /**
     * Store a newly created subject
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_khmer' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'subject_type' => ['required', 'string', 'in:core,elective,extra'],
            'credits' => ['nullable', 'integer', 'min:1'],
            'hours_per_week' => ['nullable', 'integer', 'min:1'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'syllabus' => ['nullable', 'string'],
            'prerequisites' => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $validated['is_active'] = true;

            $subject = Subject::create($validated);

            DB::commit();

            return response()->jsonSuccess(SubjectResource::make($subject), 201, 'Subject created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified subject
     */
    public function show($id)
    {
        $subject = Subject::with(['students', 'teachers'])->findOrFail($id);

        return response()->jsonSuccess(SubjectResource::make($subject));
    }

    /**
     * Update the specified subject
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'name_khmer' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'grade_level' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'subject_type' => ['sometimes', 'string', 'in:core,elective,extra'],
            'credits' => ['nullable', 'integer', 'min:1'],
            'hours_per_week' => ['nullable', 'integer', 'min:1'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'syllabus' => ['nullable', 'string'],
            'prerequisites' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::beginTransaction();

        try {
            $subject->update($validated);

            DB::commit();

            return response()->jsonSuccess(SubjectResource::make($subject), 200, 'Subject updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified subject
     */
    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);

        DB::beginTransaction();

        try {
            $subject->delete();

            DB::commit();

            return response()->jsonSuccess([], 200, 'Subject deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }
}
