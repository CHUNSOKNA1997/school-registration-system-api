<?php

namespace App\Services;

use App\Mail\StudentOnboardingMail;
use App\Enums\PaymentMethod;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\PaywayTransaction;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StudentApplicationService
{
    private const BASE_MONTHLY_TUITION = 500.00;
    private const YEARLY_DISCOUNT_RATE = 0.10;

    public function __construct(private readonly PaywayService $paywayService)
    {
    }

    public function createApplication(array $validated): StudentApplication
    {
        return DB::transaction(function () use ($validated) {
            $paymentPlan = $validated['payment_plan'] ?? 'monthly';
            unset($validated['payment_plan']);

            if (!empty($validated['class_id'])) {
                $class = Classroom::findOrFail($validated['class_id']);
                if ($class->current_enrollment >= $class->capacity) {
                    throw new \RuntimeException('Selected class is at full capacity');
                }
            }

            if ($this->hasDuplicateIdentity($validated)) {
                throw new \RuntimeException('Student is already registered with the same name, date of birth, and parent phone');
            }

            $student = Student::create([
                ...$validated,
                'uuid' => (string) Str::uuid(),
                'student_code' => $this->generateStudentCode($validated['academic_year']),
                'registration_date' => now()->toDateString(),
                'status' => 'inactive',
            ]);

            if (!empty($validated['class_id'])) {
                Classroom::where('id', $validated['class_id'])->increment('current_enrollment');
            }

            $plan = $this->resolvePaymentPlan($paymentPlan);
            $discountAmount = $student->student_type === 'monk'
                ? (float) $plan['amount']
                : (float) $plan['discount_amount'];
            $balance = (float) $plan['amount'] - $discountAmount;
            $isPaid = $discountAmount >= (float) $plan['amount'];

            $payment = Payment::create([
                'uuid' => (string) Str::uuid(),
                'payment_code' => 'PAY-' . now()->format('Ymd') . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT),
                'student_id' => $student->id,
                'academic_year' => $student->academic_year,
                'amount' => (float) $plan['amount'],
                'discount_amount' => $discountAmount,
                'paid_amount' => 0,
                'balance' => $balance,
                'payment_type' => 'tuition',
                'payment_period' => $plan['payment_period'],
                'payment_method' => PaymentMethod::CASH->value,
                'due_date' => now()->addMonths((int) $plan['months']),
                'status' => $isPaid ? 'paid' : 'pending',
                'paid_at' => $isPaid ? now() : null,
                'payment_date' => $isPaid ? now()->toDateString() : null,
            ]);

            $application = StudentApplication::create([
                'uuid' => (string) Str::uuid(),
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'personal_email' => $validated['email'],
                'payment_plan' => $paymentPlan,
                'status' => $isPaid ? 'paid' : 'payment_pending',
                'paid_at' => $isPaid ? now() : null,
                'data' => $validated,
            ]);

            if ($isPaid) {
                $this->provisionStudentAccount($application->load(['student', 'payment']));
            }

            return $application->fresh(['student', 'payment', 'user']);
        });
    }

    public function createCheckoutSession(StudentApplication $application, array $customerData = []): array
    {
        $application->loadMissing(['student', 'payment']);

        $payload = array_merge([
            'first_name' => $application->student->first_name,
            'last_name' => $application->student->last_name,
            'email' => $application->personal_email,
            'phone' => $application->student->phone,
        ], $customerData);

        return $this->paywayService->generateKHQR($application->payment, $payload);
    }

    public function refreshPaymentStatus(StudentApplication $application): StudentApplication
    {
        $application->loadMissing(['payment.paywayTransaction', 'student', 'user']);
        $payment = $application->payment;

        if ($payment->status !== 'paid') {
            $this->syncPaymentWithGateway($payment);
            $payment->refresh();
        }

        if ($payment->status === 'paid') {
            if (!$application->paid_at) {
                $application->paid_at = now();
            }

            if (in_array($application->status, ['submitted', 'payment_pending'], true)) {
                $application->status = 'paid';
            }

            $application->save();
            $this->provisionStudentAccount($application->fresh(['student', 'payment', 'user']));
        }

        return $application->fresh(['student', 'payment', 'user']);
    }

    public function provisionFromPaidPayment(Payment $payment): void
    {
        $application = StudentApplication::where('payment_id', $payment->id)
            ->with(['student', 'payment', 'user'])
            ->first();

        if (!$application) {
            return;
        }

        $this->refreshPaymentStatus($application);
    }

    public function activateStudentAccount(string $rawToken, string $password): array
    {
        $tokenHash = hash('sha256', $rawToken);

        $application = StudentApplication::where('activation_token_hash', $tokenHash)
            ->where('activation_token_expires_at', '>', now())
            ->with('user')
            ->first();

        if (!$application || !$application->user) {
            throw new \RuntimeException('Invalid or expired activation token');
        }

        DB::transaction(function () use ($application, $password) {
            $user = User::where('id', $application->user_id)->lockForUpdate()->firstOrFail();

            $user->update([
                'password' => Hash::make($password),
                'is_active' => true,
            ]);

            $application->update([
                'status' => 'activated',
                'activation_token_hash' => null,
                'activation_token_expires_at' => null,
            ]);
        });

        $user = User::findOrFail($application->user_id);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'school_email' => $application->school_email,
        ];
    }

    private function syncPaymentWithGateway(Payment $payment): void
    {
        $transaction = $payment->paywayTransaction;
        if (!$transaction || $payment->status === 'paid') {
            return;
        }

        $canVerifyWithGateway = !empty(config('payway.api_key')) && !empty(config('payway.merchant_id'));
        if (!$canVerifyWithGateway) {
            return;
        }

        try {
            $verificationResponse = $this->paywayService->checkTransactionStatus($transaction->tran_id);
            if (!$this->paywayService->isSuccessfulTransactionResponse($verificationResponse)) {
                return;
            }

            $apv = (string) ($verificationResponse['apv'] ?? '');

            DB::transaction(function () use ($payment, $transaction, $apv) {
                $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();
                $lockedTransaction = PaywayTransaction::where('id', $transaction->id)->lockForUpdate()->first();

                if (!$lockedPayment || !$lockedTransaction || $lockedPayment->status === 'paid') {
                    return;
                }

                $lockedTransaction->update([
                    'status' => 'success',
                    'apv' => $apv ?: $lockedTransaction->apv,
                ]);

                $lockedPayment->update([
                    'status' => 'paid',
                    'khqr_reference' => $apv ?: $lockedPayment->khqr_reference,
                    'paid_at' => $lockedPayment->paid_at ?? now(),
                    'payment_date' => $lockedPayment->payment_date ?? now()->toDateString(),
                    'payment_method' => PaymentMethod::BAKONG->value,
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('Application payment status reconciliation skipped', [
                'payment_uuid' => $payment->uuid,
                'tran_id' => $transaction->tran_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function provisionStudentAccount(StudentApplication $application): void
    {
        if ($application->user_id || !$application->payment || $application->payment->status !== 'paid') {
            return;
        }

        DB::transaction(function () use ($application) {
            $freshApplication = StudentApplication::where('id', $application->id)
                ->lockForUpdate()
                ->with(['student', 'payment', 'user'])
                ->firstOrFail();

            if ($freshApplication->user_id || $freshApplication->payment->status !== 'paid') {
                return;
            }

            $schoolEmail = $this->generateUniqueSchoolEmail(
                $freshApplication->student->first_name,
                $freshApplication->student->last_name
            );

            $user = User::create([
                'name' => trim($freshApplication->student->first_name . ' ' . $freshApplication->student->last_name),
                'email' => $schoolEmail,
                'password' => Hash::make(Str::random(40)),
                'phone' => $freshApplication->student->phone,
                'is_admin' => false,
                'account_type' => 'student',
                'is_active' => false,
            ]);

            $activationToken = Str::random(64);
            $freshApplication->update([
                'user_id' => $user->id,
                'school_email' => $schoolEmail,
                'status' => 'account_created',
                'paid_at' => $freshApplication->paid_at ?? now(),
                'activation_token_hash' => hash('sha256', $activationToken),
                'activation_token_expires_at' => now()->addHours(max(1, (int) config('student_onboarding.activation_link_ttl_hours', 24))),
            ]);

            $freshApplication->student->update([
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            $this->sendOnboardingEmail($freshApplication->fresh(), $activationToken);
        });
    }

    private function sendOnboardingEmail(StudentApplication $application, string $activationToken): void
    {
        if (empty($application->personal_email) || empty($application->school_email)) {
            return;
        }

        $baseUrl = rtrim((string) config('student_onboarding.frontend_url', config('app.url')), '/');
        $activationUrl = $baseUrl . '/activate-account?token=' . urlencode($activationToken);

        try {
            $expiresInHours = max(1, (int) config('student_onboarding.activation_link_ttl_hours', 24));

            Mail::to($application->personal_email)->queue(
                new StudentOnboardingMail(
                    schoolEmail: $application->school_email,
                    activationUrl: $activationUrl,
                    expiresInHours: $expiresInHours
                )
            );

            $application->update([
                'onboarding_sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to send student onboarding email', [
                'application_uuid' => $application->uuid,
                'personal_email' => $application->personal_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateUniqueSchoolEmail(string $firstName, string $lastName): string
    {
        $domain = trim((string) config('student_onboarding.school_email_domain', 'starlight.edu.kh'));
        $baseLocalPart = trim($this->normalizeEmailPart($firstName) . '.' . $this->normalizeEmailPart($lastName), '.');
        if ($baseLocalPart === '') {
            $baseLocalPart = 'student';
        }

        $candidateLocalPart = $baseLocalPart;
        $counter = 1;

        while (User::where('email', $candidateLocalPart . '@' . $domain)->exists()) {
            $candidateLocalPart = $baseLocalPart . $counter;
            $counter++;
        }

        return $candidateLocalPart . '@' . $domain;
    }

    private function normalizeEmailPart(string $value): string
    {
        $ascii = Str::ascii(Str::lower($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '.', $ascii) ?? '';

        return trim($normalized, '.');
    }

    private function resolvePaymentPlan(string $paymentPlan): array
    {
        $normalizedPlan = strtolower(trim($paymentPlan));
        $planMonths = match ($normalizedPlan) {
            'half_year' => 6,
            'yearly' => 12,
            default => 1,
        };
        $paymentPeriod = $normalizedPlan === 'yearly' ? 'yearly' : 'monthly';
        $baseAmount = self::BASE_MONTHLY_TUITION * $planMonths;
        $discountAmount = $normalizedPlan === 'yearly'
            ? round($baseAmount * self::YEARLY_DISCOUNT_RATE, 2)
            : 0.00;

        return [
            'months' => $planMonths,
            'payment_period' => $paymentPeriod,
            'amount' => number_format($baseAmount, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
        ];
    }

    private function hasDuplicateIdentity(array $payload): bool
    {
        return Student::query()
            ->where('first_name', $payload['first_name'])
            ->where('last_name', $payload['last_name'])
            ->whereDate('date_of_birth', $payload['date_of_birth'])
            ->where('parent_phone', $payload['parent_phone'])
            ->whereNull('deleted_at')
            ->exists();
    }

    private function generateStudentCode(string $academicYear): string
    {
        $year = substr($academicYear, 0, 4);
        $lastStudent = Student::where('student_code', 'LIKE', $year . '-%')
            ->orderBy('student_code', 'desc')
            ->first();

        $sequence = $lastStudent ? ((int) substr($lastStudent->student_code, 5) + 1) : 1;

        return $year . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
