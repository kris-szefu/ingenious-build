<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInvoiceRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'string', 'email:filter', 'max:255'],
            'product_lines' => ['sometimes', 'array'],
            'product_lines.*.name' => ['required', 'string', 'max:255'],
            'product_lines.*.quantity' => ['required', 'integer'],
            'product_lines.*.unit_price' => ['required', 'integer'],
        ];
    }

    /** @return list<array{name: string, quantity: int, unitPrice: int}> */
    public function productLines(): array
    {
        /** @var list<array{name: string, quantity: int, unit_price: int}> $lines */
        $lines = $this->validated('product_lines', []);

        return array_map(
            static fn (array $line): array => [
                'name' => $line['name'],
                'quantity' => $line['quantity'],
                'unitPrice' => $line['unit_price'],
            ],
            $lines,
        );
    }
}
