<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function createdStudents()
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    public function updatedStudents()
    {
        return $this->hasMany(Student::class, 'updated_by');
    }

    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeStaff($query)
    {
        return $query->where('is_admin', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isStaff(): bool
    {
        return !$this->is_admin;
    }
}
