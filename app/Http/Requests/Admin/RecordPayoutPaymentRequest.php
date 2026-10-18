<?php

namespace App\Http\Requests\Admin;

use App\Models\OrderPayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPayoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_finance') ?? false;
    }

    public function rules(): array
    {
        return [
            'payee_type' => ['required', Rule::in(array_keys(OrderPayable::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
