<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Services\PaywayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestKhqrController extends Controller
{
    protected $paywayService;

    public function __construct(PaywayService $paywayService)
    {
        $this->paywayService = $paywayService;
    }

    /**
     * Show the KHQR test page
     */
    public function index()
    {
        return view('test.khqr');
    }

    /**
     * Create a test payment
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'student_id' => 'nullable|exists:students,id',
        ]);

        try {
            DB::beginTransaction();

            // Get or create a test student
            $studentId = $request->student_id;

            if (!$studentId) {
                // Create a test student if none exists
                $student = Student::firstOrCreate(
                    ['email' => 'test@example.com'],
                    [
                        'first_name' => 'Test',
                        'last_name' => 'Student',
                        'date_of_birth' => '2000-01-01',
                        'gender' => 'male',
                        'shift' => 'morning',
                        'registration_date' => now(),
                        'academic_year' => '2025-2026',
                        'parent_name' => 'Test Parent',
                        'parent_phone' => '012345678',
                    ]
                );
                $studentId = $student->id;
            }

            // Create payment
            $payment = Payment::create([
                'student_id' => $studentId,
                'amount' => $request->amount,
                'payment_type' => 'registration_fee',
                'payment_period' => 'one_time',
                'payment_method' => 'KHQR',
                'due_date' => now()->addDays(7),
                'status' => 'pending',
                'description' => $request->description ?? 'Test Payment - ' . now()->format('Y-m-d H:i:s'),
                'academic_year' => '2025-2026',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'payment' => [
                    'uuid' => $payment->uuid,
                    'payment_code' => $payment->payment_code,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'description' => $payment->description,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Test Payment Creation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate KHQR for a payment
     */
    public function generateQR(Request $request)
    {
        $request->validate([
            'payment_uuid' => 'required|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        try {
            $payment = Payment::where('uuid', $request->payment_uuid)->firstOrFail();

            // Check if payment is already paid
            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been completed',
                ], 400);
            }

            $customerData = [
                'first_name' => $request->first_name ?? 'Test',
                'last_name' => $request->last_name ?? 'User',
                'phone' => $request->phone ?? '012345678',
            ];

            $result = $this->paywayService->generateKHQR($payment, $customerData);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Test KHQR Generation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate KHQR: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'payment_uuid' => 'required|string',
        ]);

        try {
            $payment = Payment::where('uuid', $request->payment_uuid)
                ->with('paywayTransaction')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_uuid' => $payment->uuid,
                    'payment_code' => $payment->payment_code,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'paid_at' => $payment->paid_at,
                    'transaction' => $payment->paywayTransaction ? [
                        'status' => $payment->paywayTransaction->status,
                        'qr_url' => $payment->paywayTransaction->qr_url,
                        'deeplink' => $payment->paywayTransaction->deeplink,
                        'expires_at' => $payment->paywayTransaction->expires_at,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }
    }
}
