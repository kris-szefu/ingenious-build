<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

interface InvoiceRepositoryInterface
{
    /**
     * @throws InvoiceNotFound when no invoice exists with the given id.
     */
    public function getById(InvoiceId $id): Invoice;

    public function save(Invoice $invoice): void;
}

