<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaywayTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaywayService
{
    /**
     * Generate KHQR for payment
     *
     * @param Payment $payment
     * @param array $customerData
     * @return array
     */
    public function generateKHQR(Payment $payment, array $customerData = []): array
    {
        try {
            // Create or update Payway transaction
            $transaction = PaywayTransaction::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'tran_id' => $payment->payment_code, // Use payment code as transaction ID
                    'amount' => $payment->amount,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(config('payway.khqr.qr_expiry_minutes', 15)),
                ]
            );

            $reqTime = time();
            $amount = $payment->amount;
            $shipping = 0;

            // Prepare items data
            $items = $this->prepareItemsData($payment);

            // Customer information
            $firstName = $customerData['first_name'] ?? $payment->student->first_name ?? '';
            $lastName = $customerData['last_name'] ?? $payment->student->last_name ?? '';
            $email = $customerData['email'] ?? '';
            $phone = $customerData['phone'] ?? $payment->student->phone ?? '';

            // Payment option for KHQR
            $paymentOption = config('payway.khqr.payment_option_code', 'khqr');

            // Callback URLs - Use PaywayCallbackService for smart URL resolution
            $returnUrl = PaywayCallbackService::getCallbackUrl('/api/payway/webhook');
            $continueUrl = url('/payment/success');
            $androidScheme = url('/payment/success');
            $iosScheme = url('/payment/success');
            $returnDeeplink = base64_encode(json_encode([
                'android_scheme' => $androidScheme,
                'ios_scheme' => $iosScheme,
            ]));

            // Return parameters (will be sent back in webhook)
            $returnParams = base64_encode(json_encode([
                'transaction_uuid' => $transaction->uuid,
                'payment_uuid' => $payment->uuid,
            ]));

            // Generate hash
            $hash = $this->generateHashForKHQR(
                $reqTime,
                $transaction->tran_id,
                $amount,
                $items,
                $shipping,
                $firstName,
                $lastName,
                $email,
                $phone,
                $paymentOption,
                $returnUrl,
                $continueUrl,
                $returnDeeplink,
                $returnParams
            );

            // Prepare API request data
            $requestData = [
                'req_time' => $reqTime,
                'merchant_id' => config('payway.merchant_id'),
                'tran_id' => $transaction->tran_id,
                'amount' => $amount,
                'items' => $items,
                'shipping' => $shipping,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'payment_option' => $paymentOption,
                'return_url' => $returnUrl,
                'continue_success_url' => $continueUrl,
                'return_deeplink' => $returnDeeplink,
                'return_params' => $returnParams,
                'hash' => $hash,
            ];

            // Call PayWay API
            $response = $this->callPaywayAPI($requestData);

            // Update transaction with QR data
            if (isset($response['qr']) || isset($response['abapay_deeplink'])) {
                $transaction->update([
                    'status' => 'processing',
                    'qr_string' => $response['qr'] ?? null,
                    'qr_url' => $response['qr'] ?? null,
                    'deeplink' => $response['abapay_deeplink'] ?? null,
                ]);
            }

            return [
                'success' => true,
                'transaction_uuid' => $transaction->uuid,
                'qr_string' => $response['qr'] ?? null,
                'qr_url' => $response['qr'] ?? null,
                'deeplink' => $response['abapay_deeplink'] ?? null,
                'expires_at' => $transaction->expires_at,
                'payment_code' => $payment->payment_code,
            ];
        } catch (\Exception $e) {
            Log::error('KHQR Generation Failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate KHQR: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate hash for KHQR
     *
     * @param mixed ...$params
     * @return string
     */
    private function generateHashForKHQR(...$params): string
    {
        $apiKey = config('payway.api_key');
        $merchantId = config('payway.merchant_id');

        [$reqTime, $transactionId, $amount, $items, $shipping, $firstName, $lastName,
         $email, $phone, $paymentOption, $returnUrl, $continueUrl, $returnDeeplink, $returnParams] = $params;

        $dataToHash = $reqTime . $merchantId . $transactionId . $amount . $items .
                     $shipping . $firstName . $lastName . $email . $phone .
                     $paymentOption . $returnUrl . $continueUrl . $returnDeeplink . $returnParams;

        return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
    }

    /**
     * Call PayWay API
     *
     * @param array $data
     * @return array
     */
    private function callPaywayAPI(array $data): array
    {
        $apiUrl = config('payway.api_url');

        $response = Http::asForm()->post($apiUrl, $data);

        if ($response->failed()) {
            throw new \Exception('PayWay API request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (!$responseData) {
            throw new \Exception('Invalid response from PayWay API');
        }

        // Log the response for debugging
        if (config('payway.log_all_events')) {
            Log::info('PayWay API Response', $responseData);
        }

        return $responseData;
    }

    /**
     * Prepare items data for PayWay
     *
     * @param Payment $payment
     * @return string
     */
    private function prepareItemsData(Payment $payment): string
    {
        $items = [
            [
                'name' => $payment->description ?? 'Payment for ' . $payment->payment_code,
                'price' => $payment->amount,
                'quantity' => 1,
            ]
        ];

        return base64_encode(json_encode($items));
    }

    /**
     * Check transaction status with PayWay
     *
     * @param string $tranId
     * @return array
     */
    public function checkTransactionStatus(string $tranId): array
    {
        $reqTime = time();
        $hash = $this->generateCheckTransactionHash($tranId, $reqTime);

        $requestData = [
            'req_time' => $reqTime,
            'merchant_id' => config('payway.merchant_id'),
            'tran_id' => $tranId,
            'hash' => $hash,
        ];

        $apiUrl = config('payway.check_transaction_api_url');
        $response = Http::asForm()->post($apiUrl, $requestData);

        return $response->json() ?? [];
    }

    /**
     * Generate hash for transaction check
     *
     * @param string $tranId
     * @param int $reqTime
     * @return string
     */
    private function generateCheckTransactionHash(string $tranId, int $reqTime): string
    {
        $apiKey = config('payway.api_key');
        $merchantId = config('payway.merchant_id');

        $dataToHash = $reqTime . $merchantId . $tranId;

        return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
    }
}
