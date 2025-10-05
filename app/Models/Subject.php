<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'credits' => 'integer',
            'hours_per_week' => 'integer',
            'fee' => 'decimal:2',
            'monthly_fee' => 'decimal:2',
            'prerequisites' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subject) {
            if (empty($subject->uuid)) {
                $subject->uuid = (string) Str::uuid();
            }

            if (empty($subject->subject_code)) {
                $subject->subject_code = self::generateSubjectCode();
            }
        });
    }

    // Relationships
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subjects')
            ->withPivot('teacher_id', 'enrolled_date', 'status', 'grade', 'remarks')
            ->withTimestamps();
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects')
            ->withPivot('class_id', 'academic_year', 'assigned_date', 'end_date', 'role', 'is_active')
            ->withTimestamps();
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

    // Helper methods
    public static function generateSubjectCode()
    {
        $lastSubject = self::orderBy('subject_code', 'desc')->first();

        if ($lastSubject) {
            $lastNumber = (int) substr($lastSubject->subject_code, 3);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "SUB{$newNumber}";
    }
}
