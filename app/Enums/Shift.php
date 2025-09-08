<?php

namespace App\Enums;

enum Shift: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';
    
    public function label(): string
    {
        return match($this) {
            self::MORNING => 'Morning (7:00 AM - 12:00 PM)',
            self::AFTERNOON => 'Afternoon (1:00 PM - 5:00 PM)',
            self::EVENING => 'Evening (5:00 PM - 8:00 PM)',
            self::NIGHT => 'Night (6:00 PM - 9:00 PM)',
            self::WEEKEND => 'Weekend (Saturday/Sunday)',
        };
    }
    
    public function timeRange(): array
    {
        return match($this) {
            self::MORNING => ['start' => '07:00', 'end' => '12:00'],
            self::AFTERNOON => ['start' => '13:00', 'end' => '17:00'],
            self::EVENING => ['start' => '17:00', 'end' => '20:00'],
            self::NIGHT => ['start' => '18:00', 'end' => '21:00'],
            self::WEEKEND => ['start' => '08:00', 'end' => '17:00'],
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