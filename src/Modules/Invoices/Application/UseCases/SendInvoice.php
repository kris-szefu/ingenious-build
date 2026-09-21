<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases;

use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Application\Ports\InvoiceNotifier;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class SendInvoice
{
    public function __construct(
        private InvoiceRepository $invoices,
        private InvoiceNotifier $notifier,
    ) {}

    public function handle(string $invoiceId): Invoice
    {
        $invoice = $this->invoices->find(InvoiceId::fromString($invoiceId));

        if (! $invoice instanceof Invoice) {
            throw InvoiceNotFound::withId($invoiceId);
        }

        $invoice->send();
        $this->notifier->notifyCustomer($invoice);
        $this->invoices->save($invoice);

        return $invoice;
    }
}
