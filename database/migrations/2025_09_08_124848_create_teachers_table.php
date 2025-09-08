<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Gender;
use App\Enums\EmploymentType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('teacher_code', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('khmer_name')->nullable();
            $table->enum('gender', Gender::values());
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 50)->default('Cambodian');
            $table->string('email')->unique()->nullable();
            $table->string('phone', 20);
            $table->string('emergency_contact', 20)->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('employment_type', EmploymentType::values())->default(EmploymentType::FULL_TIME->value);
            $table->string('photo')->nullable();
            $table->json('documents')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('uuid');
            $table->index('teacher_code');
            $table->index('is_active');
            $table->index(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
