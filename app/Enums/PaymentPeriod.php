<?php

namespace App\Enums;

enum PaymentPeriod: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    
    public function label(): string
    {
        return match($this) {
            self::MONTHLY => 'Monthly Payment',
            self::YEARLY => 'Yearly Payment',
        };
    }
    
    public function months(): int
    {
        return match($this) {
            self::MONTHLY => 1,
            self::YEARLY => 12,
        };
    }
    
    public function installments(): int
    {
        return match($this) {
            self::MONTHLY => 12,
            self::YEARLY => 1,
        };
    }
    
    public function discount(): float
    {
        return match($this) {
            self::MONTHLY => 0,
            self::YEARLY => 10, // 10% discount for yearly payment
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