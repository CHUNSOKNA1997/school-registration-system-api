<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BAKONG = 'bakong';
    
    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash',
            self::BAKONG => 'Bakong (KHQR)',
        };
    }
    
    public function requiresReference(): bool
    {
        return match($this) {
            self::CASH => false,
            self::BAKONG => true,
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::CASH => 'cash',
            self::BAKONG => 'qr-code',
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