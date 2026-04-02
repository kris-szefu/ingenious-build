<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Support;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class InMemoryInvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * @var array<string, Invoice>
     */
    private array $invoices = [];

    public function getById(InvoiceId $invoiceId): ?Invoice
    {
        return $this->invoices[$invoiceId->value()] ?? null;
    }

    public function save(Invoice $invoice): void
    {
        $this->invoices[$invoice->id()->value()] = $invoice;
    }
}
