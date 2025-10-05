<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Enums\Gender;
use App\Enums\EmploymentType;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'teacher_code',
        'first_name',
        'last_name',
        'khmer_name',
        'date_of_birth',
        'gender',
        'nationality',
        'phone',
        'email',
        'address',
        'emergency_contact',
        'emergency_contact_relationship',
        'education_level',
        'specialization',
        'employment_type',
        'hire_date',
        'contract_end_date',
        'salary',
        'bank_account',
        'id_card_number',
        'photo',
        'cv',
        'certificates',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'contract_end_date' => 'date',
            'salary' => 'decimal:2',
            'certificates' => 'array',
            'is_active' => 'boolean',
            'gender' => Gender::class,
            'employment_type' => EmploymentType::class,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($teacher) {
            if (empty($teacher->uuid)) {
                $teacher->uuid = (string) Str::uuid();
            }

            if (empty($teacher->teacher_code)) {
                $teacher->teacher_code = self::generateTeacherCode();
            }
        });
    }

    // Relationships
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')
            ->withPivot('class_id', 'academic_year', 'assigned_date', 'end_date', 'role', 'is_active')
            ->withTimestamps();
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
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public static function generateTeacherCode()
    {
        $year = date('Y');
        $lastTeacher = self::where('teacher_code', 'like', "T{$year}-%")
            ->orderBy('teacher_code', 'desc')
            ->first();

        if ($lastTeacher) {
            $lastNumber = (int) substr($lastTeacher->teacher_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "T{$year}-{$newNumber}";
    }
}
