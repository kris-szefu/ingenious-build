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
            'customer_name' => ['required', 'string', 'min:1'],
            'customer_email' => ['required', 'email'],
            'product_lines' => ['array'],
            'product_lines.*.product_name' => ['required', 'string', 'min:1'],
            'product_lines.*.quantity' => ['required', 'integer', 'gt:0'],
            'product_lines.*.unit_price' => ['required', 'integer', 'gt:0'],
        ];
    }
}

