<?php

namespace App\Enums;

enum PaymentType: string
{
    case REGISTRATION = 'registration';
    case TUITION = 'tuition';
    case EXAM = 'exam';
    case CERTIFICATE = 'certificate';
    case OTHER = 'other';
    
    public function label(): string
    {
        return match($this) {
            self::REGISTRATION => 'Registration Fee',
            self::TUITION => 'Tuition Fee',
            self::EXAM => 'Exam Fee',
            self::CERTIFICATE => 'Certificate Fee',
            self::OTHER => 'Other Fee',
        };
    }
    
    public function isRecurring(): bool
    {
        return match($this) {
            self::REGISTRATION => false,
            self::TUITION => true,
            self::EXAM => false,
            self::CERTIFICATE => false,
            self::OTHER => false,
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