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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 50); // Grade 1-A
            $table->integer('grade_level');
            $table->string('section', 10); // A, B, C
            $table->integer('capacity')->default(30);
            $table->integer('current_enrollment')->default(0);
            $table->string('room_number', 20)->nullable();
            $table->string('academic_year', 9); // 2024-2025
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Composite unique constraint
            $table->unique(['grade_level', 'section', 'academic_year']);
            
            // Indexes
            $table->index('uuid');
            $table->index('grade_level');
            $table->index('academic_year');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
