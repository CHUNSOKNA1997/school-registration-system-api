<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentApplication;
use App\Services\StudentApplicationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function __construct(private readonly StudentApplicationService $applicationService)
    {
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => [
                'required',
                'date',
                'before:' . now()->subYears(4)->format('Y-m-d'),
                'after:' . now()->subYears(25)->format('Y-m-d'),
            ],
            'place_of_birth' => ['nullable', 'string'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'student_type' => ['required', 'string', 'in:regular,monk'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('students', 'phone')->whereNull('deleted_at')],
            'email' => ['required', 'email', Rule::unique('students', 'email')->whereNull('deleted_at')],
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
            'payment_plan' => ['nullable', 'string', 'in:monthly,half_year,yearly'],
            'previous_school' => ['nullable', 'string'],
            'photo' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $application = $this->applicationService->createApplication($validated);
        } catch (\RuntimeException $e) {
            return response()->jsonError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return response()->jsonError($e->getMessage(), 500);
        }

        return response()->jsonSuccess([
            'application_uuid' => $application->uuid,
            'status' => $application->status,
            'payment_plan' => $application->payment_plan,
            'payment_uuid' => $application->payment?->uuid,
            'payment_code' => $application->payment?->payment_code,
            'payment_status' => $application->payment?->status,
            'amount' => $application->payment?->amount,
            'school_email' => $application->school_email,
            'onboarding_sent_at' => $application->onboarding_sent_at,
        ], 201, 'Application submitted successfully');
    }

    public function createCheckoutSession(Request $request, string $application_uuid)
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        $application = StudentApplication::where('uuid', $application_uuid)
            ->with(['student', 'payment'])
            ->first();

        if (!$application) {
            return response()->jsonError('Application not found', 404);
        }

        $result = $this->applicationService->createCheckoutSession($application, $validated);
        if (!($result['success'] ?? false)) {
            return response()->json($result, 500);
        }

        return response()->jsonSuccess([
            'application_uuid' => $application->uuid,
            'status' => $application->status,
            'payment_uuid' => $application->payment?->uuid,
            'checkout' => $result,
        ]);
    }

    public function showPaymentStatus(string $application_uuid)
    {
        $application = StudentApplication::where('uuid', $application_uuid)
            ->with(['student', 'payment.paywayTransaction', 'user'])
            ->first();

        if (!$application) {
            return response()->jsonError('Application not found', 404);
        }

        $application = $this->applicationService->refreshPaymentStatus($application);

        return response()->jsonSuccess([
            'application_uuid' => $application->uuid,
            'application_status' => $application->status,
            'payment_status' => $application->payment?->status,
            'payment_uuid' => $application->payment?->uuid,
            'payment_code' => $application->payment?->payment_code,
            'paid_at' => $application->paid_at,
            'school_email' => $application->school_email,
            'onboarding_sent_at' => $application->onboarding_sent_at,
            'can_activate' => !empty($application->activation_token_hash),
        ]);
    }

    public function activateAccount(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $activated = $this->applicationService->activateStudentAccount(
                $validated['token'],
                $validated['password']
            );
        } catch (\RuntimeException $e) {
            return response()->jsonError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return response()->jsonError($e->getMessage(), 500);
        }

        return response()->jsonSuccess([
            'message' => 'Student account activated successfully',
            'school_email' => $activated['school_email'],
            'token' => $activated['token'],
            'user' => $activated['user'],
        ]);
    }
}
