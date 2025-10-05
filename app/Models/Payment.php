<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Enums\PaymentType;
use App\Enums\PaymentPeriod;
use App\Enums\PaymentMethod;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'payment_code',
        'student_id',
        'subject_id',
        'payment_type',
        'payment_period',
        'amount',
        'discount',
        'total_amount',
        'payment_method',
        'payment_month',
        'payment_date',
        'due_date',
        'status',
        'khqr_reference',
        'bank_reference',
        'receipt_number',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_date' => 'date',
            'due_date' => 'date',
            'payment_type' => PaymentType::class,
            'payment_period' => PaymentPeriod::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }

            if (empty($payment->payment_code)) {
                $payment->payment_code = self::generatePaymentCode();
            }

            // Auto calculate total amount
            if (isset($payment->amount) && isset($payment->discount)) {
                $payment->total_amount = $payment->amount - $payment->discount;
            }
        });

        static::updating(function ($payment) {
            // Auto recalculate total amount if amount or discount changes
            if ($payment->isDirty(['amount', 'discount'])) {
                $payment->total_amount = $payment->amount - $payment->discount;
            }
        });
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('payment_month', $month);
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || ($this->status === 'pending' && $this->due_date < now());
    }

    public static function generatePaymentCode()
    {
        $year = date('Y');
        $month = date('m');
        $lastPayment = self::where('payment_code', 'like', "PAY{$year}{$month}-%")
            ->orderBy('payment_code', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PAY{$year}{$month}-{$newNumber}";
    }
}
