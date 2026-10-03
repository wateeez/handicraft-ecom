<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInvoice extends Model
{
    protected $fillable = ['invoice_id', 'order_id', 'client_id', 'payment_status', 'amount_paid', 'balance_due', 'currency'];
    protected $casts = ['amount_paid' => 'decimal:2', 'balance_due' => 'decimal:2'];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
