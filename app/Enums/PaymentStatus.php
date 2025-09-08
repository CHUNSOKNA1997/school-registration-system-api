<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PARTIAL = 'partial';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::PARTIAL => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'green',
            self::PARTIAL => 'blue',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }
    
    public function canEdit(): bool
    {
        return match($this) {
            self::PENDING => true,
            self::PAID => false,
            self::PARTIAL => true,
            self::OVERDUE => true,
            self::CANCELLED => false,
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