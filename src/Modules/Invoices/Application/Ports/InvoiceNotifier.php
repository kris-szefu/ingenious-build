<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Modules\Invoices\Domain\Entities\Invoice;

interface InvoiceNotifier
{
    public function notifyCustomer(Invoice $invoice): void;
}
