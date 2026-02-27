<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'student_id',
        'payment_id',
        'user_id',
        'personal_email',
        'school_email',
        'payment_plan',
        'status',
        'paid_at',
        'activation_token_hash',
        'activation_token_expires_at',
        'onboarding_sent_at',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'activation_token_expires_at' => 'datetime',
            'onboarding_sent_at' => 'datetime',
            'data' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->uuid)) {
                $application->uuid = (string) Str::uuid();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
