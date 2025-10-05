<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class PaywayPushback extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tran_id',
        'apv',
        'status',
        'status_message',
        'return_params',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pushback) {
            if (empty($pushback->uuid)) {
                $pushback->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function transaction()
    {
        return $this->hasOne(PaywayTransaction::class, 'pushback_id');
    }

    // Helper methods
    public function isSuccessful(): bool
    {
        return (int)$this->status === 0;
    }

    public function getReturnParameters(): ?array
    {
        if (!$this->return_params) {
            return null;
        }

        $decoded = base64_decode($this->return_params);
        return json_decode($decoded, true);
    }
}
