<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\ProductLine;

final class InvoiceResource extends JsonResource
{
    /** @var string|null */
    public static $wrap = null;

    public function __construct(Invoice $invoice)
    {
        parent::__construct($invoice);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->resource;

        return [
            'id' => $invoice->id->value,
            'status' => $invoice->status->value,
            'customer_name' => $invoice->customerName,
            'customer_email' => $invoice->customerEmail->value,
            'product_lines' => array_map(
                static fn (ProductLine $line): array => [
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unitPrice,
                    'total_unit_price' => $line->totalUnitPrice(),
                ],
                $invoice->productLines,
            ),
            'total_price' => $invoice->totalPrice(),
        ];
    }
}
