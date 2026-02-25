<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\Shift;
use App\Enums\StudentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademicTestDataSeeder extends Seeder
{
    private array $columnCache = [];

    public function run(): void
    {
        $year = (int) now()->format('Y');
        $academicYear = "{$year}-" . ($year + 1);

        $userId = $this->resolveSeederUserId();
        $teacherIds = $this->seedTeachers($year, 20);
        $classIds = $this->seedClasses($year, $academicYear, $teacherIds);
        $this->seedStudents($year, $academicYear, $classIds, $userId, 120);
        $this->syncClassroomEnrollment();
    }

    private function seedTeachers(int $year, int $targetCount): array
    {
        $currentCount = DB::table('teachers')->count();
        $toCreate = max(0, $targetCount - $currentCount);

        if ($toCreate === 0) {
            return DB::table('teachers')->pluck('id')->all();
        }

        $nextSequence = $this->nextSequence('teachers', 'teacher_code', "T{$year}-");
        $now = now();
        $rows = [];

        for ($i = 0; $i < $toCreate; $i++) {
            $sequence = $nextSequence + $i;
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();

            $row = [
                'uuid' => (string) Str::uuid(),
                'teacher_code' => sprintf('T%d-%04d', $year, $sequence),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => fake()->randomElement([Gender::MALE->value, Gender::FEMALE->value]),
                'nationality' => 'Cambodian',
                'phone' => '0' . fake()->numerify('########'),
                'email' => sprintf('teacher%s_%d@example.test', $year, $sequence),
                'is_active' => fake()->boolean(90),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->setIfColumn('teachers', $row, 'khmer_name', null);
            $this->setIfColumn('teachers', $row, 'date_of_birth', fake()->date('Y-m-d', '-24 years'));
            $this->setIfColumn('teachers', $row, 'emergency_contact', '0' . fake()->numerify('########'));
            $this->setIfColumn('teachers', $row, 'emergency_contact_relationship', fake()->randomElement(['Spouse', 'Sibling', 'Parent']));
            $this->setIfColumn('teachers', $row, 'address', fake()->address());
            $this->setIfColumn('teachers', $row, 'current_address', fake()->address());
            $this->setIfColumn('teachers', $row, 'permanent_address', fake()->address());
            $this->setIfColumn('teachers', $row, 'qualification', fake()->randomElement(['Bachelor', 'Master', 'PhD']));
            $this->setIfColumn('teachers', $row, 'education_level', fake()->randomElement(['Bachelor', 'Master', 'PhD']));
            $this->setIfColumn('teachers', $row, 'specialization', fake()->randomElement(['Math', 'English', 'Science', 'History', 'IT']));
            $this->setIfColumn('teachers', $row, 'employment_type', fake()->randomElement(EmploymentType::values()));
            $this->setIfColumn('teachers', $row, 'hire_date', fake()->date('Y-m-d', 'now'));
            $this->setIfColumn('teachers', $row, 'contract_end_date', fake()->optional()->date('Y-m-d', '+3 years'));
            $this->setIfColumn('teachers', $row, 'salary', fake()->randomFloat(2, 350, 1200));
            $this->setIfColumn('teachers', $row, 'bank_account', fake()->numerify('##########'));
            $this->setIfColumn('teachers', $row, 'id_card_number', fake()->numerify('############'));
            $this->setIfColumn('teachers', $row, 'documents', json_encode([]));
            $this->setIfColumn('teachers', $row, 'certificates', json_encode([]));
            $this->setIfColumn('teachers', $row, 'notes', fake()->optional()->sentence());

            $rows[] = $row;
        }

        DB::table('teachers')->insert($rows);

        return DB::table('teachers')->pluck('id')->all();
    }

    private function seedClasses(int $year, string $academicYear, array $teacherIds): array
    {
        $now = now();
        $grades = range(7, 12);
        $sections = ['A', 'B'];
        $counter = 1;

        foreach ($grades as $grade) {
            foreach ($sections as $section) {
                $name = "Grade {$grade}-{$section}";

                $row = [
                    'uuid' => (string) Str::uuid(),
                    'name' => $name,
                    'grade_level' => $grade,
                    'section' => $section,
                    'capacity' => fake()->numberBetween(30, 45),
                    'room_number' => sprintf('%d%s', $grade, $section),
                    'academic_year' => $academicYear,
                    'description' => "Classroom for {$name}",
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $this->setIfColumn('classes', $row, 'name_en', $name);
                $this->setIfColumn('classes', $row, 'name_kh', null);
                $this->setIfColumn('classes', $row, 'name_khmer', null);
                $this->setIfColumn('classes', $row, 'class_code', sprintf('C%d-%04d', $year, $counter));
                $this->setIfColumn('classes', $row, 'shift', fake()->randomElement([
                    Shift::MORNING->value,
                    Shift::AFTERNOON->value,
                    Shift::EVENING->value,
                ]));
                $this->setIfColumn('classes', $row, 'teacher_id', !empty($teacherIds) ? $teacherIds[($counter - 1) % count($teacherIds)] : null);
                $this->setIfColumn('classes', $row, 'current_enrollment', 0);

                DB::table('classes')->updateOrInsert(
                    [
                        'grade_level' => $grade,
                        'section' => $section,
                        'academic_year' => $academicYear,
                    ],
                    $row
                );

                $counter++;
            }
        }

        return DB::table('classes')
            ->where('academic_year', $academicYear)
            ->pluck('id')
            ->all();
    }

    private function seedStudents(
        int $year,
        string $academicYear,
        array $classIds,
        int $userId,
        int $targetCount
    ): void {
        $currentCount = DB::table('students')->count();
        $toCreate = max(0, $targetCount - $currentCount);

        if ($toCreate === 0) {
            return;
        }

        $nextSequence = $this->nextSequence('students', 'student_code', "STU{$year}-");
        $now = now();
        $rows = [];

        for ($i = 0; $i < $toCreate; $i++) {
            $sequence = $nextSequence + $i;
            $firstName = fake()->firstName();
            $lastName = fake()->lastName();
            $classId = !empty($classIds) ? fake()->randomElement($classIds) : null;

            $row = [
                'uuid' => (string) Str::uuid(),
                'student_code' => sprintf('STU%d-%04d', $year, $sequence),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => fake()->date('Y-m-d', '-8 years'),
                'gender' => fake()->randomElement([Gender::MALE->value, Gender::FEMALE->value]),
                'student_type' => fake()->randomElement(StudentType::values()),
                'nationality' => 'Cambodian',
                'parent_name' => fake()->name(),
                'parent_phone' => '0' . fake()->numerify('########'),
                'shift' => fake()->randomElement([
                    Shift::MORNING->value,
                    Shift::AFTERNOON->value,
                    Shift::EVENING->value,
                ]),
                'registration_date' => fake()->date('Y-m-d', 'now'),
                'academic_year' => $academicYear,
                'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'suspended']),
                'class_id' => $classId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->setIfColumn('students', $row, 'khmer_name', null);
            $this->setIfColumn('students', $row, 'place_of_birth', fake()->city());
            $this->setIfColumn('students', $row, 'phone', '0' . fake()->numerify('########'));
            $this->setIfColumn('students', $row, 'email', sprintf('student%s_%d@example.test', $year, $sequence));
            $this->setIfColumn('students', $row, 'current_address', fake()->address());
            $this->setIfColumn('students', $row, 'permanent_address', fake()->address());
            $this->setIfColumn('students', $row, 'parent_occupation', fake()->jobTitle());
            $this->setIfColumn('students', $row, 'emergency_contact', '0' . fake()->numerify('########'));
            $this->setIfColumn('students', $row, 'emergency_contact_relationship', fake()->randomElement(['Parent', 'Guardian', 'Sibling']));
            $this->setIfColumn('students', $row, 'previous_school', fake()->optional()->company() . ' School');
            $this->setIfColumn('students', $row, 'documents', json_encode([]));
            $this->setIfColumn('students', $row, 'notes', fake()->optional()->sentence());
            $this->setIfColumn('students', $row, 'created_by', $userId);
            $this->setIfColumn('students', $row, 'updated_by', $userId);

            $rows[] = $row;
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('students')->insert($chunk);
        }
    }

    private function syncClassroomEnrollment(): void
    {
        if (!$this->hasColumn('classes', 'current_enrollment') || !$this->hasColumn('students', 'class_id')) {
            return;
        }

        DB::table('classes')->update(['current_enrollment' => 0]);

        $counts = DB::table('students')
            ->select('class_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('class_id')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        foreach ($counts as $classId => $total) {
            DB::table('classes')
                ->where('id', $classId)
                ->update(['current_enrollment' => $total]);
        }
    }

    private function nextSequence(string $table, string $column, string $prefix): int
    {
        $lastCode = DB::table($table)
            ->where($column, 'like', "{$prefix}%")
            ->orderByDesc($column)
            ->value($column);

        if (!$lastCode) {
            return 1;
        }

        $lastSequence = (int) substr((string) $lastCode, -4);

        return $lastSequence > 0 ? $lastSequence + 1 : 1;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return $this->columnCache[$key];
    }

    private function setIfColumn(string $table, array &$row, string $column, mixed $value): void
    {
        if ($this->hasColumn($table, $column)) {
            $row[$column] = $value;
        }
    }

    private function resolveSeederUserId(): int
    {
        $adminId = User::query()->where('is_admin', true)->value('id');
        if ($adminId) {
            return $adminId;
        }

        $anyUserId = User::query()->value('id');
        if ($anyUserId) {
            return $anyUserId;
        }

        $user = User::query()->create([
            'name' => 'Seeder Admin',
            'email' => 'seeder-admin@school.com',
            'password' => Hash::make('12345678'),
            'is_admin' => true,
            'is_active' => true,
            'phone' => '099999999',
        ]);

        return $user->id;
    }
}
