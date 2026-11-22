<?php

namespace App\Http\Requests\Admin\Finance;

final class PaymentRules
{
    public static function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
