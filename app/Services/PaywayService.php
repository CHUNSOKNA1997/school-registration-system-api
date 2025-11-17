<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaywayTransaction;
use Illuminate\Support\Facades\Http;

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
                    'tran_id' => $payment->payment_code,
                    'amount' => $payment->amount,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(config('payway.khqr.qr_expiry_minutes', 15)),
                ]
            );

            // Use Unix timestamp (same as Sakal)
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

            // Payment option for KHQR (same as Sakal)
            $paymentOption = config('payway.khqr.payment_option_code', 'abapay');

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

            // Prepare API request data (same as Sakal's purchase endpoint)
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
                'type' => 'purchase',
                'return_url' => $returnUrl,
                'continue_success_url' => $continueUrl,
                'return_deeplink' => $returnDeeplink,
                'currency' => 'USD',
                'custom_fields' => '',
                'return_params' => $returnParams,
                'hash' => $hash,
            ];

            // Call PayWay purchase API (works for KHQR too)
            $response = $this->callPaywayAPI($requestData, false);

            // Update transaction with QR data
            if (isset($response['qrString']) || isset($response['qrImage']) || isset($response['abapay_deeplink'])) {
                $transaction->update([
                    'status' => 'processing',
                    'qr_string' => $response['qrString'] ?? null,
                    'qr_url' => $response['qrImage'] ?? null,
                    'deeplink' => $response['abapay_deeplink'] ?? null,
                ]);
            }

            // Construct checkout_qr_url like Sakal does
            $checkoutData = [
                'status' => $response['status'] ?? [
                    'code' => '00',
                    'message' => 'Success!',
                    'lang' => 'en'
                ],
                'step' => 'abapay_khqr_request_qr',
                'qr_string' => $response['qrString'] ?? '',
                'transaction_summary' => [
                    'order_details' => [
                        'subtotal' => $payment->amount,
                        'vat_enabled' => '0',
                        'vat' => '0',
                        'shipping' => 0,
                        'vat_amount' => 0,
                        'transaction_fee' => 0,
                        'total' => $payment->amount,
                        'currency' => 'USD'
                    ],
                    'merchant' => [
                        'name' => config('app.name', 'School Registration System'),
                        'logo' => '',
                        'primary_color' => '#201B44',
                        'cancel_url' => '',
                        'themes' => 'default',
                        'font_family' => 'SF_Pro_Display',
                        'font_size' => 14,
                        'bg_color' => '#ffffff',
                        'border_radius' => 6
                    ]
                ],
                'payment_options' => [
                    'abapay' => [
                        'label' => 'ABA Pay'
                    ]
                ],
                'expire_in' => strtotime($transaction->expires_at),
                'expire_in_sec' => '900',
                'render_qr_page' => 1
            ];

            $checkoutQrUrl = 'https://checkout-sandbox.payway.com.kh/' . base64_encode(json_encode($checkoutData));

            return [
                'success' => true,
                'transaction_uuid' => $transaction->uuid,
                'tran_id' => $transaction->tran_id,
                'amount' => $payment->amount,
                'qr_string' => $response['qrString'] ?? null,
                'qr_url' => $response['qrImage'] ?? null,
                'deeplink' => $response['abapay_deeplink'] ?? null,
                'checkout_qr_url' => $checkoutQrUrl,
                'expires_at' => $transaction->expires_at,
                'payment_code' => $payment->payment_code,
                // For hosted checkout iframe
                'checkout_form_data' => $this->generateHostedCheckoutFormData($requestData),
                'hosted_checkout_url' => url("/payway/checkout/{$payment->uuid}"),
            ];
        } catch (\Exception $e) {
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
         $email, $phone, $paymentOption, $callbackUrl, $continueUrl, $returnDeeplink, $returnParams] = $params;

        // Additional parameters for hash (matching Sakal's format)
        $type = 'purchase';
        $currency = 'USD';
        $customFields = '';
        $payout = '';
        $lifetime = '';
        $additionalParams = '';
        $googlePayToken = '';
        $cancelUrl = '';

        /**
         * Hash format
         */
        $dataToHash = $reqTime . $merchantId . $transactionId . $amount . $items .
                     $shipping . $firstName . $lastName . $email . $phone .
                     $type . $paymentOption . $callbackUrl . $cancelUrl . $continueUrl .
                     $returnDeeplink . $currency . $customFields . $returnParams . $payout .
                     $lifetime . $additionalParams . $googlePayToken;

        return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
    }

    /**
     * Call PayWay API
     *
     * @param array $data
     * @param bool $useQrEndpoint Use QR endpoint (true) or purchase endpoint (false)
     * @return array
     */
    private function callPaywayAPI(array $data, bool $useQrEndpoint = true): array
    {
        // Use QR API endpoint for KHQR generation
        $apiUrl = $useQrEndpoint
            ? config('payway.qr_api_url')
            : config('payway.api_url');

        // Both endpoints expect form data for checkout_qr_url to be returned
        $response = Http::asForm()->post($apiUrl, $data);

        if ($response->failed()) {
            throw new \Exception('PayWay API request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (!$responseData) {
            throw new \Exception('Invalid response from PayWay API');
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

    /**
     * Generate form data for PayWay hosted checkout
     * Returns the data that should be POSTed to PayWay's checkout page
     *
     * @param array $requestData
     * @return array
     */
    private function generateHostedCheckoutFormData(array $requestData): array
    {
        // Return only the fields needed for form-based checkout
        return [
            'req_time' => $requestData['req_time'],
            'merchant_id' => $requestData['merchant_id'],
            'tran_id' => $requestData['tran_id'],
            'amount' => $requestData['amount'],
            'firstname' => $requestData['firstname'],
            'lastname' => $requestData['lastname'],
            'phone' => $requestData['phone'],
            'email' => $requestData['email'],
            'items' => $requestData['items'],
            'shipping' => $requestData['shipping'],
            'payment_option' => $requestData['payment_option'],
            'type' => 'purchase',
            'return_url' => $requestData['return_url'],
            'continue_success_url' => $requestData['continue_success_url'],
            'return_deeplink' => $requestData['return_deeplink'] ?? '',
            'currency' => 'USD',
            'custom_fields' => '',
            'return_params' => $requestData['return_params'],
            'hash' => $requestData['hash'],
        ];
    }

    /**
     * Generate hosted payment form data (for iframe approach like Sakal)
     *
     * @param Payment $payment
     * @param array $customerData
     * @return array
     */
    public function generateHostedPaymentForm(Payment $payment, array $customerData = []): array
    {
        // Create or update Payway transaction
        $transaction = PaywayTransaction::updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'tran_id' => $payment->payment_code,
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
        $paymentOption = config('payway.khqr.payment_option_code', 'abapay');

        // Callback URLs
        $returnUrl = PaywayCallbackService::getCallbackUrl('/api/payway/webhook');
        $continueUrl = url('/payment/success');

        // Return parameters
        $returnParams = base64_encode(json_encode([
            'transaction_uuid' => $transaction->uuid,
            'payment_uuid' => $payment->uuid,
        ]));

        // Generate hash (without return_deeplink for hosted page)
        $hash = $this->generateHashForHostedPage(
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
            $returnParams
        );

        return [
            'hash' => $hash,
            'req_time' => $reqTime,
            'merchant_id' => config('payway.merchant_id'),
            'tran_id' => $transaction->tran_id,
            'amount' => $amount,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'items' => $items,
            'shipping' => $shipping,
            'payment_option' => $paymentOption,
            'type' => 'purchase',
            'return_url' => $returnUrl,
            'continue_success_url' => $continueUrl,
            'currency' => 'USD',
            'custom_fields' => '',
            'return_params' => $returnParams,
            'qr_image_template' => config('payway.khqr.qr_image_template', 'template3_color'),
        ];
    }

    /**
     * Generate hash for hosted payment page
     */
    private function generateHashForHostedPage(...$params): string
    {
        $apiKey = config('payway.api_key');
        $merchantId = config('payway.merchant_id');

        [$reqTime, $transactionId, $amount, $items, $shipping, $firstName, $lastName,
         $email, $phone, $paymentOption, $returnUrl, $continueUrl, $returnParams] = $params;

        // Additional parameters (empty strings as defaults)
        $type = 'purchase';
        $cancelUrl = '';
        $returnDeeplink = '';
        $currency = 'USD';
        $customFields = '';
        $payout = '';
        $lifetime = '';
        $additionalParams = '';
        $googlePayToken = '';

        // Hash format matching Sakal's PaywayManager EXACTLY
        $dataToHash = $reqTime . $merchantId . $transactionId . $amount .
                     $items . $shipping . $firstName . $lastName . $email . $phone .
                     $type . $paymentOption . $returnUrl . $cancelUrl . $continueUrl .
                     $returnDeeplink . $currency . $customFields . $returnParams . $payout .
                     $lifetime . $additionalParams . $googlePayToken;

        return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
    }
}
