<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customerName' => ['required', 'string'],
            'customerEmail' => ['required', 'string', 'email'],
            'productLines' => ['sometimes', 'array'],
            'productLines.*.name' => ['required', 'string'],
            'productLines.*.quantity' => ['required', 'integer'],
            'productLines.*.unitPrice' => ['required', 'integer'],
        ];
    }
}
