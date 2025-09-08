<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case STAFF = 'staff';
    
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::STAFF => 'Staff Member',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::ADMIN => 'Full system access with all permissions',
            self::STAFF => 'Limited access for student registration and viewing',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::ADMIN => 'red',
            self::STAFF => 'blue',
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::ADMIN => 'shield-check',
            self::STAFF => 'user',
        };
    }
    
    public function permissions(): array
    {
        return match($this) {
            self::ADMIN => [
                'students.create',
                'students.read',
                'students.update',
                'students.delete',
                'teachers.create',
                'teachers.read',
                'teachers.update',
                'teachers.delete',
                'subjects.create',
                'subjects.read',
                'subjects.update',
                'subjects.delete',
                'classes.create',
                'classes.read',
                'classes.update',
                'classes.delete',
                'users.create',
                'users.read',
                'users.update',
                'users.delete',
                'payments.create',
                'payments.read',
                'payments.update',
                'payments.delete',
                'reports.view',
                'settings.manage',
            ],
            self::STAFF => [
                'students.create',
                'students.read',
                'payments.create',
                'payments.read',
                'reports.view_limited',
            ],
        };
    }
    
    public function canAccessAdmin(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::STAFF => false,
        };
    }
    
    public function canManageTeachers(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::STAFF => false,
        };
    }
    
    public function canManageSubjects(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::STAFF => false,
        };
    }
    
    public function canManageUsers(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::STAFF => false,
        };
    }
    
    public function canEditStudents(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::STAFF => false,
        };
    }
    
    public function canDeleteStudents(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::STAFF => false,
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