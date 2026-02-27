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
        if ($this->isHostedCheckoutMode()) {
            return $this->buildHostedCheckoutResult($payment, $customerData);
        }

        try {
            $tranId = $this->generateTranId($payment);

            // Create or update Payway transaction
            $transaction = PaywayTransaction::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'tran_id' => $tranId,
                    'amount' => $payment->amount,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(config('payway.khqr.qr_expiry_minutes', 15)),
                ]
            );

            // PayWay expects request time in YYYYMMDDHHMMSS format.
            $reqTime = $this->buildRequestTime();
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
            $returnUrl = PaywayCallbackService::getCallbackUrl('/api/v1/payway/webhook');
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
                'qr_image_template' => config('payway.khqr.qr_image_template', 'template3_color'),
                'hash' => $hash,
            ];

            // Prefer QR endpoint for KHQR API response (JSON qrString/qrImage).
            $khqrApiMode = strtolower((string) config('payway.khqr.api_mode', 'qr'));
            $useQrEndpoint = $khqrApiMode !== 'purchase';
            try {
                $response = $this->callPaywayAPI($requestData, $useQrEndpoint);
            } catch (\Throwable $e) {
                if ($this->shouldFallbackToHostedCheckout($e)) {
                    return $this->buildHostedCheckoutResult($payment, $customerData);
                }

                throw $e;
            }

            // Update transaction with QR data
            if (isset($response['qrString']) || isset($response['qrImage']) || isset($response['abapay_deeplink'])) {
                $transaction->update([
                    'status' => 'processing',
                    'qr_string' => $response['qrString'] ?? null,
                    'qr_url' => $response['qrImage'] ?? null,
                    'deeplink' => $response['abapay_deeplink'] ?? null,
                ]);
            }

            $checkoutQrUrl = $this->buildCheckoutQrUrl(
                $payment,
                $transaction,
                $response['qrString'] ?? null,
                $response['status'] ?? null
            );

            if (empty($checkoutQrUrl)) {
                throw new \Exception('Failed to build checkout_qr_url from QR response');
            }

            $includeRawQr = (bool) config('payway.response.include_raw_qr', false);
            $includeCheckoutQrUrl = (bool) config('payway.response.include_checkout_qr_url', false);

            return [
                'success' => true,
                'mode' => 'qr_api',
                'transaction_uuid' => $transaction->uuid,
                'tran_id' => $transaction->tran_id,
                'amount' => $payment->amount,
                'qr_string' => $includeRawQr ? ($response['qrString'] ?? null) : null,
                'qr_url' => $includeRawQr ? ($response['qrImage'] ?? null) : null,
                'deeplink' => $includeRawQr ? ($response['abapay_deeplink'] ?? null) : null,
                'qr_currency_code' => $this->extractQrCurrencyCode($response['qrString'] ?? null),
                'checkout_qr_url' => $includeCheckoutQrUrl ? $checkoutQrUrl : null,
                'expires_at' => $transaction->expires_at,
                'payment_code' => $payment->payment_code,
                'hosted_checkout_url' => $checkoutQrUrl,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate KHQR: ' . $e->getMessage(),
            ];
        }
    }

    private function isHostedCheckoutMode(): bool
    {
        $mode = strtolower((string) config('payway.khqr.api_mode', 'qr'));

        return in_array($mode, ['purchase', 'hosted', 'checkout'], true);
    }

    private function shouldFallbackToHostedCheckout(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'wrong hash')
            || str_contains($message, 'invalid response from payway api')
            || str_contains($message, 'unsupported media type')
            || str_contains($message, 'end of api lifetime')
            || str_contains($message, 'http 403')
            || str_contains($message, 'http 415');
    }

    private function buildHostedCheckoutResult(Payment $payment, array $customerData = []): array
    {
        $formData = $this->generateHostedPaymentForm($payment, $customerData);
        $transaction = PaywayTransaction::where('payment_id', $payment->id)->first();
        $purchasePayload = $this->buildPurchasePayloadWithCurrency(
            $payment,
            $transaction,
            $customerData,
            $formData
        );

        try {
            // First try full payload with explicit currency=USD.
            $purchaseResponse = $this->callPurchaseForQr($purchasePayload);
        } catch (\Throwable) {
            // Compatibility fallback: simple sample payload.
            $purchaseResponse = $this->callPurchaseForQr($formData);
        }

        if ($transaction) {
            $transaction->update([
                'status' => 'processing',
                'qr_string' => $purchaseResponse['qrString'] ?? null,
                'qr_url' => $purchaseResponse['qrImage'] ?? null,
                'deeplink' => $purchaseResponse['abapay_deeplink'] ?? null,
            ]);
        }

        $checkoutQrUrl = $this->buildCheckoutQrUrl(
            $payment,
            $transaction,
            $purchaseResponse['qrString'] ?? null,
            $purchaseResponse['status'] ?? null
        );

        if (empty($checkoutQrUrl)) {
            throw new \Exception('Failed to build checkout_qr_url from purchase response');
        }
        $includeRawQr = (bool) config('payway.response.include_raw_qr', false);
        $includeCheckoutQrUrl = (bool) config('payway.response.include_checkout_qr_url', false);

        return [
            'success' => true,
            'mode' => 'hosted_checkout',
            'transaction_uuid' => $transaction?->uuid,
            'tran_id' => $formData['tran_id'],
            'amount' => $payment->amount,
            'qr_string' => $includeRawQr ? ($purchaseResponse['qrString'] ?? null) : null,
            'qr_url' => $includeRawQr ? ($purchaseResponse['qrImage'] ?? null) : null,
            'deeplink' => $includeRawQr ? ($purchaseResponse['abapay_deeplink'] ?? null) : null,
            'qr_currency_code' => $this->extractQrCurrencyCode($purchaseResponse['qrString'] ?? null),
            'checkout_qr_url' => $includeCheckoutQrUrl ? $checkoutQrUrl : null,
            'expires_at' => $transaction?->expires_at,
            'payment_code' => $payment->payment_code,
            // Frontend should use this URL directly.
            'hosted_checkout_url' => $checkoutQrUrl,
        ];
    }

    private function callPurchaseForQr(array $formData): array
    {
        $response = Http::acceptJson()
            ->asForm()
            ->timeout(30)
            ->post(config('payway.api_url'), $formData);

        $rawBody = trim($response->body());
        $responseData = json_decode($rawBody, true);

        if (!is_array($responseData)) {
            throw new \Exception(sprintf(
                'Invalid purchase response (HTTP %d, Content-Type: %s): %s',
                $response->status(),
                (string) $response->header('Content-Type'),
                mb_substr($rawBody, 0, 500)
            ));
        }

        $statusCode = $this->extractStatusCode($responseData);
        if (!$response->successful() || !in_array($statusCode, ['0', '00', 'success', 'succeeded'], true)) {
            throw new \Exception(sprintf(
                'Purchase API failed (HTTP %d): %s',
                $response->status(),
                $rawBody
            ));
        }

        return $responseData;
    }

    private function buildPurchasePayloadWithCurrency(
        Payment $payment,
        ?PaywayTransaction $transaction,
        array $customerData,
        array $baseFormData
    ): array {
        if (!$transaction) {
            return $baseFormData;
        }

        $items = $this->prepareItemsData($payment);
        $shipping = 0;
        $firstName = $baseFormData['firstname'] ?? ($customerData['first_name'] ?? '');
        $lastName = $baseFormData['lastname'] ?? ($customerData['last_name'] ?? '');
        $email = $baseFormData['email'] ?? ($customerData['email'] ?? '');
        $phone = $baseFormData['phone'] ?? ($customerData['phone'] ?? '');
        $paymentOption = $baseFormData['payment_option'] ?? config('payway.khqr.payment_option_code', 'abapay');
        $returnUrl = PaywayCallbackService::getCallbackUrl('/api/v1/payway/webhook');
        $continueUrl = url('/payment/success');
        $returnDeeplink = base64_encode(json_encode([
            'android_scheme' => $continueUrl,
            'ios_scheme' => $continueUrl,
        ]));
        $returnParams = base64_encode(json_encode([
            'transaction_uuid' => $transaction->uuid,
            'payment_uuid' => $payment->uuid,
        ]));

        $hash = $this->generateHashForKHQR(
            (string) $baseFormData['req_time'],
            (string) $baseFormData['tran_id'],
            (string) $baseFormData['amount'],
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

        return [
            'req_time' => $baseFormData['req_time'],
            'merchant_id' => config('payway.merchant_id'),
            'tran_id' => $baseFormData['tran_id'],
            'amount' => $baseFormData['amount'],
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
    }

    private function extractQrCurrencyCode(?string $qrString): ?string
    {
        if (empty($qrString)) {
            return null;
        }

        if (preg_match('/5303(\d{3})/', $qrString, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function buildCheckoutQrUrl(
        Payment $payment,
        ?PaywayTransaction $transaction,
        ?string $qrString,
        $status
    ): ?string {
        if (empty($qrString)) {
            return null;
        }

        $checkoutData = [
            'status' => $status ?: [
                'code' => '00',
                'message' => 'Success!',
                'lang' => 'en',
            ],
            'step' => 'abapay_khqr_request_qr',
            'qr_string' => $qrString,
            'transaction_summary' => [
                'order_details' => [
                    'subtotal' => $payment->amount,
                    'vat_enabled' => '0',
                    'vat' => '0',
                    'shipping' => 0,
                    'vat_amount' => 0,
                    'transaction_fee' => 0,
                    'total' => $payment->amount,
                    'currency' => 'USD',
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
                    'border_radius' => 6,
                ],
            ],
            'payment_options' => [
                'abapay' => [
                    'label' => 'ABA Pay',
                ],
            ],
            'expire_in' => $transaction?->expires_at ? strtotime((string) $transaction->expires_at) : (time() + 900),
            'expire_in_sec' => '900',
            'render_qr_page' => 1,
        ];

        $checkoutBaseUrl = rtrim(config('payway.checkout_url', 'https://checkout-sandbox.payway.com.kh'), '/');

        return $checkoutBaseUrl . '/' . base64_encode(json_encode($checkoutData));
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

        $http = Http::acceptJson()->timeout(30);

        // QR endpoint expects JSON payload. Purchase endpoint uses form payload.
        $response = $useQrEndpoint
            ? $http->asJson()->post($apiUrl, $data)
            : $http->asForm()->post($apiUrl, $data);

        // Fallback retry with opposite payload type if gateway rejects content type.
        if ($response->status() === 415) {
            $response = $useQrEndpoint
                ? $http->asForm()->post($apiUrl, $data)
                : $http->asJson()->post($apiUrl, $data);
        }

        $rawBody = trim($response->body());

        if ($response->failed()) {
            throw new \Exception(sprintf(
                'PayWay API request failed (HTTP %d): %s',
                $response->status(),
                $rawBody
            ));
        }

        $responseData = json_decode($rawBody, true);
        if (!is_array($responseData)) {
            throw new \Exception(sprintf(
                'Invalid response from PayWay API (HTTP %d, Content-Type: %s): %s',
                $response->status(),
                (string) $response->header('Content-Type'),
                mb_substr($rawBody, 0, 500)
            ));
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
        $reqTime = $this->buildRequestTime();
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
     * Verify webhook signature from PayWay.
     * Expected format: base64(HMAC_SHA512(raw_payload, api_key))
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $providedSignature): bool
    {
        if (empty($rawPayload) || empty($providedSignature)) {
            return false;
        }

        $apiKey = config('payway.api_key');
        if (empty($apiKey)) {
            return false;
        }

        $expectedSignature = base64_encode(hash_hmac('sha512', $rawPayload, $apiKey, true));

        return hash_equals($expectedSignature, trim($providedSignature));
    }

    /**
     * Determine if PayWay API response indicates a successful transaction.
     */
    public function isSuccessfulTransactionResponse(array $response): bool
    {
        $status = $this->extractStatusCode($response);

        return in_array($status, ['0', '00', 'success', 'succeeded'], true);
    }

    /**
     * Determine if check-transaction response is still pending/processing.
     */
    public function isPendingTransactionResponse(array $response): bool
    {
        $status = $this->extractStatusCode($response);
        $paymentStatus = strtolower((string) ($response['payment_status'] ?? ''));
        $description = strtolower((string) ($response['description'] ?? $response['status_message'] ?? ''));

        if (in_array($status, ['2', 'pending', 'processing'], true)) {
            return true;
        }

        if (in_array($paymentStatus, ['pending', 'processing'], true)) {
            return true;
        }

        return str_contains($description, 'pending') || str_contains($description, 'processing');
    }

    /**
     * Extract status code from varying PayWay response formats.
     */
    public function extractStatusCode(array $response): ?string
    {
        if (isset($response['status']) && is_array($response['status']) && isset($response['status']['code'])) {
            return strtolower((string) $response['status']['code']);
        }

        if (isset($response['status']) && !is_array($response['status'])) {
            return strtolower((string) $response['status']);
        }

        if (isset($response['status_code'])) {
            return strtolower((string) $response['status_code']);
        }

        if (isset($response['code'])) {
            return strtolower((string) $response['code']);
        }

        return null;
    }

    /**
     * Generate hash for transaction check
     *
     * @param string $tranId
     * @param string $reqTime
     * @return string
     */
    private function generateCheckTransactionHash(string $tranId, string $reqTime): string
    {
        $apiKey = config('payway.api_key');
        $merchantId = config('payway.merchant_id');

        $dataToHash = $reqTime . $merchantId . $tranId;

        return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
    }

    /**
     * Build req_time in configured PayWay format (unix or ymdhis).
     */
    private function buildRequestTime(): string
    {
        $format = strtolower((string) config('payway.req_time_format', 'unix'));

        if ($format === 'ymdhis') {
            return gmdate('YmdHis');
        }

        // Default compatibility mode for existing PayWay integrations.
        return (string) time();
    }

    /**
     * Build the purchase payload used by hosted checkout mode.
     */
    private function generateHostedPaymentForm(Payment $payment, array $customerData = []): array
    {
        $tranId = $this->generateTranId($payment);

        // Create or update Payway transaction
        $transaction = PaywayTransaction::updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'tran_id' => $tranId,
                'amount' => $payment->amount,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(config('payway.khqr.qr_expiry_minutes', 15)),
            ]
        );

        // Hosted checkout request time format expected by gateway.
        $reqTime = gmdate('YmdHis');
        $amount = $payment->amount;
        // Customer information
        $firstName = $customerData['first_name'] ?? $payment->student->first_name ?? '';
        $lastName = $customerData['last_name'] ?? $payment->student->last_name ?? '';
        $email = $customerData['email'] ?? '';
        $phone = $customerData['phone'] ?? $payment->student->phone ?? '';

        // Payment option for KHQR
        $paymentOption = config('payway.khqr.payment_option_code', 'abapay');

        // Generate hash using the same field set as the known-working checkout sample.
        $hash = $this->generateHashForHostedPage(
            $reqTime,
            $transaction->tran_id,
            $amount,
            $firstName,
            $lastName,
            $email,
            $phone,
            $paymentOption
        );

        return [
            'hash' => $hash,
            'req_time' => $reqTime,
            'merchant_id' => config('payway.merchant_id'),
            'tran_id' => $transaction->tran_id,
            'amount' => $amount,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'payment_option' => $paymentOption,
        ];
    }

    /**
     * Generate hash for hosted payment page
     */
    private function generateHashForHostedPage(...$params): string
    {
        $apiKey = config('payway.api_key');
        $merchantId = config('payway.merchant_id');

        [$reqTime, $transactionId, $amount, $firstName, $lastName,
         $email, $phone, $paymentOption] = $params;

        $dataToHash = $reqTime . $merchantId . $transactionId . $amount .
                     $firstName . $lastName . $email . $phone . $paymentOption;

        return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
    }

    /**
     * Build a fresh PayWay transaction ID per generation attempt.
     * Reusing old tran_id can trigger lifetime errors on gateway side.
     */
    private function generateTranId(Payment $payment): string
    {
        $base = preg_replace('/[^A-Za-z0-9]/', '', (string) $payment->payment_code);
        $base = strtoupper($base ?: 'PAY');

        // Keep short suffix to satisfy PayWay tran_id length constraints.
        $suffix = gmdate('mdHis') . random_int(10, 99);
        $maxPrefixLength = 20 - strlen($suffix);
        $prefix = substr($base, 0, max(1, $maxPrefixLength));

        return $prefix . $suffix;
    }

}
