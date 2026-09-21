<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Support;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class InMemoryInvoiceRepository implements InvoiceRepository
{
    /** @var array<string, Invoice> */
    private array $invoices = [];

    public function find(InvoiceId $id): ?Invoice
    {
        $invoice = $this->invoices[$id->value] ?? null;

        return $invoice === null ? null : clone $invoice;
    }

    public function save(Invoice $invoice): void
    {
        $this->invoices[$invoice->id->value] = clone $invoice;
    }

    public function isEmpty(): bool
    {
        return $this->invoices === [];
    }
}
