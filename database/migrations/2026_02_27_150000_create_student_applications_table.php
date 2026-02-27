<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('personal_email');
            $table->string('school_email')->nullable()->unique();
            $table->string('payment_plan', 20)->default('monthly');
            $table->string('status', 30)->default('payment_pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('activation_token_hash', 128)->nullable();
            $table->timestamp('activation_token_expires_at')->nullable();
            $table->timestamp('onboarding_sent_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('personal_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
