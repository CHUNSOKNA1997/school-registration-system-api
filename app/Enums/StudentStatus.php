<?php

namespace App\Enums;

enum StudentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case GRADUATED = 'graduated';
    case DROPPED = 'dropped';
    case SUSPENDED = 'suspended';
    
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::GRADUATED => 'Graduated',
            self::DROPPED => 'Dropped Out',
            self::SUSPENDED => 'Suspended',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::GRADUATED => 'blue',
            self::DROPPED => 'red',
            self::SUSPENDED => 'orange',
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