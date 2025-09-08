<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ACTIVE = 'active';
    case DROPPED = 'dropped';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Currently Enrolled',
            self::DROPPED => 'Dropped',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'blue',
            self::DROPPED => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }
    
    public function canEdit(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            self::DROPPED => false,
            self::COMPLETED => false,
            self::FAILED => false,
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