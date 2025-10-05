<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaywayTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'payment_id',
        'tran_id',
        'amount',
        'status',
        'qr_string',
        'qr_url',
        'deeplink',
        'apv',
        'pushback_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function pushback()
    {
        return $this->belongsTo(PaywayPushback::class);
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'processing';
    }

    public function markAsSuccess(string $apv, PaywayPushback $pushback)
    {
        $this->update([
            'status' => 'success',
            'apv' => $apv,
            'pushback_id' => $pushback->id,
        ]);
    }

    public function markAsFailed(PaywayPushback $pushback)
    {
        $this->update([
            'status' => 'failed',
            'pushback_id' => $pushback->id,
        ]);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->whereNotIn('status', ['success', 'failed']);
    }
}
