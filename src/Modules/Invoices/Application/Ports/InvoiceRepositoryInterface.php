<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

interface InvoiceRepositoryInterface
{
    public function getById(InvoiceId $invoiceId): ?Invoice;

    public function save(Invoice $invoice): void;
}
