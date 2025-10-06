<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - {{ $payment->payment_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Scan to Pay</h1>
                <p class="text-gray-600">Use ABA Mobile or any KHQR banking app</p>
            </div>

            <!-- Payment Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <!-- ABA PayWay Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold">ABA PayWay</h2>
                            <p class="text-blue-100 text-sm">Secure Payment Gateway</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg px-4 py-2">
                            <span class="text-2xl font-bold">${{ number_format($payment->amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <!-- QR Code Section -->
                    <div class="flex justify-center mb-6">
                        @if($qrImage)
                            <div class="bg-white p-4 rounded-2xl shadow-lg">
                                <img src="{{ $qrImage }}"
                                     alt="KHQR Payment Code"
                                     class="w-full max-w-sm mx-auto">
                            </div>
                        @else
                            <div class="w-64 h-64 bg-gray-200 rounded-2xl flex items-center justify-center">
                                <p class="text-gray-500">QR Code Not Available</p>
                            </div>
                        @endif
                    </div>

                    <!-- Payment Details -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Payment Code</p>
                                <p class="font-mono font-semibold text-gray-800">{{ $payment->payment_code }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Amount</p>
                                <p class="text-2xl font-bold text-blue-600">${{ number_format($payment->amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Status</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                    {{ $payment->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Description</p>
                                <p class="font-medium text-gray-800">{{ $payment->description ?? 'Payment' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Deeplink Button -->
                    @if($deeplink)
                        <div class="mb-6">
                            <a href="{{ $deeplink }}"
                               class="block w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                                      text-white text-center py-4 rounded-xl font-semibold text-lg shadow-lg
                                      transform transition hover:scale-105">
                                <span class="flex items-center justify-center">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    Open in ABA Mobile
                                </span>
                            </a>
                        </div>
                    @endif

                    <!-- Instructions -->
                    <div class="border-t pt-6">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            How to Pay
                        </h3>
                        <ol class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">1</span>
                                <span>Open your banking app (ABA Mobile, Wing, ACLEDA, etc.)</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">2</span>
                                <span>Scan the QR code above</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">3</span>
                                <span>Confirm the payment in your app</span>
                            </li>
                        </ol>
                    </div>

                    <!-- Waiting Status -->
                    <div class="mt-6 text-center" id="waiting-status">
                        <div class="flex items-center justify-center text-gray-500">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full pulse-animation mr-2"></div>
                            <span class="text-sm">Waiting for payment confirmation...</span>
                        </div>
                    </div>

                    <!-- Success Message (hidden by default) -->
                    <div class="mt-6 text-center hidden" id="success-status">
                        <div class="flex items-center justify-center text-green-600">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-semibold">Payment Completed Successfully!</span>
                        </div>
                    </div>
                </div>

                <!-- Supported Banks Footer -->
                <div class="bg-gray-50 px-8 py-4 border-t">
                    <p class="text-xs text-gray-500 text-center mb-2">Supported by all KHQR banking apps:</p>
                    <div class="flex justify-center items-center gap-4 text-xs text-gray-600">
                        <span class="font-semibold">ABA Bank</span>
                        <span>•</span>
                        <span class="font-semibold">Wing</span>
                        <span>•</span>
                        <span class="font-semibold">ACLEDA</span>
                        <span>•</span>
                        <span class="font-semibold">Pi Pay</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    Powered by <span class="font-semibold">ABA PayWay</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Auto-refresh script to check payment status -->
    <script>
        const paymentUuid = '{{ $payment->uuid }}';
        let checkInterval;

        async function checkPaymentStatus() {
            try {
                const response = await fetch('/api/payway/payment/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ payment_uuid: paymentUuid })
                });

                const data = await response.json();

                if (data.success && data.data.status === 'paid') {
                    // Payment completed
                    clearInterval(checkInterval);
                    document.getElementById('waiting-status').classList.add('hidden');
                    document.getElementById('success-status').classList.remove('hidden');

                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = '/payment/success?payment=' + paymentUuid;
                    }, 2000);
                }
            } catch (error) {
                console.error('Status check failed:', error);
            }
        }

        // Check status every 3 seconds
        checkInterval = setInterval(checkPaymentStatus, 3000);

        // Check once immediately
        checkPaymentStatus();
    </script>
</body>
</html>
