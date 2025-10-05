<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Enums\Shift;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'uuid',
        'class_code',
        'name',
        'name_khmer',
        'grade_level',
        'section',
        'academic_year',
        'shift',
        'room_number',
        'capacity',
        'teacher_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'shift' => Shift::class,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($class) {
            if (empty($class->uuid)) {
                $class->uuid = (string) Str::uuid();
            }

            if (empty($class->class_code)) {
                $class->class_code = self::generateClassCode();
            }
        });
    }

    // Relationships
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
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

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return "{$this->name} - {$this->section}";
    }

    public function getCurrentStudentsCountAttribute()
    {
        return $this->students()->where('status', 'active')->count();
    }

    public function getAvailableSlotsAttribute()
    {
        return $this->capacity - $this->current_students_count;
    }

    public static function generateClassCode()
    {
        $year = date('Y');
        $lastClass = self::where('class_code', 'like', "C{$year}-%")
            ->orderBy('class_code', 'desc')
            ->first();

        if ($lastClass) {
            $lastNumber = (int) substr($lastClass->class_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "C{$year}-{$newNumber}";
    }
}
