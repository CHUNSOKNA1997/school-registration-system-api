@extends('layouts.app')

@section('title', 'Test PayWay KHQR')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">PayWay KHQR Payment Test</h2>
        <p class="mt-2 text-gray-600">Test the KHQR payment integration with PayWay</p>
    </div>

    <!-- Step 1: Create Test Payment -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6" x-data="paymentCreator()">
        <h3 class="text-xl font-semibold mb-4">Step 1: Create Test Payment</h3>

        <form @submit.prevent="createPayment" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (USD)</label>
                    <input
                        type="number"
                        x-model="amount"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter amount (e.g., 100.00)"
                        step="0.01"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input
                        type="text"
                        x-model="description"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Test Payment"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student ID (optional)</label>
                    <input
                        type="number"
                        x-model="studentId"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Leave empty for test"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input
                        type="text"
                        x-model="phone"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="012345678"
                    >
                </div>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50"
                :disabled="loading"
            >
                <span x-show="!loading">Create Test Payment</span>
                <span x-show="loading">Creating...</span>
            </button>
        </form>

        <!-- Payment Created Success -->
        <div x-show="payment" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <h4 class="font-semibold text-green-800 mb-2">✅ Payment Created!</h4>
            <div class="text-sm space-y-1">
                <p><strong>Payment UUID:</strong> <span x-text="payment?.uuid" class="font-mono"></span></p>
                <p><strong>Payment Code:</strong> <span x-text="payment?.payment_code" class="font-mono"></span></p>
                <p><strong>Amount:</strong> $<span x-text="payment?.amount"></span></p>
                <p><strong>Status:</strong> <span x-text="payment?.status" class="uppercase"></span></p>
            </div>
        </div>
    </div>

    <!-- Step 2: Generate KHQR -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6" x-data="khqrGenerator()">
        <h3 class="text-xl font-semibold mb-4">Step 2: Generate KHQR Code</h3>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Payment UUID</label>
            <input
                type="text"
                x-model="paymentUuid"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Paste payment UUID from Step 1"
            >
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                <input
                    type="text"
                    x-model="firstName"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Sophea"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                <input
                    type="text"
                    x-model="lastName"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Chan"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input
                    type="text"
                    x-model="phone"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="012345678"
                >
            </div>
        </div>

        <button
            @click="generateQR"
            class="w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition disabled:opacity-50"
            :disabled="loading || !paymentUuid"
        >
            <span x-show="!loading">🔄 Generate QR Code</span>
            <span x-show="loading">Generating...</span>
        </button>

        <!-- Error Message -->
        <div x-show="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-800" x-text="error"></p>
        </div>

        <!-- QR Code Display -->
        <div x-show="qrData" class="mt-6">
            <div class="border-t pt-6">
                <h4 class="text-lg font-semibold mb-4 text-center">Scan QR Code to Pay</h4>

                <!-- QR Code Image -->
                <div class="flex justify-center mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-lg">
                        <img
                            :src="qrData?.qr_url"
                            alt="KHQR Code"
                            class="w-64 h-64 object-contain"
                            x-show="qrData?.qr_url"
                        >
                        <div x-show="!qrData?.qr_url" class="w-64 h-64 flex items-center justify-center bg-gray-100 rounded">
                            <p class="text-gray-500">No QR Image</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <h5 class="font-semibold mb-2">Payment Information</h5>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-600">Transaction UUID:</span>
                            <p class="font-mono text-xs break-all" x-text="qrData?.transaction_uuid"></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Payment Code:</span>
                            <p class="font-mono" x-text="qrData?.payment_code"></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Expires At:</span>
                            <p x-text="qrData?.expires_at"></p>
                        </div>
                        <div>
                            <span class="text-gray-600">Status:</span>
                            <p class="uppercase font-semibold" :class="{
                                'text-yellow-600': paymentStatus === 'pending',
                                'text-green-600': paymentStatus === 'paid',
                                'text-red-600': paymentStatus === 'failed'
                            }" x-text="paymentStatus"></p>
                        </div>
                    </div>
                </div>

                <!-- Mobile Deeplink -->
                <div x-show="qrData?.deeplink" class="mb-4">
                    <a
                        :href="qrData?.deeplink"
                        class="block w-full bg-purple-600 text-white text-center px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition"
                    >
                        📱 Pay with ABA Mobile App
                    </a>
                </div>

                <!-- Polling Status -->
                <div class="text-center">
                    <button
                        @click="startPolling"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
                        :disabled="polling"
                    >
                        <span x-show="!polling">▶️ Start Checking Status</span>
                        <span x-show="polling">⏸️ Checking... (Click to stop)</span>
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Auto-checks payment status every 3 seconds</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Manual Status Check -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold mb-4">Step 3: Manual Status Check</h3>

        <div x-data="{ paymentUuid: '', status: null, loading: false }">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment UUID</label>
                <input
                    type="text"
                    x-model="paymentUuid"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="Enter payment UUID"
                >
            </div>

            <button
                @click="checkStatus(paymentUuid)"
                class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition"
                :disabled="loading"
            >
                Check Payment Status
            </button>

            <div x-show="status" class="mt-4 p-4 bg-gray-50 rounded-lg">
                <pre x-text="JSON.stringify(status, null, 2)" class="text-xs overflow-auto"></pre>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">📋 Testing Instructions</h3>
        <ol class="list-decimal list-inside space-y-2 text-blue-800">
            <li>Create a test payment using Step 1</li>
            <li>Copy the Payment UUID from the success message</li>
            <li>Paste it in Step 2 and generate the QR code</li>
            <li>Scan the QR with ABA app (or click mobile link)</li>
            <li>Complete payment in ABA app</li>
            <li>Watch the status update automatically (or check manually in Step 3)</li>
        </ol>
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
            <p class="text-sm text-yellow-800">
                <strong>⚠️ Note:</strong> Make sure your NGROK_URL is set in .env if testing locally!
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function paymentCreator() {
        return {
            amount: 100,
            description: 'Test Payment',
            studentId: null,
            phone: '012345678',
            loading: false,
            payment: null,

            async createPayment() {
                this.loading = true;

                try {
                    const response = await fetch('/test/khqr/create-payment', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            amount: this.amount,
                            description: this.description,
                            student_id: this.studentId,
                            phone: this.phone
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.payment = data.payment;
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Network error: ' + error.message);
                } finally {
                    this.loading = false;
                }
            }
        }
    }

    function khqrGenerator() {
        return {
            paymentUuid: '',
            firstName: 'Sophea',
            lastName: 'Chan',
            phone: '012345678',
            qrData: null,
            error: null,
            loading: false,
            polling: false,
            pollingInterval: null,
            paymentStatus: 'idle',

            async generateQR() {
                this.loading = true;
                this.error = null;

                try {
                    const response = await fetch('/test/khqr/generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            payment_uuid: this.paymentUuid,
                            first_name: this.firstName,
                            last_name: this.lastName,
                            phone: this.phone
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.qrData = data.data;
                        this.paymentStatus = 'pending';
                        this.error = null;
                    } else {
                        this.error = data.message || 'Failed to generate QR code';
                    }
                } catch (error) {
                    this.error = 'Network error: ' + error.message;
                } finally {
                    this.loading = false;
                }
            },

            async checkPaymentStatus() {
                try {
                    const response = await fetch('/test/khqr/status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            payment_uuid: this.paymentUuid
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.paymentStatus = data.data.status;

                        if (data.data.status === 'paid') {
                            this.stopPolling();
                            alert('🎉 Payment Successful!');
                        }
                    }
                } catch (error) {
                    console.error('Status check error:', error);
                }
            },

            startPolling() {
                if (this.polling) {
                    this.stopPolling();
                    return;
                }

                this.polling = true;
                this.pollingInterval = setInterval(() => {
                    this.checkPaymentStatus();
                }, 3000);
            },

            stopPolling() {
                this.polling = false;
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
            }
        }
    }

    async function checkStatus(uuid) {
        // For Step 3 manual check
        if (!uuid) return;

        const response = await fetch('/test/khqr/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ payment_uuid: uuid })
        });

        return await response.json();
    }
</script>
@endpush
@endsection
