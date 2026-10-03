<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    protected $fillable = ['reporting_currency', 'default_commission_type', 'default_commission_value', 'commission_base', 'commission_earned_trigger', 'loyalty_basis', 'payment_reminders_enabled'];
    protected $casts = ['default_commission_value' => 'decimal:2', 'payment_reminders_enabled' => 'boolean'];
}
