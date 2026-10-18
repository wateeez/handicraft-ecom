<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Services\Finance\FinanceRateRepository;
use Illuminate\Database\Seeder;

class ProfitSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Only fill missing keys so re-seeding never overwrites admin edits
        $rates = app(FinanceRateRepository::class);
        foreach (FinanceRateRepository::DEFAULTS as $key => $value) {
            if (!\App\Models\FinanceRateSetting::where('key', $key)->exists()) {
                $rates->set($key, $value);
            }
        }

        Vendor::withTrashed()->firstOrCreate(
            ['name' => 'HME'],
            ['cost_price_pct' => 50, 'commission_pct' => 20, 'notes' => 'Default vendor', 'is_active' => true]
        );
    }
}
