<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FinanceRateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_finance') ?? false;
    }

    public function rules(): array
    {
        return [
            'gateway_fee_pct' => ['required', 'numeric', 'between:0,100'],
            'csr_pct' => ['required', 'numeric', 'between:0,100'],
            'data_entry_pct' => ['required', 'numeric', 'between:0,100'],
            'de_fixed_rate' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'de_threshold_rate' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'include_shipping_in_commission' => ['sometimes', 'boolean'],
        ];
    }
}
