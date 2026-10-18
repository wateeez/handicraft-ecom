<?php

namespace App\Http\Requests\Admin;

use App\Models\OrderPayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordBulkPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_finance') ?? false;
    }

    public function rules(): array
    {
        return [
            'payee_type' => ['required', Rule::in(array_keys(OrderPayable::TYPES))],
            'payable_ids' => ['required', 'array', 'min:1', 'max:200'],
            'payable_ids.*' => ['integer', 'distinct'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['payable_ids.required' => 'Select at least one order to pay.'];
    }
}
