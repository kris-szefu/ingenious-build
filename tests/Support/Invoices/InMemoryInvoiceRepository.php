<?php

declare(strict_types=1);

namespace Tests\Support\Invoices;

use Closure;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class InMemoryInvoiceRepository implements InvoiceRepositoryInterface
{
    /** @var array<string, Invoice> */
    private array $invoices = [];

    public function getById(InvoiceId $id): Invoice
    {
        return $this->invoices[$id->value]
            ?? throw InvoiceNotFound::withId($id->value);
    }

    public function save(Invoice $invoice): void
    {
        $this->invoices[$invoice->id->value] = $invoice;
    }

    /**
     * Single-threaded stand-in for the pessimistic-locked update path. No real
     * concurrency, but preserves the "mutator throws → nothing persists"
     * contract that the production adapter relies on.
     */
    public function updateLocked(InvoiceId $id, Closure $mutator): void
    {
        $invoice = $this->getById($id);

        $mutator($invoice);

        $this->save($invoice);
    }
}
