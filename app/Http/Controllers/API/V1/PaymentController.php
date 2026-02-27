<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaywayTransaction;
use App\Models\PaywayPushback;
use App\Services\PaywayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    private const CANONICAL_PAYMENT_CHECKOUT_SESSION_PATH = '/api/v1/payments/{payment_uuid}/checkout-sessions';
    private const CANONICAL_PAYMENT_RESOURCE_PATH = '/api/v1/payments/{payment_uuid}';
    private const CANONICAL_PAYWAY_WEBHOOK_PATH = '/api/v1/webhooks/payway';
    private const SUNSET_AT = '2026-06-30 23:59:59 UTC';

    protected $paywayService;

    public function __construct(PaywayService $paywayService)
    {
        $this->paywayService = $paywayService;
    }

    /**
     * Legacy endpoint: payment UUID in request body.
     * Successor: POST /api/v1/payments/{payment_uuid}/checkout-sessions
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

        $response = $this->handleGenerateKHQR($request->payment_uuid, [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return $this->withDeprecationHeaders($response, self::CANONICAL_PAYMENT_CHECKOUT_SESSION_PATH);
    }

    /**
     * Deprecated alias endpoint.
     * Successor: POST /api/v1/payments/{payment_uuid}/checkout-sessions
     */
    public function generateKHQRForPayment(Request $request, string $payment_uuid)
    {
        $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $response = $this->handleGenerateKHQR($payment_uuid, [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return $this->withDeprecationHeaders($response, self::CANONICAL_PAYMENT_CHECKOUT_SESSION_PATH);
    }

    /**
     * Canonical endpoint for creating a payment checkout session.
     */
    public function createCheckoutSession(Request $request, string $payment_uuid)
    {
        $request->validate([
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        return $this->handleGenerateKHQR($payment_uuid, [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);
    }

    /**
     * Canonical webhook endpoint.
     */
    public function webhookCanonical(Request $request)
    {
        return $this->handleWebhook($request);
    }

    /**
     * Deprecated webhook alias endpoint.
     * Successor: POST /api/v1/webhooks/payway
     */
    public function webhook(Request $request)
    {
        $response = $this->handleWebhook($request);

        return $this->withDeprecationHeaders($response, self::CANONICAL_PAYWAY_WEBHOOK_PATH);
    }

    private function handleWebhook(Request $request): Response
    {
        $signatureHeader = (string) config('payway.webhook.signature_header', 'X-PayWay-Signature');
        $providedSignature = $request->header($signatureHeader);
        $signatureValid = $this->paywayService->verifyWebhookSignature($request->getContent(), $providedSignature);
        $signatureRequired = (bool) config('payway.webhook.require_signature', false);
        $normalizedStatus = $this->paywayService->extractStatusCode($request->all())
            ?? (is_scalar($request->status) ? (string) $request->status : null);
        $statusMessage = $request->status_message
            ?? (is_array($request->status) ? ($request->status['message'] ?? null) : null);

        $validated = validator($request->all(), [
            'tran_id' => ['required', 'string'],
            'status' => ['required'],
            'return_params' => ['nullable', 'string'],
            'apv' => ['nullable', 'string'],
            'status_message' => ['nullable', 'string'],
        ]);

        if ($validated->fails()) {
            Log::warning('PayWay webhook rejected: invalid payload', [
                'errors' => $validated->errors()->toArray(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'success']);
        }

        if ($signatureRequired && !$signatureValid) {
            Log::warning('PayWay webhook rejected: invalid signature', [
                'tran_id' => $request->tran_id,
                'signature_header' => $signatureHeader,
            ]);

            return response()->json(['status' => 'success']);
        }

        if ($normalizedStatus === null || $normalizedStatus === '') {
            Log::warning('PayWay webhook rejected: unable to normalize status', [
                'tran_id' => $request->tran_id,
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'success']);
        }

        if (!$signatureValid && !empty($providedSignature)) {
            Log::warning('PayWay webhook signature mismatch (continuing because signature is optional)', [
                'tran_id' => $request->tran_id,
                'signature_header' => $signatureHeader,
            ]);
        }

        DB::beginTransaction();
        try {
            // Create pushback record
            $pushback = PaywayPushback::create([
                'tran_id' => $request->tran_id,
                'apv' => $request->apv,
                'status' => $normalizedStatus,
                'status_message' => $statusMessage,
                'return_params' => $request->return_params,
                'data' => array_merge($request->all(), [
                    '_security' => [
                        'signature_header' => $signatureHeader,
                        'signature_present' => !empty($providedSignature),
                        'signature_valid' => $signatureValid,
                    ],
                ]),
            ]);

            // Resolve transaction/payment primarily by return_params, then fallback to tran_id.
            $returnParams = $pushback->getReturnParameters();
            $transaction = null;
            $payment = null;

            if (
                is_array($returnParams)
                && isset($returnParams['transaction_uuid'], $returnParams['payment_uuid'])
            ) {
                $transaction = PaywayTransaction::where('uuid', $returnParams['transaction_uuid'])
                    ->lockForUpdate()
                    ->first();

                $payment = Payment::where('uuid', $returnParams['payment_uuid'])
                    ->lockForUpdate()
                    ->first();
            }

            if (!$transaction || !$payment) {
                $transaction = PaywayTransaction::where('tran_id', $request->tran_id)
                    ->lockForUpdate()
                    ->latest('id')
                    ->firstOrFail();

                $payment = Payment::where('id', $transaction->payment_id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if (!empty($transaction->payment_id) && (int) $transaction->payment_id !== (int) $payment->id) {
                throw new \RuntimeException('Transaction-payment mismatch');
            }

            // Idempotency/replay guard: if already finalized, keep state unchanged.
            if ($payment->status === 'paid' || $transaction->status === 'success') {
                if ((int) ($transaction->pushback_id ?? 0) !== (int) $pushback->id) {
                    $transaction->update([
                        'pushback_id' => $pushback->id,
                    ]);
                }

                DB::commit();

                Log::info('PayWay webhook duplicate ignored', [
                    'tran_id' => $request->tran_id,
                    'payment_uuid' => $payment->uuid,
                    'transaction_uuid' => $transaction->uuid,
                    'apv' => $request->apv,
                ]);

                return response()->json(['status' => 'success']);
            }

            // Check if payment is successful
            $isSuccessful = $pushback->isSuccessful();

            $canVerifyWithGateway = !empty(config('payway.api_key')) && !empty(config('payway.merchant_id'));
            $shouldVerifyWithGateway = $isSuccessful
                && (bool) config('payway.webhook.verify_with_check_transaction', true)
                && $canVerifyWithGateway;

            if ($isSuccessful && !$canVerifyWithGateway) {
                Log::warning('PayWay webhook check-transaction verification skipped: missing credentials', [
                    'tran_id' => $transaction->tran_id,
                    'payment_uuid' => $payment->uuid,
                ]);
            }

            if ($shouldVerifyWithGateway) {
                try {
                    $verificationResponse = $this->paywayService->checkTransactionStatus($transaction->tran_id);

                    if (!$this->paywayService->isSuccessfulTransactionResponse($verificationResponse)) {
                        if ($this->paywayService->isPendingTransactionResponse($verificationResponse)) {
                            Log::warning('PayWay check-transaction still pending; accepting webhook success', [
                                'tran_id' => $transaction->tran_id,
                                'payment_uuid' => $payment->uuid,
                                'webhook_status' => $normalizedStatus,
                                'check_transaction_response' => $verificationResponse,
                            ]);
                        } else {
                            Log::warning('PayWay webhook success rejected by check-transaction verification', [
                                'tran_id' => $transaction->tran_id,
                                'payment_uuid' => $payment->uuid,
                                'webhook_status' => $normalizedStatus,
                                'check_transaction_response' => $verificationResponse,
                            ]);

                            $transaction->update([
                                'status' => 'processing',
                                'pushback_id' => $pushback->id,
                            ]);

                            DB::commit();

                            return response()->json(['status' => 'success']);
                        }
                    }
                } catch (\Throwable $verificationError) {
                    Log::warning('PayWay check-transaction verification failed; continuing with webhook status', [
                        'tran_id' => $transaction->tran_id,
                        'payment_uuid' => $payment->uuid,
                        'error' => $verificationError->getMessage(),
                    ]);
                }
            }

            if ($isSuccessful) {
                // Success path
                $transaction->markAsSuccess($request->apv, $pushback);

                // Update payment record
                $payment->update([
                    'status' => 'paid',
                    'khqr_reference' => $request->apv,
                    'paid_at' => now(),
                    'payment_date' => now(),
                    'payment_method' => PaymentMethod::BAKONG->value,
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

            Log::error('PayWay webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            // Still return success to PayWay to avoid retries
            return response()->json(['status' => 'success']);
        }
    }

    /**
     * Legacy endpoint: payment UUID in request body.
     * Successor: GET /api/v1/payments/{payment_uuid}
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'payment_uuid' => 'required|string',
        ]);

        $response = $this->handleCheckStatus($request->payment_uuid);

        return $this->withDeprecationHeaders($response, self::CANONICAL_PAYMENT_RESOURCE_PATH);
    }

    /**
     * Deprecated alias endpoint.
     * Successor: GET /api/v1/payments/{payment_uuid}
     */
    public function checkPaymentStatus(string $payment_uuid)
    {
        $response = $this->handleCheckStatus($payment_uuid);

        return $this->withDeprecationHeaders($response, self::CANONICAL_PAYMENT_RESOURCE_PATH);
    }

    /**
     * Canonical payment resource read endpoint.
     */
    public function showPayment(string $payment_uuid)
    {
        return $this->handleCheckStatus($payment_uuid);
    }

    private function handleGenerateKHQR(string $paymentUuid, array $customerData): Response
    {
        try {
            $payment = Payment::where('uuid', $paymentUuid)->firstOrFail();

            // Check if payment is already paid
            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been completed',
                ], 400);
            }

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

    private function handleCheckStatus(string $paymentUuid): Response
    {
        try {
            $payment = Payment::where('uuid', $paymentUuid)
                ->with('paywayTransaction')
                ->firstOrFail();

            $this->reconcilePaymentStatusFromGateway($payment);
            $payment->refresh()->load('paywayTransaction');
            $includeRawQrInStatus = (bool) config('payway.response.include_raw_qr_in_status', false);

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
                        'qr_url' => $includeRawQrInStatus ? $payment->paywayTransaction->qr_url : null,
                        'deeplink' => $includeRawQrInStatus ? $payment->paywayTransaction->deeplink : null,
                        'expires_at' => $payment->paywayTransaction->expires_at,
                        'has_qr_data' => !empty($payment->paywayTransaction->qr_url),
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

    /**
     * Fallback reconciliation when webhook is delayed/missed:
     * query PayWay check-transaction and mark local payment as paid when confirmed.
     */
    private function reconcilePaymentStatusFromGateway(Payment $payment): void
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
                    'payment_date' => $lockedPayment->payment_date ?? now(),
                    'payment_method' => PaymentMethod::BAKONG->value,
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('PayWay status reconciliation skipped (check-transaction failed)', [
                'payment_uuid' => $payment->uuid,
                'tran_id' => $transaction->tran_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function withDeprecationHeaders(Response $response, string $replacementPath): Response
    {
        $sunset = gmdate('D, d M Y H:i:s \G\M\T', strtotime(self::SUNSET_AT));

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', $sunset);
        $response->headers->set('Link', sprintf('<%s>; rel="successor-version"', $replacementPath));

        return $response;
    }
}
