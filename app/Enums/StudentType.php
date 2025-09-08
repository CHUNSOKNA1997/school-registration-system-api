<?php

namespace App\Enums;

enum StudentType: string
{
    case REGULAR = 'regular';
    case MONK = 'monk';
    
    public function label(): string
    {
        return match($this) {
            self::REGULAR => 'Regular Student',
            self::MONK => 'Monk Student',
        };
    }
    
    public function discount(): float
    {
        return match($this) {
            self::REGULAR => 0,
            self::MONK => 100, // 100% discount for monks
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