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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payment_code', 30)->unique();
            $table->string('invoice_number', 30)->nullable();
            $table->foreignId('student_id')->constrained();
            $table->string('academic_year', 9);
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->enum('payment_type', ['registration', 'tuition', 'exam', 'certificate', 'other'])->default('tuition');
            $table->enum('payment_period', ['monthly', 'quarterly', 'semester', 'yearly', 'one_time']);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'khqr', 'aba', 'acleda', 'wing']);
            $table->string('payment_month', 7)->nullable(); // 2024-01
            $table->date('payment_date')->nullable();
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'cancelled'])->default('pending');
            $table->string('khqr_reference', 100)->nullable();
            $table->string('bank_reference', 100)->nullable();
            $table->string('receipt_number', 30)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('uuid');
            $table->index('payment_code');
            $table->index('invoice_number');
            $table->index('student_id');
            $table->index('status');
            $table->index('payment_date');
            $table->index('due_date');
            $table->index('academic_year');
            $table->index('payment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
