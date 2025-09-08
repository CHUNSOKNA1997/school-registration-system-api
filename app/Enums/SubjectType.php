<?php

namespace App\Enums;

enum SubjectType: string
{
    case CORE = 'core';
    case ELECTIVE = 'elective';
    case EXTRA = 'extra';
    
    public function label(): string
    {
        return match($this) {
            self::CORE => 'Core Subject',
            self::ELECTIVE => 'Elective Subject',
            self::EXTRA => 'Extra Curricular',
        };
    }
    
    public function isRequired(): bool
    {
        return match($this) {
            self::CORE => true,
            self::ELECTIVE => false,
            self::EXTRA => false,
        };
    }
    
    public function priority(): int
    {
        return match($this) {
            self::CORE => 1,
            self::ELECTIVE => 2,
            self::EXTRA => 3,
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