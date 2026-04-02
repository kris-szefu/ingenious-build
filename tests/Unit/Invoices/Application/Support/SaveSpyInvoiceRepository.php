<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Support;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class SaveSpyInvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * @var array<string, Invoice>
     */
    private array $invoices = [];

    public int $saveCalls = 0;

    public ?string $lastSavedInvoiceId = null;

    public function __construct(Invoice ...$seedInvoices)
    {
        foreach ($seedInvoices as $invoice) {
            $this->invoices[$invoice->id()->value()] = $invoice;
        }
    }

    public function getById(InvoiceId $invoiceId): ?Invoice
    {
        return $this->invoices[$invoiceId->value()] ?? null;
    }

    public function save(Invoice $invoice): void
    {
        $this->saveCalls++;
        $this->lastSavedInvoiceId = $invoice->id()->value();
        $this->invoices[$invoice->id()->value()] = $invoice;
    }
}
