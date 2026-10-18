<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalculateOrderFinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_finance') ?? false;
    }

    public function rules(): array
    {
        return [
            // Must exist and be active (soft-deleted vendors are excluded)
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'product_price_usd' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'shipping_usd' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'forex_rate' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'actual_shipping_cost_npr' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return ['vendor_id.exists' => 'Choose an active vendor.'];
    }
}
