<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test PayWay Payment Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8 text-center">PayWay KHQR Payment Test</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Option 1: QR Code Image -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">✅ Option 1: QR Code</h2>
                <p class="text-gray-600 mb-4">Display PayWay's official QR code (Best for Desktop)</p>
                <button onclick="testQRCode()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold">
                    Show QR Code
                </button>
                <p class="text-xs text-gray-500 mt-3">Scan with ABA Mobile, Wing, ACLEDA, or any KHQR app</p>
            </div>

            <!-- Option 2: Deeplink -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">✅ Option 2: Mobile Deeplink</h2>
                <p class="text-gray-600 mb-4">Open ABA Mobile app directly (Best for Mobile)</p>
                <button onclick="testDeeplink()" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold">
                    Open ABA Mobile
                </button>
                <p class="text-xs text-gray-500 mt-3">Opens payment directly in ABA Mobile app</p>
            </div>
        </div>

        <!-- Information Box -->
        <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-lg">
            <h3 class="font-semibold text-blue-900 mb-2">💡 For Your Next.js Integration</h3>
            <p class="text-blue-800 mb-3">PayWay provides QR code and deeplink for payment. There's no separate hosted checkout page URL.</p>
            <ul class="text-sm text-blue-700 space-y-2">
                <li>✓ <strong>Desktop users:</strong> Display the QR code image from <code class="bg-blue-100 px-1">qr_url</code></li>
                <li>✓ <strong>Mobile users:</strong> Redirect to <code class="bg-blue-100 px-1">deeplink</code> to open ABA Mobile</li>
                <li>✓ <strong>Check status:</strong> Poll <code class="bg-blue-100 px-1">/api/payway/payment/status</code> to detect when payment is complete</li>
            </ul>
        </div>

        <!-- QR Code Display -->
        <div id="qrDisplay" class="hidden mt-8 bg-white rounded-lg shadow-lg p-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">Scan to Pay</h3>
                <button onclick="closeQR()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="text-center">
                <img id="qrImage" src="" alt="QR Code" class="mx-auto max-w-md">
                <p class="text-gray-600 mt-4">Payment Code: <span id="paymentCode" class="font-mono font-bold"></span></p>
                <p class="text-gray-600">Amount: <span id="paymentAmount" class="font-bold text-green-600">$100.00</span></p>
            </div>
        </div>

        <!-- Hidden form for hosted checkout -->
        <form id="hostedCheckoutForm" action="" method="POST" enctype="application/x-www-form-urlencoded" style="display: none;">
        </form>

        <!-- Loading indicator -->
        <div id="loading" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-700">Processing payment...</p>
            </div>
        </div>
    </div>

    <script>
        const PAYMENT_UUID = 'b2c24e36-76f2-41e0-bd5d-d5da559ff03a';
        const API_URL = '/api/payway/test/khqr';

        async function getPaymentData() {
            const loading = document.getElementById('loading');
            loading.classList.remove('hidden');

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        payment_uuid: PAYMENT_UUID,
                        first_name: 'John',
                        last_name: 'Doe',
                        email: 'john@example.com',
                        phone: '012345678'
                    })
                });

                const data = await response.json();
                loading.classList.add('hidden');

                if (data.success) {
                    return data.data;
                } else {
                    alert('Error: ' + (data.message || 'Failed to generate payment'));
                    return null;
                }
            } catch (error) {
                loading.classList.add('hidden');
                alert('Error: ' + error.message);
                return null;
            }
        }

        async function testQRCode() {
            const data = await getPaymentData();
            if (!data) return;

            document.getElementById('qrImage').src = data.qr_url;
            document.getElementById('paymentCode').textContent = data.payment_code;
            document.getElementById('qrDisplay').classList.remove('hidden');
        }

        async function testDeeplink() {
            const data = await getPaymentData();
            if (!data) return;

            // Open deeplink
            window.location.href = data.deeplink;
        }

        async function testHostedCheckout() {
            const data = await getPaymentData();
            if (!data) return;

            console.log('Payment data:', data);
            console.log('Checkout URL:', data.checkout_form_url);
            console.log('Form data:', data.checkout_form_data);

            const form = document.getElementById('hostedCheckoutForm');
            const formData = data.checkout_form_data;

            // Set form action to PayWay's checkout URL
            form.action = data.checkout_form_url;
            form.method = 'POST';

            // Clear any existing hidden inputs
            form.innerHTML = '';

            // Create and populate form fields dynamically
            Object.keys(formData).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = formData[key];
                form.appendChild(input);

                const displayValue = String(formData[key]);
                const truncated = displayValue.length > 50 ? displayValue.substring(0, 50) + '...' : displayValue;
                console.log(`Added field: ${key} = ${truncated}`);
            });

            console.log('Form fields count:', form.elements.length);
            console.log('Submitting to:', form.action);

            // Submit form
            form.submit();
        }

        function closeQR() {
            document.getElementById('qrDisplay').classList.add('hidden');
        }
    </script>
</body>
</html>
