<?php

namespace App\Enums;

enum StudentStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case GRADUATED = 'graduated';
    case TRANSFERRED = 'transferred';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::GRADUATED => 'Graduated',
            self::TRANSFERRED => 'Transferred',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'red',
            self::GRADUATED => 'blue',
            self::TRANSFERRED => 'yellow',
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
