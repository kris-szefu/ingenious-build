<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\ProductLine;

final readonly class CreateInvoice
{
    public function __construct(
        private InvoiceRepository $invoices,
    ) {}

    /** @param list<array{name: string, quantity: int, unitPrice: int}> $productLines */
    public function handle(string $customerName, string $customerEmail, array $productLines = []): Invoice
    {
        $invoice = Invoice::create(
            $customerName,
            CustomerEmail::fromString($customerEmail),
            array_map(
                static fn (array $line): ProductLine => new ProductLine(
                    name: $line['name'],
                    quantity: $line['quantity'],
                    unitPrice: $line['unitPrice'],
                ),
                $productLines,
            ),
        );

        $this->invoices->save($invoice);

        return $invoice;
    }
}
