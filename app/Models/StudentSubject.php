<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Enums\EnrollmentStatus;

class StudentSubject extends Pivot
{
    protected $table = 'student_subjects';

    protected $fillable = [
        'student_id',
        'subject_id',
        'teacher_id',
        'enrolled_date',
        'status',
        'grade',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_date' => 'date',
            'grade' => 'decimal:2',
            'status' => EnrollmentStatus::class,
        ];
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
}
