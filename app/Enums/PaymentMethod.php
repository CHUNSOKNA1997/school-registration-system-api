<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case KHQR = 'khqr';
    case ABA = 'aba';
    case ACLEDA = 'acleda';
    case WING = 'wing';
    
    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::KHQR => 'KHQR (Bakong)',
            self::ABA => 'ABA Bank',
            self::ACLEDA => 'ACLEDA Bank',
            self::WING => 'Wing',
        };
    }
    
    public function requiresReference(): bool
    {
        return match($this) {
            self::CASH => false,
            self::BANK_TRANSFER => true,
            self::KHQR => true,
            self::ABA => true,
            self::ACLEDA => true,
            self::WING => true,
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::CASH => 'cash',
            self::BANK_TRANSFER => 'bank',
            self::KHQR => 'qr-code',
            self::ABA => 'credit-card',
            self::ACLEDA => 'credit-card',
            self::WING => 'mobile',
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