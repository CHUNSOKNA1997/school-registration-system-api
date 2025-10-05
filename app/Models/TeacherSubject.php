<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeacherSubject extends Pivot
{
    protected $table = 'teacher_subjects';

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
        'academic_year',
        'assigned_date',
        'end_date',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
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
        return $this->belongsTo(Classroom::class, 'class_id');
    }
}
