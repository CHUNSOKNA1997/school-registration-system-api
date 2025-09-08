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
        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained();
            $table->foreignId('teacher_id')->constrained();
            $table->string('academic_year', 9);
            $table->date('enrolled_date');
            $table->date('completion_date')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade', 2)->nullable();
            $table->enum('status', ['active', 'dropped', 'completed', 'failed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate enrollments
            $table->unique(['student_id', 'subject_id', 'academic_year']);
            
            // Indexes for queries
            $table->index('uuid');
            $table->index('student_id');
            $table->index('subject_id');
            $table->index('teacher_id');
            $table->index('status');
            $table->index('academic_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
    }
};
