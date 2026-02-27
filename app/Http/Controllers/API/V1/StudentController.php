<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StudentController extends Controller
{
    private const CANONICAL_STUDENT_RESOURCE_PATH = '/api/v1/students/{student}';
    private const SUNSET_AT = '2026-06-30 23:59:59 UTC';

    protected function findStudentByIdOrUuid(string $identifier): Student
    {
        return Student::where('uuid', $identifier)
            ->orWhere('id', $identifier)
            ->firstOrFail();
    }

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
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('students', 'phone')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', Rule::unique('students', 'email')->whereNull('deleted_at')],
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

        if ($this->hasDuplicateIdentity($validated)) {
            return response()->jsonError(
                'Student is already registered with the same name, date of birth, and parent phone',
                422
            );
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
        $student = $this->findStudentByIdOrUuid((string) $id)
            ->load(['class', 'subjects', 'payments', 'creator']);

        return response()->jsonSuccess(StudentResource::make($student));
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, $id): Response
    {
        $response = $this->handleUpdate($request, $id);

        if ($request->isMethod('put')) {
            return $this->withDeprecationHeaders($response, self::CANONICAL_STUDENT_RESOURCE_PATH);
        }

        return $response;
    }

    private function handleUpdate(Request $request, $id): Response
    {
        $student = $this->findStudentByIdOrUuid((string) $id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['sometimes', 'date'],
            'place_of_birth' => ['nullable', 'string'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'student_type' => ['sometimes', 'string', 'in:regular,monk'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('students', 'phone')->ignore($student->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('students', 'email')->ignore($student->id)->whereNull('deleted_at'),
            ],
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
            $identityPayload = [
                'first_name' => $validated['first_name'] ?? $student->first_name,
                'last_name' => $validated['last_name'] ?? $student->last_name,
                'date_of_birth' => $validated['date_of_birth'] ?? $student->date_of_birth?->format('Y-m-d'),
                'parent_phone' => $validated['parent_phone'] ?? $student->parent_phone,
            ];

            if ($this->hasDuplicateIdentity($identityPayload, $student->id)) {
                DB::rollBack();
                return response()->jsonError(
                    'Student is already registered with the same name, date of birth, and parent phone',
                    422
                );
            }

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
        $student = $this->findStudentByIdOrUuid((string) $id);

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
            // Method is updated when payment is completed (e.g. KHQR => bakong).
            'payment_method' => PaymentMethod::CASH->value,
            'due_date' => now()->addMonth(),
            'status' => $discountAmount >= $baseAmount ? 'paid' : 'pending',
        ]);
    }

    private function withDeprecationHeaders(Response $response, string $replacementPath): Response
    {
        $sunset = gmdate('D, d M Y H:i:s \G\M\T', strtotime(self::SUNSET_AT));

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', $sunset);
        $response->headers->set('Link', sprintf('<%s>; rel="successor-version"', $replacementPath));

        return $response;
    }

    private function hasDuplicateIdentity(array $payload, ?int $ignoreStudentId = null): bool
    {
        $query = Student::query()
            ->where('first_name', $payload['first_name'])
            ->where('last_name', $payload['last_name'])
            ->whereDate('date_of_birth', $payload['date_of_birth'])
            ->where('parent_phone', $payload['parent_phone'])
            ->whereNull('deleted_at');

        if ($ignoreStudentId !== null) {
            $query->where('id', '!=', $ignoreStudentId);
        }

        return $query->exists();
    }
}
