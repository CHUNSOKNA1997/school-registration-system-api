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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subject_code', 20)->unique();
            $table->string('name', 100);
            $table->string('name_khmer')->nullable();
            $table->text('description')->nullable();
            $table->integer('grade_level');
            $table->enum('subject_type', ['core', 'elective', 'extra'])->default('core');
            $table->integer('credits')->default(1);
            $table->integer('hours_per_week')->default(1);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->string('syllabus')->nullable(); // File path
            $table->json('prerequisites')->nullable(); // Subject IDs
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('uuid');
            $table->index('subject_code');
            $table->index('grade_level');
            $table->index('subject_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
