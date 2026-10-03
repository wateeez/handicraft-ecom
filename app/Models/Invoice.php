<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'generated_by',
        'status',
        'client_snapshot',
        'financial_snapshot',
        'issued_at',
        'voided_at',
        'voided_by',
        'void_reason',
        'pdf_path',
    ];

    protected $casts = [
        'client_snapshot' => 'array',
        'financial_snapshot' => 'array',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(OrderAuditLog::class);
    }

    public function financeInvoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FinanceInvoice::class);
    }

    /**
     * Generate next invoice number: INV-YYYY-NNNNN
     */
    public static function generateInvoiceNumber(): string
    {
        // lockForUpdate() serialises concurrent inserts under InnoDB (gap lock on last row).
        // Must be called within an open DB::transaction.
        $year = now()->year;
        $last = static::withTrashed()
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        $nextNum = $last ? ((int) substr($last->invoice_number, -5)) + 1 : 1;
        return "INV-{$year}-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if invoice is editable (only drafts are editable)
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if invoice can be voided
     */
    public function isVoidable(): bool
    {
        return in_array($this->status, ['draft', 'issued']);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'yellow',
            'issued' => 'green',
            'voided' => 'red',
            default => 'gray',
        };
    }
}
