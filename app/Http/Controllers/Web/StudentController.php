<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(15)->withQueryString();

        return Inertia::render('Students/Index', [
            'students' => $students,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new student
     */
    public function create()
    {
        $classrooms = Classroom::select('id', 'name_en', 'name_kh', 'grade_level')
            ->orderBy('grade_level')
            ->get();

        return Inertia::render('Students/Create', [
            'classrooms' => $classrooms,
        ]);
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
            'gender' => ['required', 'string', 'in:male,female'],
            'student_type' => ['required', 'string', 'in:regular,special'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'current_address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'parent_occupation' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'class_id' => ['nullable', 'exists:classrooms,id'],
            'shift' => ['nullable', 'string', 'in:morning,afternoon,evening'],
            'academic_year' => ['nullable', 'string', 'max:9'],
            'previous_school' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive,suspended,graduated'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $validated['uuid'] = Str::uuid();
            $validated['created_by'] = $request->user()->id;
            $validated['registration_date'] = now()->toDateString();

            $student = Student::create($validated);

            DB::commit();

            return redirect()->route('students.index')
                ->with('success', 'Student created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified student
     */
    public function show(Student $student)
    {
        $student->load(['class', 'enrollments.subject', 'enrollments.teacher', 'payments']);

        return Inertia::render('Students/Show', [
            'student' => StudentResource::make($student)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified student
     */
    public function edit(Student $student)
    {
        $classrooms = Classroom::select('id', 'name_en', 'name_kh', 'grade_level')
            ->orderBy('grade_level')
            ->get();

        return Inertia::render('Students/Edit', [
            'student' => $student,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['required', 'date'],
            'place_of_birth' => ['nullable', 'string'],
            'gender' => ['required', 'string', 'in:male,female'],
            'student_type' => ['required', 'string', 'in:regular,special'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'current_address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string'],
            'parent_phone' => ['nullable', 'string', 'max:20'],
            'parent_occupation' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string'],
            'class_id' => ['nullable', 'exists:classrooms,id'],
            'shift' => ['nullable', 'string', 'in:morning,afternoon,evening'],
            'academic_year' => ['nullable', 'string', 'max:9'],
            'previous_school' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive,suspended,graduated'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $validated['updated_by'] = $request->user()->id;
            $student->update($validated);

            DB::commit();

            return redirect()->route('students.show', $student->uuid)
                ->with('success', 'Student updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified student
     */
    public function destroy(Student $student)
    {
        DB::beginTransaction();

        try {
            $student->delete();

            DB::commit();

            return redirect()->route('students.index')
                ->with('success', 'Student deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show student enrollments
     */
    public function enrollments(Student $student)
    {
        $student->load(['class']);

        $enrollments = $student->enrollments()
            ->with(['subject', 'teacher'])
            ->get();

        $subjects = Subject::select('id', 'subject_code', 'name_en', 'name_kh')
            ->orderBy('name_en')
            ->get();

        $teachers = Teacher::select('id', 'first_name', 'last_name', 'teacher_code')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Students/Enrollments', [
            'student' => $student,
            'enrollments' => $enrollments,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }
}
