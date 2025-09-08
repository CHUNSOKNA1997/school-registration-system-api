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
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes');
            $table->string('academic_year', 9);
            $table->date('assigned_date');
            $table->date('end_date')->nullable();
            $table->enum('role', ['primary', 'assistant', 'substitute'])->default('primary');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['teacher_id', 'subject_id', 'class_id', 'academic_year'], 'teacher_subject_class_year_unique');
            
            // Indexes
            $table->index('uuid');
            $table->index('teacher_id');
            $table->index('subject_id');
            $table->index('class_id');
            $table->index('academic_year');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
