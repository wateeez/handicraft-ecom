<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distributor extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'address', 'payout_details', 'is_active'];

    protected $casts = ['payout_details' => 'array', 'is_active' => 'boolean'];

    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function financeSnapshots(): HasMany { return $this->hasMany(FinanceOrderSnapshot::class); }
    public function payables(): HasMany { return $this->hasMany(DistributorPayable::class); }
    public function payoutBatches(): HasMany { return $this->hasMany(DistributorPayoutBatch::class); }
}
