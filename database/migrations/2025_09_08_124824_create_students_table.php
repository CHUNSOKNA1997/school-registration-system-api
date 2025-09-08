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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('student_code', 20)->unique(); // 2024-0001
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('khmer_name')->nullable();
            $table->date('date_of_birth');
            $table->string('place_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('student_type', ['regular', 'monk'])->default('regular');
            $table->string('nationality', 50)->default('Cambodian');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('parent_name');
            $table->string('parent_phone', 20);
            $table->string('parent_occupation')->nullable();
            $table->string('emergency_contact', 20)->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->foreignId('class_id')->nullable()->constrained('classes');
            $table->enum('shift', ['morning', 'afternoon', 'evening', 'night', 'weekend']);
            $table->date('registration_date');
            $table->string('academic_year', 9); // 2024-2025
            $table->string('previous_school')->nullable();
            $table->string('photo')->nullable();
            $table->json('documents')->nullable(); // Store document paths
            $table->enum('status', ['active', 'inactive', 'graduated', 'dropped', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('uuid');
            $table->index('student_code');
            $table->index('class_id');
            $table->index('status');
            $table->index('academic_year');
            $table->index(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
