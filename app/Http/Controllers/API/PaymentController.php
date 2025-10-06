<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaywayTransaction;
use App\Models\PaywayPushback;
use App\Services\PaywayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected $paywayService;

    public function __construct(PaywayService $paywayService)
    {
        $this->paywayService = $paywayService;
    }

    /**
     * Generate KHQR for a payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateKHQR(Request $request)
    {
        $request->validate([
            'payment_uuid' => 'required|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        try {
            $payment = Payment::where('uuid', $request->payment_uuid)->firstOrFail();

            // Check if payment is already paid
            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been completed',
                ], 400);
            }

            $customerData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
            ];

            $result = $this->paywayService->generateKHQR($payment, $customerData);

            if (!$result['success']) {
                return response()->json($result, 500);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate KHQR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle webhook from PayWay (pushback)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        DB::beginTransaction();

        try {
            // Create pushback record
            $pushback = PaywayPushback::create([
                'tran_id' => $request->tran_id,
                'apv' => $request->apv,
                'status' => $request->status,
                'status_message' => $request->status_message ?? null,
                'return_params' => $request->return_params,
                'data' => $request->all(),
            ]);

            // Extract return parameters
            $returnParams = $pushback->getReturnParameters();

            if (empty($returnParams) || !isset($returnParams['transaction_uuid']) || !isset($returnParams['payment_uuid'])) {
                throw new \Exception('Invalid or missing return parameters');
            }

            // Find PayWay transaction
            $transaction = PaywayTransaction::where('uuid', $returnParams['transaction_uuid'])->firstOrFail();

            // Find Payment
            $payment = Payment::where('uuid', $returnParams['payment_uuid'])->firstOrFail();

            // Check if payment is successful
            $isSuccessful = $pushback->isSuccessful();

            if ($isSuccessful) {
                // Success path
                $transaction->markAsSuccess($request->apv, $pushback);

                // Update payment record
                $payment->update([
                    'status' => 'paid',
                    'khqr_reference' => $request->apv,
                    'paid_at' => now(),
                    'payment_date' => now(),
                    'payment_method' => 'KHQR',
                ]);
            } else {
                // Failure path
                $transaction->markAsFailed($pushback);

                $payment->update([
                    'status' => 'pending', // Keep as pending so user can retry
                ]);
            }

            DB::commit();

            // Always return success to PayWay
            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Still return success to PayWay to avoid retries
            return response()->json(['status' => 'success']);
        }
    }

    /**
     * Check payment status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
