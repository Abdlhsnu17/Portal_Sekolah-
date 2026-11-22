<?php

namespace App\Http\Requests\Admin\Finance;

use App\Models\StudentInvoice;
use Illuminate\Validation\Rule;

final class InvoiceRules
{
    public static function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(StudentInvoice::CATEGORIES))],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
