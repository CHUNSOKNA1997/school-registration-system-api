<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Enums\Gender;
use App\Enums\StudentType;
use App\Enums\Shift;

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

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'registration_date' => 'date',
            'documents' => 'array',
            'gender' => Gender::class,
            'student_type' => StudentType::class,
            'shift' => Shift::class,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->uuid)) {
                $student->uuid = (string) Str::uuid();
            }

            if (empty($student->student_code)) {
                $student->student_code = self::generateStudentCode();
            }
        });
    }

    // Relationships
    public function class()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
            ->withPivot('teacher_id', 'enrolled_date', 'status', 'grade', 'remarks')
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

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public static function generateStudentCode()
    {
        $year = date('Y');
        $lastStudent = self::where('student_code', 'like', "{$year}-%")
            ->orderBy('student_code', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->student_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$year}-{$newNumber}";
    }
}
