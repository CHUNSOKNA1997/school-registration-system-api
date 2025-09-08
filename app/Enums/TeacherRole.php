<?php

namespace App\Enums;

enum TeacherRole: string
{
    case PRIMARY = 'primary';
    case ASSISTANT = 'assistant';
    case SUBSTITUTE = 'substitute';
    
    public function label(): string
    {
        return match($this) {
            self::PRIMARY => 'Primary Teacher',
            self::ASSISTANT => 'Assistant Teacher',
            self::SUBSTITUTE => 'Substitute Teacher',
        };
    }
    
    public function canGrade(): bool
    {
        return match($this) {
            self::PRIMARY => true,
            self::ASSISTANT => false,
            self::SUBSTITUTE => true,
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