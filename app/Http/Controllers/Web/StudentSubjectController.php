<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentSubjectController extends Controller
{
    /**
     * Store a newly created enrollment
     */
    public function store(Request $request, Student $student)
    {

        $validated = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
        ]);

        DB::beginTransaction();

        try {
            // Check if enrollment already exists
            $exists = StudentSubject::where('student_id', $student->id)
                ->where('subject_id', $validated['subject_id'])
                ->exists();

            if ($exists) {
                return back()->withErrors(['error' => 'Student is already enrolled in this subject']);
            }

            StudentSubject::create([
                'student_id' => $student->id,
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'status' => 'active',
                'academic_year' => $student->academic_year ?? now()->year,
            ]);

            DB::commit();

            return back()->with('success', 'Enrollment added successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified enrollment
     */
    public function update(Request $request, $studentId, $enrollmentId)
    {
        $enrollment = StudentSubject::where('student_id', $studentId)
            ->findOrFail($enrollmentId);

        $validated = $request->validate([
            'grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'string', 'in:active,completed,dropped,pending'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $enrollment->update($validated);

            DB::commit();

            return back()->with('success', 'Enrollment updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified enrollment
     */
    public function destroy($studentId, $enrollmentId)
    {
        $enrollment = StudentSubject::where('student_id', $studentId)
            ->findOrFail($enrollmentId);

        DB::beginTransaction();

        try {
            $enrollment->delete();

            DB::commit();

            return back()->with('success', 'Enrollment removed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
