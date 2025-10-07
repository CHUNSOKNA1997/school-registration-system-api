<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentSubjectResource;
use App\Models\Student;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentSubjectController extends Controller
{
    /**
     * Get all enrollments for a student
     */
    public function index(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);

        $query = StudentSubject::with(['subject', 'teacher'])
            ->where('student_id', $student->id);

        // Filter by academic year
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->paginate($request->get('per_page', 15));

        return response()->jsonSuccess(StudentSubjectResource::collection($enrollments));
    }

    /**
     * Enroll a student in a subject
     */
    public function store(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'enrolled_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:active,completed,dropped,failed'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            // Check if already enrolled
            $exists = StudentSubject::where('student_id', $student->id)
                ->where('subject_id', $validated['subject_id'])
                ->where('academic_year', $validated['academic_year'])
                ->exists();

            if ($exists) {
                return response()->jsonError('Student is already enrolled in this subject for the academic year', 422);
            }

            $enrollment = StudentSubject::create([
                'uuid' => Str::uuid()->toString(),
                'student_id' => $student->id,
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'academic_year' => $validated['academic_year'],
                'enrolled_date' => $validated['enrolled_date'],
                'status' => $validated['status'] ?? 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->jsonSuccess(
                StudentSubjectResource::make($enrollment->load(['subject', 'teacher'])),
                201,
                'Student enrolled successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Update enrollment details (grades, status, etc.)
     */
    public function update(Request $request, $studentId, $enrollmentId)
    {
        $student = Student::findOrFail($studentId);
        $enrollment = StudentSubject::where('student_id', $student->id)
            ->findOrFail($enrollmentId);

        $validated = $request->validate([
            'teacher_id' => ['sometimes', 'exists:teachers,id'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grade' => ['nullable', 'string', 'max:2'],
            'status' => ['sometimes', 'string', 'in:active,completed,dropped,failed'],
            'completion_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $enrollment->update($validated);

            DB::commit();

            return response()->jsonSuccess(
                StudentSubjectResource::make($enrollment->load(['subject', 'teacher'])),
                200,
                'Enrollment updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Drop a subject (soft delete or change status)
     */
    public function destroy($studentId, $enrollmentId)
    {
        $student = Student::findOrFail($studentId);
        $enrollment = StudentSubject::where('student_id', $student->id)
            ->findOrFail($enrollmentId);

        DB::beginTransaction();

        try {
            // Change status to dropped instead of deleting
            $enrollment->update(['status' => 'dropped']);

            DB::commit();

            return response()->jsonSuccess([], 200, 'Subject dropped successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Bulk enroll students in subjects
     */
    public function bulkEnroll(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['exists:students,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'enrolled_date' => ['required', 'date'],
        ]);

        DB::beginTransaction();

        try {
            $enrollments = [];
            $skipped = [];

            foreach ($validated['student_ids'] as $studentId) {
                // Check if already enrolled
                $exists = StudentSubject::where('student_id', $studentId)
                    ->where('subject_id', $validated['subject_id'])
                    ->where('academic_year', $validated['academic_year'])
                    ->exists();

                if ($exists) {
                    $skipped[] = $studentId;
                    continue;
                }

                $enrollments[] = [
                    'uuid' => Str::uuid()->toString(),
                    'student_id' => $studentId,
                    'subject_id' => $validated['subject_id'],
                    'teacher_id' => $validated['teacher_id'],
                    'academic_year' => $validated['academic_year'],
                    'enrolled_date' => $validated['enrolled_date'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($enrollments)) {
                StudentSubject::insert($enrollments);
            }

            DB::commit();

            return response()->jsonSuccess([
                'enrolled' => count($enrollments),
                'skipped' => count($skipped),
                'skipped_ids' => $skipped,
            ], 200, 'Bulk enrollment completed');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Get student's transcript/grade report
     */
    public function transcript($studentId)
    {
        $student = Student::with(['class'])->findOrFail($studentId);

        $enrollments = StudentSubject::with(['subject', 'teacher'])
            ->where('student_id', $student->id)
            ->orderBy('academic_year', 'desc')
            ->orderBy('enrolled_date', 'asc')
            ->get()
            ->groupBy('academic_year');

        return response()->jsonSuccess([
            'student' => [
                'id' => $student->id,
                'uuid' => $student->uuid,
                'student_code' => $student->student_code,
                'full_name' => $student->full_name,
                'class' => $student->class?->name,
            ],
            'transcript' => $enrollments->map(function ($yearEnrollments, $year) {
                return [
                    'academic_year' => $year,
                    'subjects' => StudentSubjectResource::collection($yearEnrollments),
                    'total_subjects' => $yearEnrollments->count(),
                    'completed' => $yearEnrollments->where('status', 'completed')->count(),
                    'average_score' => $yearEnrollments->avg('score'),
                ];
            }),
        ]);
    }
}
