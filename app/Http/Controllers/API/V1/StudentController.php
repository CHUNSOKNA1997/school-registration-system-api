<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'date_of_birth' => [
                'required',
                'date',
                'before:' . now()->subYears(4)->format('Y-m-d'), // Must be at least 4 years old
                'after:' . now()->subYears(25)->format('Y-m-d'),  // Not older than 25
            ],
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

        // Validate class capacity if class is selected
        if (!empty($validated['class_id'])) {
            $class = Classroom::findOrFail($validated['class_id']);
            if ($class->current_enrollment >= $class->capacity) {
                return response()->jsonError('Selected class is at full capacity', 422);
            }
        }

        DB::beginTransaction();

        try {
            // Generate unique student code
            $validated['student_code'] = $this->generateStudentCode($validated['academic_year']);
            $validated['uuid'] = Str::uuid();
            $validated['created_by'] = $request->user()->id;
            $validated['status'] = 'active';

            $student = Student::create($validated);

            // Update class enrollment count
            if (!empty($validated['class_id'])) {
                Classroom::where('id', $validated['class_id'])->increment('current_enrollment');
            }

            // Create initial payment record
            $this->createInitialPayment($student);

            DB::commit();

            return response()->jsonSuccess(StudentResource::make($student), 201, 'Student registered successfully');
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

    /**
     * Generate unique student code
     * Format: YYYY-XXXX (e.g., 2024-0001)
     */
    protected function generateStudentCode($academicYear)
    {
        // Extract year from academic year (e.g., "2024-2025" -> "2024")
        $year = substr($academicYear, 0, 4);

        // Get last student code for this year
        $lastStudent = Student::where('student_code', 'LIKE', $year . '-%')
            ->orderBy('student_code', 'desc')
            ->first();

        if (!$lastStudent) {
            $sequence = 1;
        } else {
            // Extract sequence number from last code (e.g., "2024-0042" -> 42)
            $lastSequence = (int) substr($lastStudent->student_code, 5);
            $sequence = $lastSequence + 1;
        }

        // Format: YYYY-XXXX with leading zeros
        return $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create initial payment record for student
     */
    protected function createInitialPayment(Student $student)
    {
        // Calculate base tuition amount (can be configured or calculated from subjects)
        $baseAmount = 500.00; // Base tuition fee

        // Calculate discount for monk students (100% discount)
        $discountAmount = $student->student_type === 'monk' ? $baseAmount : 0;

        // Calculate final balance
        $balance = $baseAmount - $discountAmount;

        // Create payment record
        Payment::create([
            'uuid' => Str::uuid(),
            'payment_code' => 'PAY-' . now()->format('Ymd') . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT),
            'student_id' => $student->id,
            'academic_year' => $student->academic_year,
            'amount' => $baseAmount,
            'discount_amount' => $discountAmount,
            'paid_amount' => 0,
            'balance' => $balance,
            'payment_type' => 'tuition',
            'payment_period' => 'monthly',
            'payment_method' => 'pending',
            'due_date' => now()->addMonth(),
            'status' => $discountAmount >= $baseAmount ? 'paid' : 'pending',
        ]);
    }
}
