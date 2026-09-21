<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class GetInvoice
{
    public function __construct(
        private InvoiceRepository $invoices,
    ) {}

    public function handle(string $invoiceId): Invoice
    {
        $invoice = $this->invoices->find(InvoiceId::fromString($invoiceId));

        if (! $invoice instanceof Invoice) {
            throw InvoiceNotFound::withId($invoiceId);
        }

        return $invoice;
    }
}
