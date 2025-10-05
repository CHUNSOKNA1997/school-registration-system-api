<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payway_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('cascade');
            $table->string('tran_id'); // PayWay transaction ID
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, processing, success, failed, cancelled

            // KHQR specific fields
            $table->text('qr_string')->nullable(); // QR code data
            $table->string('qr_url')->nullable(); // QR image URL from PayWay
            $table->string('deeplink')->nullable(); // ABA deeplink for mobile

            $table->string('apv')->nullable(); // Approval code from PayWay
            $table->foreignId('pushback_id')->nullable()->constrained('payway_pushbacks');

            $table->timestamp('expires_at')->nullable(); // QR expiry time
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('uuid');
            $table->index('tran_id');
            $table->index('payment_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payway_transactions');
    }
};
