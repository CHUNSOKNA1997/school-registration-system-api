# School Registration System - Complete Implementation Guideline

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technology Stack](#technology-stack)
3. [System Architecture](#system-architecture)
4. [Database Design](#database-design)
5. [API Endpoints](#api-endpoints)
6. [Implementation Phases](#implementation-phases)
7. [Detailed Implementation Steps](#detailed-implementation-steps)
8. [Security Considerations](#security-considerations)
9. [Testing Strategy](#testing-strategy)
10. [Deployment Guide](#deployment-guide)

---

## Project Overview

### Purpose

A web-based school registration system for managing student enrollments across Kindergarten, Primary, and High School levels with integrated payment tracking via KHQR Bakong API.

### Key Features

-   Two-role authentication system (Staff & Admin)
-   8-step student registration process
-   Teacher-Subject-Student relationship management
-   Payment integration with KHQR Bakong
-   Dashboard with statistics and reports
-   Automated Student ID generation

### User Roles

| Role      | Permissions                                                                                                                                                                             |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Staff** | • Register new students<br>• View student list<br>• Search students<br>• View dashboard (limited)                                                                                       |
| **Admin** | • All Staff permissions<br>• CRUD Teachers<br>• CRUD Subjects<br>• CRUD Users (Admin/Staff)<br>• CRUD Classes<br>• Edit/Delete students<br>• System configuration<br>• View all reports |

---

## Technology Stack

### Backend

```
Framework: Laravel 11
PHP Version: 8.2+
Database: MySQL 8.0 / PostgreSQL 14
Authentication: Laravel Sanctum
API: RESTful JSON
Queue: Laravel Queue (for notifications)
Cache: Redis (optional)
```

### Frontend

```
Framework: Next.js 14 (App Router)
Language: TypeScript
UI Library: Shadcn/ui
Styling: Tailwind CSS
Forms: React Hook Form + Zod
State: Zustand / Context API
HTTP Client: Axios / Fetch API
Charts: Recharts
```

### Development Tools

```
Version Control: Git
Package Manager: Composer (PHP) & npm/yarn (JS)
API Testing: Postman / Insomnia
Database GUI: TablePlus / phpMyAdmin
IDE: VS Code / PHPStorm
```

---

## System Architecture

### High-Level Architecture

```
┌─────────────────┐     HTTPS      ┌─────────────────┐
│                 │◄──────────────►│                 │
│   Next.js App   │                │  Laravel API    │
│   (Frontend)    │   JSON/REST    │   (Backend)     │
│                 │                │                 │
└─────────────────┘                └─────────────────┘
                                            │
                                            ▼
                                    ┌─────────────────┐
                                    │                 │
                                    │  MySQL/PostgreSQL│
                                    │   Database      │
                                    │                 │
                                    └─────────────────┘
                                            │
                                            ▼
                                    ┌─────────────────┐
                                    │  KHQR Bakong    │
                                    │   Payment API   │
                                    └─────────────────┘
```

### Request Flow

```
1. User Action (Browser)
   ↓
2. Next.js Frontend
   ↓
3. API Request (with Sanctum token)
   ↓
4. Laravel Middleware (auth, validation)
   ↓
5. Controller Logic
   ↓
6. Database Operation
   ↓
7. JSON Response
   ↓
8. Frontend State Update
   ↓
9. UI Re-render
```

---

## Database Design

### Technology Stack for Database

-   **Database**: Supabase (PostgreSQL)
-   **ORM**: Laravel Eloquent
-   **Migrations**: Laravel Migration files

### Entity Relationship Diagram

```
students ─────< student_subjects >───── subjects
    │                  │                    │
    │                  │                    │
    │              teachers ─────< teacher_subjects
    │
    └──< payments
```

### Laravel Migrations (With UUID Support)

#### 1. Create Users Table Migration

```php
// database/migrations/2024_01_01_000001_create_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('avatar')->nullable();
            $table->string('phone', 20)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('uuid');
            $table->index('email');
            $table->index('is_admin');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

#### 2. Create Classes Table Migration (must be before students)

```php
// database/migrations/2024_01_01_000002_create_classes_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
```

#### 3. Create Students Table Migration

```php
// database/migrations/2024_01_01_000003_create_students_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Gender;
use App\Enums\StudentType;
use App\Enums\StudentStatus;
use App\Enums\Shift;

return new class extends Migration
{
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
            $table->enum('gender', Gender::values());
            $table->enum('student_type', StudentType::values())->default(StudentType::REGULAR->value);
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
            $table->enum('shift', Shift::values());
            $table->date('registration_date');
            $table->string('academic_year', 9); // 2024-2025
            $table->string('previous_school')->nullable();
            $table->string('photo')->nullable();
            $table->json('documents')->nullable(); // Store document paths
            $table->enum('status', StudentStatus::values())->default(StudentStatus::ACTIVE->value);
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
```

#### 4. Create Teachers Table Migration

```php
// database/migrations/2024_01_01_000004_create_teachers_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Gender;
use App\Enums\EmploymentType;

return new class extends Migration
{
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
```

#### 5. Create Subjects Table Migration

```php
// database/migrations/2024_01_01_000005_create_subjects_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SubjectType;

return new class extends Migration
{
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
            $table->enum('subject_type', SubjectType::values())->default(SubjectType::CORE->value);
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
```

#### 6. Create Student-Subjects Pivot Table Migration

```php
// database/migrations/2024_01_01_000006_create_student_subjects_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EnrollmentStatus;

return new class extends Migration
{
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
            $table->enum('status', EnrollmentStatus::values())->default(EnrollmentStatus::ACTIVE->value);
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
```

#### 7. Create Teacher-Subjects Pivot Table Migration

```php
// database/migrations/2024_01_01_000007_create_teacher_subjects_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TeacherRole;

return new class extends Migration
{
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
            $table->enum('role', TeacherRole::values())->default(TeacherRole::PRIMARY->value);
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
```

#### 8. Create Payments Table Migration

```php
// database/migrations/2024_01_01_000008_create_payments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentType;
use App\Enums\PaymentPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

return new class extends Migration
{
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
            $table->enum('payment_type', PaymentType::values())->default(PaymentType::TUITION->value);
            $table->enum('payment_period', PaymentPeriod::values());
            $table->enum('payment_method', PaymentMethod::values());
            $table->string('payment_month', 7)->nullable(); // 2024-01
            $table->date('payment_date')->nullable();
            $table->date('due_date');
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::PENDING->value);
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
```

#### 9. Create System Settings Table Migration

```php
// database/migrations/2024_01_01_000009_create_system_settings_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SettingType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('group', 50)->default('general');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->enum('type', SettingType::values())->default(SettingType::STRING->value);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('uuid');
            $table->index('key');
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
```

#### 10. Create Activity Logs Table Migration

```php
// database/migrations/2024_01_01_000010_create_activity_logs_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('module', 50)->nullable();
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_uuid', 36)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index(['model_type', 'model_id']);
            $table->index('model_uuid');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

### Laravel Enums (PHP 8.1+ Enums)

Create a dedicated `app/Enums` folder for all enum classes:

#### 1. Gender Enum

```php
// app/Enums/Gender.php
<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::OTHER => 'Other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
```

#### 2. Student Type Enum

```php
// app/Enums/StudentType.php
<?php

namespace App\Enums;

enum StudentType: string
{
    case REGULAR = 'regular';
    case MONK = 'monk';

    public function label(): string
    {
        return match($this) {
            self::REGULAR => 'Regular Student',
            self::MONK => 'Monk Student',
        };
    }

    public function discount(): float
    {
        return match($this) {
            self::REGULAR => 0,
            self::MONK => 100, // 100% discount for monks
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 3. Student Status Enum

```php
// app/Enums/StudentStatus.php
<?php

namespace App\Enums;

enum StudentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case GRADUATED = 'graduated';
    case DROPPED = 'dropped';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::GRADUATED => 'Graduated',
            self::DROPPED => 'Dropped Out',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::GRADUATED => 'blue',
            self::DROPPED => 'red',
            self::SUSPENDED => 'orange',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 4. Shift Enum

```php
// app/Enums/Shift.php
<?php

namespace App\Enums;

enum Shift: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';

    public function label(): string
    {
        return match($this) {
            self::MORNING => 'Morning (7:00 AM - 12:00 PM)',
            self::AFTERNOON => 'Afternoon (1:00 PM - 5:00 PM)',
            self::EVENING => 'Evening (5:00 PM - 8:00 PM)',
            self::NIGHT => 'Night (6:00 PM - 9:00 PM)',
            self::WEEKEND => 'Weekend (Saturday/Sunday)',
        };
    }

    public function timeRange(): array
    {
        return match($this) {
            self::MORNING => ['start' => '07:00', 'end' => '12:00'],
            self::AFTERNOON => ['start' => '13:00', 'end' => '17:00'],
            self::EVENING => ['start' => '17:00', 'end' => '20:00'],
            self::NIGHT => ['start' => '18:00', 'end' => '21:00'],
            self::WEEKEND => ['start' => '08:00', 'end' => '17:00'],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 5. Employment Type Enum

```php
// app/Enums/EmploymentType.php
<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';

    public function label(): string
    {
        return match($this) {
            self::FULL_TIME => 'Full Time',
            self::PART_TIME => 'Part Time',
            self::CONTRACT => 'Contract',
        };
    }

    public function hoursPerWeek(): int
    {
        return match($this) {
            self::FULL_TIME => 40,
            self::PART_TIME => 20,
            self::CONTRACT => 0, // Varies
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 6. Subject Type Enum

```php
// app/Enums/SubjectType.php
<?php

namespace App\Enums;

enum SubjectType: string
{
    case CORE = 'core';
    case ELECTIVE = 'elective';
    case EXTRA = 'extra';

    public function label(): string
    {
        return match($this) {
            self::CORE => 'Core Subject',
            self::ELECTIVE => 'Elective Subject',
            self::EXTRA => 'Extra Curricular',
        };
    }

    public function isRequired(): bool
    {
        return match($this) {
            self::CORE => true,
            self::ELECTIVE => false,
            self::EXTRA => false,
        };
    }

    public function priority(): int
    {
        return match($this) {
            self::CORE => 1,
            self::ELECTIVE => 2,
            self::EXTRA => 3,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 7. Enrollment Status Enum

```php
// app/Enums/EnrollmentStatus.php
<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case DROPPED = 'dropped';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Currently Enrolled',
            self::DROPPED => 'Dropped',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'blue',
            self::DROPPED => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }

    public function canEdit(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            self::DROPPED => false,
            self::COMPLETED => false,
            self::FAILED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 8. Teacher Role Enum

```php
// app/Enums/TeacherRole.php
<?php

namespace App\Enums;

enum TeacherRole: string
{
    case PRIMARY = 'primary';
    case ASSISTANT = 'assistant';
    case SUBSTITUTE = 'substitute';

    public function label(): string
    {
        return match($this) {
            self::PRIMARY => 'Primary Teacher',
            self::ASSISTANT => 'Assistant Teacher',
            self::SUBSTITUTE => 'Substitute Teacher',
        };
    }

    public function canGrade(): bool
    {
        return match($this) {
            self::PRIMARY => true,
            self::ASSISTANT => false,
            self::SUBSTITUTE => true,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 9. Payment Type Enum

```php
// app/Enums/PaymentType.php
<?php

namespace App\Enums;

enum PaymentType: string
{
    case REGISTRATION = 'registration';
    case TUITION = 'tuition';
    case EXAM = 'exam';
    case CERTIFICATE = 'certificate';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::REGISTRATION => 'Registration Fee',
            self::TUITION => 'Tuition Fee',
            self::EXAM => 'Exam Fee',
            self::CERTIFICATE => 'Certificate Fee',
            self::OTHER => 'Other Fee',
        };
    }

    public function isRecurring(): bool
    {
        return match($this) {
            self::REGISTRATION => false,
            self::TUITION => true,
            self::EXAM => false,
            self::CERTIFICATE => false,
            self::OTHER => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 10. Payment Period Enum

```php
// app/Enums/PaymentPeriod.php
<?php

namespace App\Enums;

enum PaymentPeriod: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMESTER = 'semester';
    case YEARLY = 'yearly';
    case ONE_TIME = 'one_time';

    public function label(): string
    {
        return match($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly (3 months)',
            self::SEMESTER => 'Per Semester (6 months)',
            self::YEARLY => 'Yearly',
            self::ONE_TIME => 'One Time Payment',
        };
    }

    public function months(): int
    {
        return match($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::SEMESTER => 6,
            self::YEARLY => 12,
            self::ONE_TIME => 0,
        };
    }

    public function installments(): int
    {
        return match($this) {
            self::MONTHLY => 12,
            self::QUARTERLY => 4,
            self::SEMESTER => 2,
            self::YEARLY => 1,
            self::ONE_TIME => 1,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 11. Payment Method Enum

```php
// app/Enums/PaymentMethod.php
<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case KHQR = 'khqr';
    case ABA = 'aba';
    case ACLEDA = 'acleda';
    case WING = 'wing';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::KHQR => 'KHQR (Bakong)',
            self::ABA => 'ABA Bank',
            self::ACLEDA => 'ACLEDA Bank',
            self::WING => 'Wing',
        };
    }

    public function requiresReference(): bool
    {
        return match($this) {
            self::CASH => false,
            self::BANK_TRANSFER => true,
            self::KHQR => true,
            self::ABA => true,
            self::ACLEDA => true,
            self::WING => true,
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CASH => 'cash',
            self::BANK_TRANSFER => 'bank',
            self::KHQR => 'qr-code',
            self::ABA => 'credit-card',
            self::ACLEDA => 'credit-card',
            self::WING => 'mobile',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 12. Payment Status Enum

```php
// app/Enums/PaymentStatus.php
<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::PARTIAL => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'green',
            self::PARTIAL => 'blue',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }

    public function canEdit(): bool
    {
        return match($this) {
            self::PENDING => true,
            self::PAID => false,
            self::PARTIAL => true,
            self::OVERDUE => true,
            self::CANCELLED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

#### 13. Setting Type Enum

```php
// app/Enums/SettingType.php
<?php

namespace App\Enums;

enum SettingType: string
{
    case STRING = 'string';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case ARRAY = 'array';

    public function label(): string
    {
        return match($this) {
            self::STRING => 'Text',
            self::NUMBER => 'Number',
            self::BOOLEAN => 'Yes/No',
            self::JSON => 'JSON Object',
            self::ARRAY => 'List',
        };
    }

    public function defaultValue(): mixed
    {
        return match($this) {
            self::STRING => '',
            self::NUMBER => 0,
            self::BOOLEAN => false,
            self::JSON => '{}',
            self::ARRAY => '[]',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Updated Migrations with Enum Classes

Now update the migrations to use the enum values:

```php
// Example for students table migration
use App\Enums\Gender;
use App\Enums\StudentType;
use App\Enums\StudentStatus;
use App\Enums\Shift;

// In the up() method:
$table->enum('gender', Gender::values());
$table->enum('student_type', StudentType::values())->default(StudentType::REGULAR->value);
$table->enum('status', StudentStatus::values())->default(StudentStatus::ACTIVE->value);
$table->enum('shift', Shift::values());
```

### Complete Laravel Models with UUID Support

#### 1. User Model (Updated with Enums)

```php
// app/Models/User.php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'is_admin',
        'is_active',
        'last_login_at',
        'avatar',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function studentsCreated()
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    public function studentsUpdated()
    {
        return $this->hasMany(Student::class, 'updated_by');
    }

    public function paymentsReceived()
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeStaff($query)
    {
        return $query->where('is_admin', false);
    }

    // Accessors & Mutators
    public function getIsAdminAttribute($value)
    {
        return (bool) $value;
    }

    public function getRoleAttribute()
    {
        return $this->is_admin ? 'Admin' : 'Staff';
    }

    // Methods
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function canAccessAdmin()
    {
        return $this->is_admin && $this->is_active;
    }
}
```

#### 2. Student Model (Updated with Enums)

```php
// app/Models/Student.php
<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StudentType;
use App\Enums\StudentStatus;
use App\Enums\Shift;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'student_code',
        'first_name',
        'last_name',
        'khmer_name',
        'date_of_birth',
        'place_of_birth',
        'gender',
        'student_type',
        'nationality',
        'phone',
        'email',
        'current_address',
        'permanent_address',
        'parent_name',
        'parent_phone',
        'parent_occupation',
        'emergency_contact',
        'emergency_contact_relationship',
        'class_id',
        'shift',
        'registration_date',
        'academic_year',
        'previous_school',
        'photo',
        'documents',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'registration_date' => 'date',
        'documents' => 'array',
        'gender' => Gender::class,
        'student_type' => StudentType::class,
        'status' => StudentStatus::class,
        'shift' => Shift::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->student_code)) {
                $model->student_code = self::generateStudentCode();
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    // Relationships
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
                    ->withPivot(['teacher_id', 'academic_year', 'enrolled_date',
                                 'completion_date', 'score', 'grade', 'status', 'notes'])
                    ->withTimestamps();
    }

    public function activeSubjects()
    {
        return $this->subjects()->wherePivot('status', 'active');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('student_code', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('khmer_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('parent_phone', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullNameKhmerAttribute()
    {
        return $this->khmer_name ?: $this->full_name;
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    // Methods
    public static function generateStudentCode()
    {
        $year = date('Y');
        $lastStudent = self::whereYear('created_at', $year)
                           ->orderBy('student_code', 'desc')
                           ->first();

        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->student_code, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalFees()
    {
        return $this->activeSubjects->sum('fee');
    }

    public function getOutstandingBalance()
    {
        return $this->payments()
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->sum('balance');
    }

    public function enrollInSubject($subjectId, $teacherId, $academicYear = null)
    {
        $academicYear = $academicYear ?: config('app.academic_year');

        return $this->subjects()->attach($subjectId, [
            'uuid' => Str::uuid()->toString(),
            'teacher_id' => $teacherId,
            'academic_year' => $academicYear,
            'enrolled_date' => now(),
            'status' => 'active',
        ]);
    }

    public function dropSubject($subjectId)
    {
        return $this->subjects()
                    ->updateExistingPivot($subjectId, [
                        'status' => 'dropped',
                        'completion_date' => now(),
                    ]);
    }
}
```

#### 3. Teacher Model (Updated with Enums)

```php
// app/Models/Teacher.php
<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'teacher_code',
        'first_name',
        'last_name',
        'khmer_name',
        'gender',
        'date_of_birth',
        'nationality',
        'email',
        'phone',
        'emergency_contact',
        'current_address',
        'permanent_address',
        'qualification',
        'specialization',
        'salary',
        'hire_date',
        'employment_type',
        'photo',
        'documents',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'documents' => 'array',
        'is_active' => 'boolean',
        'salary' => 'decimal:2',
        'gender' => Gender::class,
        'employment_type' => EmploymentType::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->teacher_code)) {
                $model->teacher_code = self::generateTeacherCode();
            }
        });
    }

    // Relationships
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')
                    ->withPivot(['class_id', 'academic_year', 'assigned_date',
                                'end_date', 'role', 'is_active'])
                    ->withTimestamps();
    }

    public function activeSubjects()
    {
        return $this->subjects()->wherePivot('is_active', true);
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'teacher_subjects', 'teacher_id', 'class_id')
                    ->distinct();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('teacher_code', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('khmer_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullNameKhmerAttribute()
    {
        return $this->khmer_name ?: $this->full_name;
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    public function getYearsOfServiceAttribute()
    {
        return $this->hire_date ? $this->hire_date->diffInYears(now()) : 0;
    }

    // Methods
    public static function generateTeacherCode()
    {
        $prefix = 'TCH';
        $year = date('Y');
        $lastTeacher = self::where('teacher_code', 'like', "{$prefix}{$year}%")
                           ->orderBy('teacher_code', 'desc')
                           ->first();

        if ($lastTeacher) {
            $lastNumber = intval(substr($lastTeacher->teacher_code, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function assignToSubject($subjectId, $classId = null, $academicYear = null)
    {
        $academicYear = $academicYear ?: config('app.academic_year');

        return $this->subjects()->attach($subjectId, [
            'uuid' => Str::uuid()->toString(),
            'class_id' => $classId,
            'academic_year' => $academicYear,
            'assigned_date' => now(),
            'role' => 'primary',
            'is_active' => true,
        ]);
    }

    public function getTotalStudents()
    {
        return $this->studentSubjects()
                    ->where('status', 'active')
                    ->distinct('student_id')
                    ->count('student_id');
    }

    public function getWorkload()
    {
        return $this->activeSubjects()
                    ->withCount('students')
                    ->get()
                    ->sum('students_count');
    }
}
```

#### 4. Subject Model (Updated with Enums)

```php
// app/Models/Subject.php
<?php

namespace App\Models;

use App\Enums\SubjectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'subject_code',
        'name',
        'name_khmer',
        'description',
        'grade_level',
        'subject_type',
        'credits',
        'hours_per_week',
        'fee',
        'monthly_fee',
        'syllabus',
        'prerequisites',
        'is_active',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'is_active' => 'boolean',
        'fee' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'subject_type' => SubjectType::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->subject_code)) {
                $model->subject_code = self::generateSubjectCode($model->grade_level);
            }
        });
    }

    // Relationships
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects')
                    ->withPivot(['class_id', 'academic_year', 'assigned_date',
                                'end_date', 'role', 'is_active'])
                    ->withTimestamps();
    }

    public function activeTeachers()
    {
        return $this->teachers()->wherePivot('is_active', true);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subjects')
                    ->withPivot(['teacher_id', 'academic_year', 'enrolled_date',
                                'completion_date', 'score', 'grade', 'status', 'notes'])
                    ->withTimestamps();
    }

    public function activeStudents()
    {
        return $this->students()->wherePivot('status', 'active');
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function prerequisiteSubjects()
    {
        return $this->whereIn('id', $this->prerequisites ?? []);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGradeLevel($query, $level)
    {
        return $query->where('grade_level', $level);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('subject_type', $type);
    }

    public function scopeCore($query)
    {
        return $query->where('subject_type', 'core');
    }

    public function scopeElective($query)
    {
        return $query->where('subject_type', 'elective');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('subject_code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('name_khmer', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return $this->name_khmer ? "{$this->name} ({$this->name_khmer})" : $this->name;
    }

    public function getSyllabusUrlAttribute()
    {
        return $this->syllabus ? asset('storage/' . $this->syllabus) : null;
    }

    public function getTotalFeeAttribute()
    {
        return $this->fee + ($this->monthly_fee * 12);
    }

    // Methods
    public static function generateSubjectCode($gradeLevel)
    {
        $prefix = 'SUB';
        $grade = str_pad($gradeLevel, 2, '0', STR_PAD_LEFT);
        $lastSubject = self::where('subject_code', 'like', "{$prefix}{$grade}%")
                           ->orderBy('subject_code', 'desc')
                           ->first();

        if ($lastSubject) {
            $lastNumber = intval(substr($lastSubject->subject_code, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $grade . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function hasPrerequisites()
    {
        return !empty($this->prerequisites);
    }

    public function checkPrerequisites(Student $student)
    {
        if (!$this->hasPrerequisites()) {
            return true;
        }

        $completedSubjects = $student->subjects()
                                     ->whereIn('subject_id', $this->prerequisites)
                                     ->wherePivot('status', 'completed')
                                     ->count();

        return $completedSubjects === count($this->prerequisites);
    }

    public function getEnrollmentCount($academicYear = null)
    {
        $query = $this->activeStudents();

        if ($academicYear) {
            $query->wherePivot('academic_year', $academicYear);
        }

        return $query->count();
    }
}
```

#### 5. ClassModel Model

```php
// app/Models/ClassModel.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'uuid',
        'name',
        'grade_level',
        'section',
        'capacity',
        'current_enrollment',
        'room_number',
        'academic_year',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
        'current_enrollment' => 'integer',
        'grade_level' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->name)) {
                $model->name = "Grade {$model->grade_level}-{$model->section}";
            }
        });
    }

    // Relationships
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function activeStudents()
    {
        return $this->students()->where('status', 'active');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects', 'class_id', 'teacher_id')
                    ->distinct();
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'class_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByGradeLevel($query, $level)
    {
        return $query->where('grade_level', $level);
    }

    public function scopeAvailable($query)
    {
        return $query->whereColumn('current_enrollment', '<', 'capacity');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('room_number', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getIsFullAttribute()
    {
        return $this->current_enrollment >= $this->capacity;
    }

    public function getAvailableSlotsAttribute()
    {
        return max(0, $this->capacity - $this->current_enrollment);
    }

    public function getOccupancyRateAttribute()
    {
        if ($this->capacity == 0) {
            return 0;
        }

        return round(($this->current_enrollment / $this->capacity) * 100, 2);
    }

    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->academic_year})";
    }

    // Methods
    public function enrollStudent(Student $student)
    {
        if ($this->is_full) {
            throw new \Exception('Class is at full capacity');
        }

        $student->update(['class_id' => $this->id]);
        $this->increment('current_enrollment');

        return true;
    }

    public function removeStudent(Student $student)
    {
        if ($student->class_id !== $this->id) {
            throw new \Exception('Student is not in this class');
        }

        $student->update(['class_id' => null]);
        $this->decrement('current_enrollment');

        return true;
    }

    public function updateEnrollmentCount()
    {
        $count = $this->activeStudents()->count();
        $this->update(['current_enrollment' => $count]);

        return $count;
    }

    public function getSubjects()
    {
        return Subject::whereHas('teacherSubjects', function ($query) {
            $query->where('class_id', $this->id)
                  ->where('is_active', true);
        })->distinct()->get();
    }
}
```

#### 6. Payment Model (Updated with Enums)

```php
// app/Models/Payment.php
<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Enums\PaymentPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'payment_code',
        'invoice_number',
        'student_id',
        'academic_year',
        'amount',
        'discount_amount',
        'paid_amount',
        'balance',
        'payment_type',
        'payment_period',
        'payment_method',
        'payment_month',
        'payment_date',
        'due_date',
        'status',
        'khqr_reference',
        'bank_reference',
        'receipt_number',
        'description',
        'notes',
        'received_by',
        'paid_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_type' => PaymentType::class,
        'payment_period' => PaymentPeriod::class,
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->payment_code)) {
                $model->payment_code = self::generatePaymentCode();
            }

            if (empty($model->invoice_number)) {
                $model->invoice_number = self::generateInvoiceNumber();
            }

            // Calculate balance
            $model->balance = $model->amount - $model->discount_amount - $model->paid_amount;

            // Update status based on payment
            if ($model->balance <= 0) {
                $model->status = 'paid';
                $model->paid_at = now();
            } elseif ($model->paid_amount > 0) {
                $model->status = 'partial';
            }
        });

        static::updating(function ($model) {
            // Recalculate balance
            $model->balance = $model->amount - $model->discount_amount - $model->paid_amount;

            // Update status
            if ($model->balance <= 0) {
                $model->status = 'paid';
                if (!$model->paid_at) {
                    $model->paid_at = now();
                }
            } elseif ($model->paid_amount > 0) {
                $model->status = 'partial';
            } elseif ($model->due_date < now() && $model->status !== 'cancelled') {
                $model->status = 'overdue';
            }
        });
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                     ->orWhere(function ($q) {
                         $q->where('due_date', '<', now())
                           ->whereNotIn('status', ['paid', 'cancelled']);
                     });
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('payment_code', 'like', "%{$search}%")
              ->orWhere('invoice_number', 'like', "%{$search}%")
              ->orWhere('receipt_number', 'like', "%{$search}%")
              ->orWhereHas('student', function ($sq) use ($search) {
                  $sq->where('student_code', 'like', "%{$search}%")
                     ->orWhere('first_name', 'like', "%{$search}%")
                     ->orWhere('last_name', 'like', "%{$search}%");
              });
        });
    }

    // Accessors
    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'overdue' ||
               ($this->due_date < now() && !in_array($this->status, ['paid', 'cancelled']));
    }

    public function getNetAmountAttribute()
    {
        return $this->amount - $this->discount_amount;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->amount == 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->amount) * 100, 2);
    }

    // Methods
    public static function generatePaymentCode()
    {
        $prefix = 'PAY';
        $date = date('Ymd');
        $lastPayment = self::where('payment_code', 'like', "{$prefix}{$date}%")
                           ->orderBy('payment_code', 'desc')
                           ->first();

        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->payment_code, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}{$year}{$month}%")
                           ->orderBy('invoice_number', 'desc')
                           ->first();

        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function generateReceiptNumber()
    {
        if ($this->receipt_number) {
            return $this->receipt_number;
        }

        $prefix = 'RCP';
        $date = date('Ymd');
        $number = str_pad($this->id, 6, '0', STR_PAD_LEFT);

        $this->update(['receipt_number' => $prefix . $date . $number]);

        return $this->receipt_number;
    }

    public function recordPayment($amount, $method = 'cash', $reference = null)
    {
        $this->paid_amount += $amount;
        $this->payment_method = $method;
        $this->payment_date = now();

        if ($reference) {
            if ($method === 'khqr') {
                $this->khqr_reference = $reference;
            } else {
                $this->bank_reference = $reference;
            }
        }

        if (auth()->check()) {
            $this->received_by = auth()->id();
        }

        $this->save();

        return $this;
    }

    public function applyDiscount($amount, $reason = null)
    {
        $this->discount_amount = $amount;

        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . ' | ' : '') . "Discount: {$reason}";
        }

        $this->save();

        return $this;
    }

    public function markAsPaid()
    {
        $this->status = 'paid';
        $this->paid_amount = $this->amount - $this->discount_amount;
        $this->balance = 0;
        $this->paid_at = now();
        $this->save();

        return $this;
    }

    public function cancel($reason = null)
    {
        $this->status = 'cancelled';

        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . ' | ' : '') . "Cancelled: {$reason}";
        }

        $this->save();

        return $this;
    }
}
```

#### 7. Pivot Table Models

```php
// app/Models/StudentSubject.php
<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentSubject extends Model
{
    use HasFactory;

    protected $table = 'student_subjects';

    protected $fillable = [
        'uuid',
        'student_id',
        'subject_id',
        'teacher_id',
        'academic_year',
        'enrolled_date',
        'completion_date',
        'score',
        'grade',
        'status',
        'notes',
    ];

    protected $casts = [
        'enrolled_date' => 'date',
        'completion_date' => 'date',
        'score' => 'decimal:2',
        'status' => EnrollmentStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    // Methods
    public function calculateGrade()
    {
        if ($this->score === null) {
            return null;
        }

        $gradeScale = [
            ['min' => 90, 'grade' => 'A'],
            ['min' => 80, 'grade' => 'B'],
            ['min' => 70, 'grade' => 'C'],
            ['min' => 60, 'grade' => 'D'],
            ['min' => 50, 'grade' => 'E'],
            ['min' => 0, 'grade' => 'F'],
        ];

        foreach ($gradeScale as $scale) {
            if ($this->score >= $scale['min']) {
                return $scale['grade'];
            }
        }

        return 'F';
    }

    public function markAsCompleted($score = null)
    {
        $this->status = 'completed';
        $this->completion_date = now();

        if ($score !== null) {
            $this->score = $score;
            $this->grade = $this->calculateGrade();
        }

        $this->save();

        return $this;
    }
}
```

```php
// app/Models/TeacherSubject.php
<?php

namespace App\Models;

use App\Enums\TeacherRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeacherSubject extends Model
{
    use HasFactory;

    protected $table = 'teacher_subjects';

    protected $fillable = [
        'uuid',
        'teacher_id',
        'subject_id',
        'class_id',
        'academic_year',
        'assigned_date',
        'end_date',
        'role',
        'is_active',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'role' => TeacherRole::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopePrimary($query)
    {
        return $query->where('role', 'primary');
    }

    // Methods
    public function deactivate()
    {
        $this->is_active = false;
        $this->end_date = now();
        $this->save();

        return $this;
    }
}
```

#### 8. System Settings Model (Updated with Enums)

```php
// app/Models/SystemSetting.php
<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'type' => SettingType::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    // Methods
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    public static function set($key, $value, $type = 'string')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => self::prepareValue($value, $type),
                'type' => $type,
            ]
        );
    }

    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'json':
            case 'array':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }

    protected static function prepareValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'json':
            case 'array':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
}
```

#### 9. Activity Log Model

```php
// app/Models/ActivityLog.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'action',
        'module',
        'model_type',
        'model_id',
        'model_uuid',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'method',
        'url',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            if (empty($model->ip_address)) {
                $model->ip_address = request()->ip();
            }

            if (empty($model->user_agent)) {
                $model->user_agent = request()->userAgent();
            }

            if (empty($model->method)) {
                $model->method = request()->method();
            }

            if (empty($model->url)) {
                $model->url = request()->fullUrl();
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByModel($query, $modelType, $modelId = null)
    {
        $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public static function log($action, $model = null, $oldValues = null, $newValues = null, $metadata = [])
    {
        $data = [
            'action' => $action,
            'metadata' => $metadata,
        ];

        if ($model) {
            $data['model_type'] = get_class($model);
            $data['model_id'] = $model->id;

            if (method_exists($model, 'getUuidAttribute')) {
                $data['model_uuid'] = $model->uuid;
            }

            // Determine module from model
            $data['module'] = strtolower(class_basename($model));
        }

        if ($oldValues) {
            $data['old_values'] = is_array($oldValues) ? $oldValues : $oldValues->toArray();
        }

        if ($newValues) {
            $data['new_values'] = is_array($newValues) ? $newValues : $newValues->toArray();
        }

        return self::create($data);
    }

    public function getChangedAttributes()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];

        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue != $newValue) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changed;
    }
}
```

### Creating Enums with Artisan Commands

#### Option 1: Custom Artisan Command for Enum Generation

Create a custom command to generate enum classes:

```php
// app/Console/Commands/MakeEnum.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeEnum extends Command
{
    protected $signature = 'make:enum {name} {--values=} {--type=string}';

    protected $description = 'Create a new enum class';

    public function handle()
    {
        $name = $this->argument('name');
        $values = $this->option('values');
        $type = $this->option('type');

        $enumPath = app_path("Enums/{$name}.php");

        if (file_exists($enumPath)) {
            $this->error("Enum {$name} already exists!");
            return 1;
        }

        // Create Enums directory if it doesn't exist
        if (!is_dir(app_path('Enums'))) {
            mkdir(app_path('Enums'), 0755, true);
        }

        $stub = $this->getStub($name, $values, $type);

        file_put_contents($enumPath, $stub);

        $this->info("Enum {$name} created successfully!");

        return 0;
    }

    protected function getStub($name, $values, $type)
    {
        $namespace = "App\\Enums";
        $cases = '';

        if ($values) {
            $valueArray = explode(',', $values);
            foreach ($valueArray as $value) {
                $value = trim($value);
                $constant = strtoupper(Str::snake($value));
                $cases .= "    case {$constant} = '{$value}';\n";
            }
        } else {
            // Default template
            $cases = "    case ACTIVE = 'active';\n";
            $cases .= "    case INACTIVE = 'inactive';\n";
        }

        return <<<PHP
<?php

namespace {$namespace};

enum {$name}: {$type}
{
{$cases}
    public function label(): string
    {
        return match(\$this) {
            // Add your labels here
            default => ucfirst(str_replace('_', ' ', \$this->value)),
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        \$options = [];
        foreach (self::cases() as \$case) {
            \$options[\$case->value] = \$case->label();
        }
        return \$options;
    }
}
PHP;
    }
}
```

Register the command in your Kernel:

```php
// app/Console/Kernel.php
protected $commands = [
    Commands\MakeEnum::class,
];
```

#### Usage Examples:

```bash
# Create a basic enum
php artisan make:enum Status

# Create enum with specific values
php artisan make:enum Gender --values=male,female,other

# Create enum with integer type
php artisan make:enum Priority --type=int --values=1,2,3,4,5

# Create payment status enum
php artisan make:enum PaymentStatus --values=pending,paid,partial,overdue,cancelled

# Create user role enum
php artisan make:enum UserRole --values=admin,staff,teacher,student
```

#### Option 2: Using a Package (Laravel-Enum)

Install the package:

```bash
composer require bensampo/laravel-enum
```

Generate enum:

```bash
    php artisan make:enum UserRole
    php artisan make:enum Gender --values=male,female,other
    php artisan make:enum StudentType --values=regular,monk
    php artisan make:enum StudentStatus --values=active,inactive,graduated,dropped,suspended
    php artisan make:enum Shift --values=morning,afternoon,evening,night,weekend
    php artisan make:enum EmploymentType --values=full_time,part_time,contract
    php artisan make:enum SubjectType --values=core,elective,extra
    php artisan make:enum EnrollmentStatus --values=active,dropped,completed,failed
    php artisan make:enum TeacherRole --values=primary,assistant,substitute
    php artisan make:enum PaymentType --values=registration,tuition,exam,certificate,other
    php artisan make:enum PaymentPeriod --values=monthly,quarterly,semester,yearly,one_time
    php artisan make:enum PaymentMethod --values=cash,bank_transfer,khqr,aba,acleda,wing
    php artisan make:enum PaymentStatus --values=pending,paid,partial,overdue,cancelled
    php artisan make:enum SettingType --values=string,number,boolean,json,array
       3278 +
       3279 +  echo "All enums created successfully!"
```

#### Option 3: Advanced Custom Command with Interactive php

// app/Console/Commands/MakeEnumIntphp

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeEnumInteractive extends Command
{
protected $signature = 'make:enum:interactive';

    protected $description = 'Create a new enum class interactively';

    public function handle()
    {
        $name = $this->ask('What is the enum name? (e.g., UserStatus)');

        $type = $this->choice(
            'What is the backing type?',
            ['string', 'int'],
            0
        );

        $values = [];
        $labels = [];

        $this->info('Enter enum values (press enter with empty value to finish):');

        while (true) {
            $value = $this->ask('Enter value (or press enter to finish)');

            if (empty($value)) {
                break;
            }

            $label = $this->ask("Enter label for '{$value}'", ucfirst(str_replace('_', ' ', $value)));

            $values[] = $value;
            $labels[$value] = $label;
        }

        if (empty($values)) {
            $this->error('No values provided!');
            return 1;
        }

        $addColors = $this->confirm('Do you want to add colors for each value?');
        $colors = [];

        if ($addColors) {
            foreach ($values as $value) {
                $colors[$value] = $this->ask("Enter color for '{$value}'", 'gray');
            }
        }

        $this->generateEnum($name, $type, $values, $labels, $colors);

        $this->info("Enum {$name} created successfully!");

        return 0;
    }

    protected function generateEnum($name, $type, $values, $labels, $colors = [])
    {
        $enumPath = app_path("Enumsphp");

        if (!is_dir(app_path('Enums'))) {
            mkdir(app_path('Enums'), 0755, true);
        }

        $cases = '';
        $labelCases = '';
        $colorCases = '';

        foreach ($values as $value) {
            $constant = strtoupper(Str::snake($value));
            $label = $labels[$value] ?? ucfirst($value);

            if ($type === 'int') {
                $cases .= "    case {$constant} = " . (array_search($value, $values) + 1) . ";\n";
            } else {
                $cases .= "    case {$constant} = '{$value}';\n";
            }

            $labelCases .= "            self::{$constant} => '{$label}',\n";

            if (!empty($colors)) {
                $color = $colors[$value] ?? 'gray';
                $colorCases .= "            self::{$constant} => '{$color}',\n";
            }
        }

        $colorMethod = '';
        if (!empty($colors)) {
            $colorMetPHP


    public function color(): string
    {
        return match(\$this) {

{$colorCases} PHP;
}

        $sphp

namespace App\\Enums;

enum {$name}: {$type}
{
{$cases}
    public function label(): string
    {
        return match(\$this) {
{$labelCases} };
}{$colorMethod}

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        \$options = [];
        foreach (self::cases() as \$case) {
            \$options[\$case->value] = \$case->label();
        }
        return \$optioPHP;

        file_put_contents($enumPath, $stub);
    }

}

```

#### Custom Command for Pivot Table Creaphp
// app/Console/Commands/MakePivot.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakePivot extends Command
{
    protected $signature = 'make:pivot {firstModel} {secondModel} {--timestamps} {--softdeletes}';

    protected $description = 'Create a new pivot table migration';

    public function handle()
    {
        $firstModel = Str::snake(Str::singular($this->argument('firstModel')));
        $secondModel = Str::snake(Str::singular($this->argument('secondModel')));

        // Sort alphabetically as per Laravel convention
        $models = collect([$firstModel, $secondModel])->sort()->values();
        $tableName = $models->implode('_');

        $migrationName = "create_{$tableName}_table";

        // Create migration using artisan command
        $this->call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName
        ]);

        $this->info("Pivot table migration '{$migrationName}' created successfully!");
        $this->info("Table name: {$tableName}");
        $this->info("Add your foreign keys and additional columns to the migration file.");

        // Show example migration content
        $this->line("\nExample migration content:");
        $this->comment($this->getExampleContent($models->toArray(), $tableName));

        return 0;
    }

    protected function getExampleContent($models, $tableName)
    {
        $firstModel = $models[0];
        $secondModel = $models[1];
        $timestamps = $this->option('timestamps') ? "\n            \$table->timestamps();" : "";
        $softDeletes = $this->option('softdeletes') ? "\n            \$table->softDeletes();" : "";

        return <<<PHP
Schema::create('{$tableName}', function (Blueprint \$table) {
    \$table->id();
    \$table->uuid('uuid')->unique();
    \$table->foreignId('{$firstModel}_id')->constrained()->onDelete('cascade');
    \$table->foreignId('{$secondModel}_id')->constrained()->onDelete('cascade');

    // Add your custom columns here
    // \$table->string('status')->default('active');
    // \$table->date('assigned_date')->nullable();
    {$timestamps}{$softDeletes}

    // Composite unique constraint
    \$table->unique(['{$firstModel}_id', '{$secondModel}_id']);

    // Indexes
    \$table->index('uuid');
    \$table->index('{$firstModel}_id');
    \$table->index('{$secondModel}_id');
});
PHP;
    }
}
```

Usage:

```bash
# Basic pivot table
php artisan make:pivot students subjects

# With timestamps
php artisan make:pivot students subjects --timestamps

# With soft deletes
php artisan make:pivot teachers subjects --softdeletes
```

#### Bash Script for Quick Enum Creation

Create a bash script `create-enums.sh`:

```bash
#!/bin/bash

# Create all enums for the School Registration System
php artisan make:enum Gender --values=male,female,other
php artisan make:enum StudentType --values=regular,monk
php artisan make:enum StudentStatus --values=active,inactive,graduated,dropped,suspended
php artisan make:enum Shift --values=morning,afternoon,evening,night,weekend
php artisan make:enum EmploymentType --values=full_time,part_time,contract
php artisan make:enum SubjectType --values=core,elective,extra
php artisan make:enum EnrollmentStatus --values=active,dropped,completed,failed
php artisan make:enum TeacherRole --values=primary,assistant,substitute
php artisan make:enum PaymentType --values=registration,tuition,exam,certificate,other
php artisan make:enum PaymentPeriod --values=monthly,quarterly,semester,yearly,one_time
php artisan make:enum PaymentMethod --values=cash,bank_transfer,khqr,aba,acleda,wing
php artisan make:enum PaymentStatus --values=pending,paid,partial,overdue,cancelled
php artisan make:enum SettingType --values=string,number,boolean,json,array

echo "All enums created successfully!"
```

Run it:

```bash
chmod +x create-enums.sh
./create-enums.sh
```

### Supabase Configuration for Laravel

#### 1. Install PostgreSQL Driver

```bash
composer require doctrine/dbal
```

#### 2. Configure .env for Supabase

```env
DB_CONNECTION=pgsql
DB_HOST=your-project.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password
```

#### 3. Run Migrations

```bash
php artisan migrate
```

### Important Notes for Supabase

1. **Row Level Security (RLS)**: Supabase has RLS enabled by default. You may need to configure policies or disable RLS for tables accessed by Laravel.

2. **Connection Pooling**: For production, use Supabase's connection pooler:

```env
DB_HOST=your-project.pooler.supabase.com
DB_PORT=6543
```

3. **SSL Connection**: Add to database config:

```php
// config/database.php
'pgsql' => [
    // ... other settings
    'sslmode' => env('DB_SSLMODE', 'require'),
]
```

---

## API Endpoints

### Authentication Endpoints

```
POST   /api/auth/login
       Body: { email, password }
       Response: { user, token }

POST   /api/auth/logout
       Headers: Authorization: Bearer {token}

GET    /api/auth/user
       Headers: Authorization: Bearer {token}
       Response: { user }

POST   /api/auth/refresh
       Headers: Authorization: Bearer {token}
```

### Student Management

```
GET    /api/students
       Query: ?page=1&per_page=20&search=john&class_id=1&status=active
       Response: { data: [], meta: { pagination } }

POST   /api/students
       Body: { student_data }
       Response: { student, message }

GET    /api/students/{id}
       Response: { student }

PUT    /api/students/{id}  [Admin only]
       Body: { updated_data }

DELETE /api/students/{id}  [Admin only]
       Response: { message }

GET    /api/students/{id}/subjects
       Response: { subjects_with_teachers }

GET    /api/students/{id}/payments
       Response: { payments }
```

### Teacher Management [Admin Only]

```
GET    /api/teachers
       Query: ?search=smith&is_active=true
       Response: { data: [], meta: { pagination } }

POST   /api/teachers
       Body: { teacher_data, subject_ids: [] }

GET    /api/teachers/{id}

PUT    /api/teachers/{id}

DELETE /api/teachers/{id}

GET    /api/teachers/{id}/subjects
       Response: { subjects }

POST   /api/teachers/{id}/subjects
       Body: { subject_ids: [] }
```

### Subject Management [Admin Only]

```
GET    /api/subjects
       Query: ?grade_level=1&is_active=true

POST   /api/subjects
       Body: { subject_data }

GET    /api/subjects/{id}

PUT    /api/subjects/{id}

DELETE /api/subjects/{id}

GET    /api/subjects/{id}/teachers
```

### Class Management [Admin Only]

```
GET    /api/classes
       Query: ?grade_level=1&academic_year=2024-2025

POST   /api/classes

GET    /api/classes/{id}

PUT    /api/classes/{id}

DELETE /api/classes/{id}

GET    /api/classes/{id}/students
```

### Payment Management

```
GET    /api/payments
       Query: ?student_id=1&status=pending&date_from=2024-01-01

POST   /api/payments
       Body: { payment_data }

GET    /api/payments/{id}

PUT    /api/payments/{id}

POST   /api/payments/khqr/verify
       Body: { reference_number }
```

### User Management [Admin Only]

```
GET    /api/users

POST   /api/users
       Body: { name, email, password, is_admin }

GET    /api/users/{id}

PUT    /api/users/{id}

PUT    /api/users/{id}/toggle-status

DELETE /api/users/{id}
```

### Reports [Admin Only]

```
GET    /api/reports/dashboard-stats
GET    /api/reports/registrations?period=daily&from=2024-01-01
GET    /api/reports/payments?period=monthly
GET    /api/reports/class-capacity
GET    /api/reports/teacher-workload
```

### System Settings [Admin Only]

```
GET    /api/settings
POST   /api/settings
PUT    /api/settings/{key}
```

---

## Implementation Phases

### Phase 1: Foundation (Week 1)

#### Backend Setup

1. **Laravel Installation & Configuration**

    ```bash
    composer create-project laravel/laravel school-registration
    cd school-registration

    # Install dependencies
    composer require laravel/sanctum
    composer require doctrine/dbal

    # Configure Supabase in .env
    # Run migrations
    php artisan migrate
    ```

2. **Create Models with Relationships**

    ```php
    // app/Models/Student.php
    class Student extends Model
    {
        use HasFactory, SoftDeletes;

        protected $fillable = [
            'student_code', 'first_name', 'last_name',
            'date_of_birth', 'gender', 'student_type',
            'phone', 'email', 'address', 'parent_name',
            'parent_phone', 'emergency_contact', 'class_id',
            'shift', 'registration_date', 'academic_year',
            'status', 'created_by'
        ];

        public function class()
        {
            return $this->belongsTo(ClassModel::class, 'class_id');
        }

        public function subjects()
        {
            return $this->belongsToMany(Subject::class, 'student_subjects')
                        ->withPivot('teacher_id', 'enrolled_date', 'status')
                        ->withTimestamps();
        }

        public function payments()
        {
            return $this->hasMany(Payment::class);
        }

        public function creator()
        {
            return $this->belongsTo(User::class, 'created_by');
        }

        // Accessor for full name
        public function getFullNameAttribute()
        {
            return "{$this->first_name} {$this->last_name}";
        }
    }
    ```

3. **Setup Authentication with Sanctum**

    ```php
    // app/Http/Controllers/AuthController.php
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    ```

#### Frontend Setup

1. **Next.js Installation**

    ```bash
    npx create-next-app@latest school-registration-frontend
    cd school-registration-frontend

    # Install dependencies
    npm install axios zustand react-hook-form zod
    npm install @hookform/resolvers
    npx shadcn-ui@latest init
    ```

2. **Configure API Client**

    ```typescript
    // lib/api.ts
    import axios from "axios";

    const api = axios.create({
        baseURL: process.env.NEXT_PUBLIC_API_URL,
        headers: {
            "Content-Type": "application/json",
        },
    });

    api.interceptors.request.use((config) => {
        const token = localStorage.getItem("token");
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    });

    export default api;
    ```

### Phase 2: Core Features (Week 2)

#### Admin Features Implementation

1. **Teacher Management**

    - CRUD operations for teachers
    - Subject assignment interface
    - Teacher list with filters

2. **Subject Management**

    - CRUD operations for subjects
    - Grade level configuration
    - Fee settings per subject

3. **Class Management**

    - Create classes with sections
    - Set capacity limits
    - Assign room numbers

4. **User Management**
    - Create Staff/Admin accounts
    - Role assignment
    - Account activation/deactivation

#### Registration Flow

1. **Multi-step Form Implementation**

    ```typescript
    // components/RegistrationForm.tsx
    const steps = [
        { id: 1, name: "Student Information" },
        { id: 2, name: "Student Type" },
        { id: 3, name: "Registration Date" },
        { id: 4, name: "Payment Configuration" },
        { id: 5, name: "Subject & Teacher Assignment" },
        { id: 6, name: "Class Information" },
        { id: 7, name: "Study Shift" },
        { id: 8, name: "Review & Confirm" },
    ];
    ```

2. **Form Validation with Zod**
    ```typescript
    const studentSchema = z.object({
        first_name: z.string().min(2),
        last_name: z.string().min(2),
        date_of_birth: z.date(),
        gender: z.enum(["male", "female", "other"]),
        student_type: z.enum(["regular", "monk"]),
        // ... other fields
    });
    ```

### Phase 3: Dashboard & Reports (Week 3)

#### Dashboard Implementation

1. **Staff Dashboard**

    - Registration statistics
    - Quick registration button
    - Recent registrations list

2. **Admin Dashboard**
    - System-wide statistics
    - Charts and analytics
    - User activity feed
    - Quick admin actions

#### Reports Module

1. **Registration Reports**

    - Daily/Monthly summaries
    - Student distribution charts

2. **Payment Reports**

    - Collection summaries
    - Outstanding fees
    - Payment method analysis

3. **Academic Reports**
    - Class rosters
    - Teacher workload
    - Subject enrollment

### Phase 4: Payment Integration (Week 4)

#### KHQR Bakong Integration

1. **Setup Bakong API**

    ```php
    // app/Services/BakongService.php
    class BakongService
    {
        public function generateQR($amount, $reference)
        {
            // Integration with KHQR API
        }

        public function verifyPayment($reference)
        {
            // Verify payment status
        }
    }
    ```

2. **Payment Recording**
    - Manual cash recording
    - Bank transfer verification
    - Receipt generation

### Phase 5: Testing & Optimization (Week 5)

#### Testing Strategy

1. **Backend Testing**

    ```bash
    # Unit tests
    php artisan test --filter=StudentTest

    # Feature tests
    php artisan test --filter=RegistrationTest
    ```

2. **Frontend Testing**

    ```bash
    # Component tests
    npm run test

    # E2E tests
    npm run test:e2e
    ```

#### Performance Optimization

1. **Database Optimization**

    - Add proper indexes
    - Optimize queries
    - Implement caching

2. **Frontend Optimization**
    - Code splitting
    - Image optimization
    - Lazy loading

### Phase 6: Deployment (Week 6)

#### Production Setup

1. **Backend Deployment**

    ```bash
    # Server setup
    - Ubuntu 22.04 LTS
    - Nginx
    - PHP 8.2
    - Supervisor for queues

    # Deploy commands
    git pull origin main
    composer install --optimize-autoloader
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    ```

2. **Frontend Deployment**

    ```bash
    # Vercel deployment
    npm run build
    vercel --prod

    # Or self-hosted
    npm run build
    pm2 start npm --name "school-frontend" -- start
    ```

---

## Detailed Implementation Steps

### Step 1: Project Initialization

#### Laravel Backend

```bash
# Create project
composer create-project laravel/laravel school-registration-api

# Install packages
composer require laravel/sanctum
composer require doctrine/dbal

# Setup Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Create all models
php artisan make:model Student -mcr
php artisan make:model Teacher -mcr
php artisan make:model Subject -mcr
php artisan make:model ClassModel -mcr
php artisan make:model Payment -mcr

# Create middleware
php artisan make:middleware IsAdmin
```

#### Next.js Frontend

```bash
# Create project with TypeScript
npx create-next-app@latest school-registration-frontend --typescript --tailwind --app

# Install UI library
npx shadcn-ui@latest init

# Add components
npx shadcn-ui@latest add button
npx shadcn-ui@latest add form
npx shadcn-ui@latest add table
npx shadcn-ui@latest add card
npx shadcn-ui@latest add dialog

# Install additional packages
npm install axios zustand react-hook-form @hookform/resolvers zod
npm install recharts date-fns
```

### Step 2: Database Setup

1. **Run migrations in order**
2. **Create seeders for initial data**
3. **Setup model relationships**
4. **Configure Supabase RLS policies**

### Step 3: Authentication Implementation

1. **Laravel Sanctum setup**
2. **Login/logout endpoints**
3. **Frontend auth context**
4. **Protected routes**
5. **Role-based access control**

### Step 4: CRUD Operations

1. **Implement all controllers**
2. **Add validation rules**
3. **Create API resources**
4. **Frontend forms and tables**

### Step 5: Registration Flow

1. **Multi-step form component**
2. **Form state management**
3. **Validation at each step**
4. **Student ID generation**
5. **Receipt generation**

### Step 6: Dashboard & Analytics

1. **Statistics calculations**
2. **Chart implementations**
3. **Real-time updates**
4. **Export functionality**

---

## Security Considerations

### Backend Security

1. **Input Validation**

    - Validate all inputs
    - Sanitize data
    - Use prepared statements

2. **Authentication & Authorization**

    - Rate limiting on login
    - Password requirements
    - Session management
    - Role-based middleware

3. **API Security**
    - CORS configuration
    - API rate limiting
    - Request throttling

### Frontend Security

1. **XSS Prevention**

    - Sanitize user inputs
    - Content Security Policy

2. **Authentication**
    - Secure token storage
    - Auto-logout on inactivity
    - HTTPS only

---

## Testing Strategy

### Backend Tests

```php
// tests/Feature/RegistrationTest.php
class RegistrationTest extends TestCase
{
    public function test_staff_can_register_student()
    {
        $staff = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($staff)
            ->post('/api/students', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                // ... other fields
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('students', [
            'first_name' => 'John'
        ]);
    }
}
```

### Frontend Tests

```typescript
// __tests__/RegistrationForm.test.tsx
describe("RegistrationForm", () => {
    it("validates required fields", async () => {
        render(<RegistrationForm />);

        const submitButton = screen.getByText("Submit");
        fireEvent.click(submitButton);

        expect(
            await screen.findByText("First name is required")
        ).toBeInTheDocument();
    });
});
```

---

## Deployment Guide

### Prerequisites

-   VPS with Ubuntu 22.04
-   Domain name configured
-   SSL certificate (Let's Encrypt)
-   Supabase account

### Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install nginx mysql-server php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

# Install PM2
sudo npm install -g pm2
```

### Nginx Configuration

```nginx
# /etc/nginx/sites-available/school-registration
server {
    listen 80;
    server_name api.yourschool.com;
    root /var/www/school-registration-api/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

server {
    listen 80;
    server_name app.yourschool.com;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Deployment Scripts

```bash
# deploy.sh
#!/bin/bash

# Backend deployment
cd /var/www/school-registration-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan queue:restart

# Frontend deployment
cd /var/www/school-registration-frontend
git pull origin main
npm install
npm run build
pm2 restart school-frontend
```

---

## Monitoring & Maintenance

### Application Monitoring

1. **Error Tracking**: Sentry
2. **Performance**: New Relic
3. **Uptime**: UptimeRobot
4. **Logs**: Laravel Telescope

### Backup Strategy

```bash
# Daily database backup
0 2 * * * pg_dump -h your-project.supabase.co -U postgres -d postgres > /backups/db_$(date +%Y%m%d).sql

# Weekly full backup
0 3 * * 0 tar -czf /backups/full_$(date +%Y%m%d).tar.gz /var/www/
```

### Maintenance Checklist

-   [ ] Daily: Check error logs
-   [ ] Weekly: Review performance metrics
-   [ ] Monthly: Update dependencies
-   [ ] Quarterly: Security audit

---

## Troubleshooting Guide

### Common Issues

1. **CORS Errors**

    ```php
    // config/cors.php
    'allowed_origins' => [env('FRONTEND_URL')],
    ```

2. **Supabase Connection Issues**

    - Check connection pooler
    - Verify SSL settings
    - Check RLS policies

3. **Payment Integration Failures**
    - Verify API credentials
    - Check network connectivity
    - Review error logs

---

## Conclusion

This comprehensive guide covers the entire development lifecycle of the School Registration System. Follow the phases sequentially for best results, and adjust based on your specific requirements. Remember to:

1. Start with MVP features
2. Test thoroughly at each phase
3. Document as you build
4. Get user feedback early
5. Iterate based on actual usage

The system is designed to be scalable and maintainable, with clear separation of concerns and modern best practices.

---

## Current Implementation Status

**Last Updated:** October 6, 2025

### ✅ Completed Features

#### Phase 1: Foundation (100% Complete)

-   ✅ Laravel 11 installation and configuration
-   ✅ Database setup with migrations
-   ✅ Models created:
    -   Student
    -   Teacher
    -   Subject
    -   Classroom
    -   Payment
    -   PaywayTransaction
    -   PaywayPushback
    -   User (with authentication)
    -   StudentSubject (pivot)
    -   TeacherSubject (pivot)
-   ✅ Laravel Sanctum authentication configured
-   ✅ API route structure established

#### Phase 2: Core Features (100% Complete)

-   ✅ Teacher Management (Full CRUD)

    -   `GET /api/user/v1/teachers` - List teachers
    -   `POST /api/user/v1/teachers` - Create teacher
    -   `GET /api/user/v1/teachers/{id}` - Show teacher
    -   `PUT /api/user/v1/teachers/{id}` - Update teacher
    -   `DELETE /api/user/v1/teachers/{id}` - Delete teacher

-   ✅ Subject Management (Full CRUD)

    -   `GET /api/user/v1/subjects` - List subjects
    -   `POST /api/user/v1/subjects` - Create subject
    -   `GET /api/user/v1/subjects/{id}` - Show subject
    -   `PUT /api/user/v1/subjects/{id}` - Update subject
    -   `DELETE /api/user/v1/subjects/{id}` - Delete subject

-   ✅ Classroom Management (Full CRUD)

    -   `GET /api/user/v1/classrooms` - List classrooms
    -   `POST /api/user/v1/classrooms` - Create classroom
    -   `GET /api/user/v1/classrooms/{id}` - Show classroom
    -   `PUT /api/user/v1/classrooms/{id}` - Update classroom
    -   `DELETE /api/user/v1/classrooms/{id}` - Delete classroom

-   ✅ Student Management (Full CRUD)

    -   `GET /api/user/v1/students` - List students
    -   `POST /api/user/v1/students` - Create student
    -   `GET /api/user/v1/students/{id}` - Show student
    -   `PUT /api/user/v1/students/{id}` - Update student
    -   `DELETE /api/user/v1/students/{id}` - Delete student

-   ✅ Authentication System
    -   `POST /api/user/v1/auth/register` - Register user
    -   `POST /api/user/v1/auth/login` - Login
    -   `DELETE /api/user/v1/auth/logout` - Logout
    -   `GET /api/user/v1/auth/user` - Get authenticated user

#### Phase 4: Payment Integration (100% Complete)

-   ✅ PayWay KHQR Integration

    -   `POST /api/payway/v1/khqr/generate` - Generate KHQR payment
    -   `POST /api/payway/v1/payment/status` - Check payment status
    -   `POST /api/payway/v1/webhook` - Receive PayWay callbacks

-   ✅ Payment Services

    -   PaywayService - Core payment logic
    -   PaywayCallbackService - Smart webhook URL handling (ngrok support)
    -   Hosted checkout page integration
    -   Transaction tracking
    -   Webhook callback handling
    -   Payment status updates

-   ✅ Documentation
    -   Complete PayWay integration guide
    -   Frontend implementation examples
    -   Webhook setup instructions
    -   Troubleshooting guide
    -   Testing procedures

---

### 🚧 In Progress / Pending Features

#### Phase 2: Core Features (Partially Complete - 80%)

-   ❌ User/Admin Management (Missing dedicated controller)
    -   Need: `/api/user/v1/users` endpoints for managing Staff/Admin accounts
    -   Need: Role-based access control middleware (IsAdmin)

#### Phase 3: Dashboard & Reports (Not Started - 0%)

-   ❌ Dashboard Statistics Endpoints

    -   Total students count
    -   Today's registrations
    -   Payment statistics
    -   Monthly revenue
    -   Student distribution by grade/class
    -   Teacher workload statistics

-   ❌ Reports Module
    -   Daily/Monthly registration reports
    -   Payment collection reports
    -   Outstanding fees reports
    -   Class roster reports
    -   Teacher workload reports
    -   Subject enrollment reports

#### Student Registration Flow (Partially Complete - 40%)

-   ✅ Basic student creation (CRUD)
-   ❌ 8-step multi-step registration form
    -   Step 1: Student Information ✅
    -   Step 2: Student Type (regular/monk) ⚠️
    -   Step 3: Registration Date ⚠️
    -   Step 4: Payment Configuration ❌
    -   Step 5: Subject & Teacher Assignment ❌
    -   Step 6: Class Information ⚠️
    -   Step 7: Study Shift ⚠️
    -   Step 8: Review & Confirm ❌

#### Student-Subject-Teacher Relationships (Partially Complete - 30%)

-   ✅ Database tables created (student_subjects, teacher_subjects)
-   ✅ Model relationships defined
-   ❌ Enrollment endpoints
    -   Need: Enroll student in subjects
    -   Need: Assign teacher to student's subject
    -   Need: Track enrollment status
    -   Need: Remove/update enrollments

#### Payment Features (Partially Complete - 60%)

-   ✅ KHQR payment generation
-   ✅ Webhook handling
-   ✅ Transaction tracking
-   ❌ Manual payment recording (cash, bank transfer)
-   ❌ Receipt generation (PDF)
-   ❌ Payment history per student
-   ❌ Refund handling

#### Additional Features (Not Started - 0%)

-   ❌ Student ID auto-generation with custom format
-   ❌ Academic year management
-   ❌ Advanced search and filtering
-   ❌ Soft deletes implementation
-   ❌ Audit logging (who created/updated records)
-   ❌ File uploads (student photos, documents)
-   ❌ Notifications system
-   ❌ Email notifications for payments

#### Phase 5: Testing (Not Started - 0%)

-   ❌ Unit tests for models
-   ❌ Feature tests for API endpoints
-   ❌ Integration tests for payment flow
-   ❌ Performance testing

#### Phase 6: Deployment (Not Started - 0%)

-   ❌ Production server setup
-   ❌ CI/CD pipeline
-   ❌ Monitoring and logging
-   ❌ Backup strategy

---

### 📊 Overall Progress

| Category                  | Completion |
| ------------------------- | ---------- |
| **Backend Foundation**    | 100% ✅    |
| **Core CRUD Operations**  | 90% 🟡     |
| **Payment Integration**   | 100% ✅    |
| **Dashboard & Analytics** | 0% ❌      |
| **Advanced Features**     | 20% 🔴     |
| **Testing**               | 0% ❌      |
| **Documentation**         | 80% 🟡     |
| **Overall Project**       | **~50%**   |

---

### 🎯 Next Priority Tasks

#### Immediate (Week 1)

1. **User Management Controller**

    - Create UserController for managing Staff/Admin accounts
    - Implement role-based middleware (IsAdmin, IsStaff)
    - Add endpoints: GET/POST/PUT/DELETE `/api/user/v1/users`

2. **Dashboard Statistics**

    - Create DashboardController
    - Implement statistics endpoints:
        - GET `/api/user/v1/dashboard/stats` - Overall statistics
        - GET `/api/user/v1/dashboard/recent-registrations`
        - GET `/api/user/v1/dashboard/payment-summary`

3. **Student-Subject Enrollment**
    - Create EnrollmentController
    - Endpoints:
        - POST `/api/user/v1/students/{id}/enroll` - Enroll in subjects
        - GET `/api/user/v1/students/{id}/subjects` - Get enrolled subjects
        - DELETE `/api/user/v1/students/{id}/subjects/{subjectId}` - Unenroll

#### Short-term (Week 2-3)

4. **Payment Receipts**

    - Install PDF library (barryvdh/laravel-dompdf)
    - Create receipt template
    - Add endpoint: GET `/api/payway/v1/payments/{id}/receipt`

5. **Advanced Filters & Search**

    - Add query parameters to all list endpoints
    - Implement search by name, student code, class
    - Add date range filters
    - Pagination improvements

6. **Reports Module**
    - Create ReportController
    - Implement basic reports (registration, payments)
    - Export to PDF/Excel

#### Long-term (Week 4+)

7. **Multi-step Registration Flow**

    - Create step-based validation
    - Frontend integration guide
    - Form state management

8. **Testing Suite**

    - Write feature tests for all endpoints
    - Integration tests for payment flow
    - API documentation with Swagger

9. **Production Readiness**
    - Security audit
    - Performance optimization
    - Deployment preparation

---

### 📝 Notes

**Current Focus:** Payment integration is fully complete with PayWay KHQR. The system has solid foundation with all core models and basic CRUD operations.

**Technical Debt:**

-   Need to add validation for all endpoints
-   Missing API documentation (Swagger/OpenAPI)
-   No error handling standardization
-   Missing request/response transformers (API Resources)
-   No rate limiting configured

**Database Considerations:**

-   All pivot tables (student_subjects, teacher_subjects) are created but not fully utilized
-   Need to add indexes for performance on frequently queried columns
-   Consider adding full-text search indexes for student names

**Security Considerations:**

-   Implement role-based access control (RBAC) properly
-   Add request validation for all inputs
-   Implement API rate limiting
-   Add CORS configuration
-   Secure webhook endpoints

---

### 🔗 Related Documentation

-   [PayWay Integration Guide](./PAYWAY_INTEGRATION.md)
-   [Testing KHQR Guide](./TESTING_KHQR.md)
-   [ngrok Setup](./NGROK_SETUP.md)
-   [Sandbox Account Request](../PAYWAY_SANDBOX_REQUEST.md)

---

**Progress Tracking Sheet:** https://github.com/CHUNSOKNA1997/school-registration-system-api
