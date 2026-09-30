<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\SendInvoice;

use Modules\Invoices\Application\Ports\CustomerNotifierInterface;
use Modules\Invoices\Application\Ports\DomainEventDispatcherInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;

final readonly class SendInvoiceHandler
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
        private CustomerNotifierInterface $notifier,
        private DomainEventDispatcherInterface $events,
    ) {}

    public function handle(SendInvoiceCommand $command): void
    {
        $recorded = [];

        // The whole flow runs inside `updateLocked` so that concurrent send
        // requests for the same invoice are serialised on a pessimistic row
        // lock. A losing request blocks until the winner commits, then sees
        // `status = sending` and its guard throws — no second customer
        // notification is dispatched.
        //
        // Guard order:
        //   1. Invoice::assertCanBeSent() — pure query, no mutation, no side
        //      effect. Fails fast so the notifier is never called for an
        //      invoice that cannot legally be sent.
        //   2. Notify the customer via the outbound port.
        //   3. Invoice::send() — re-checks guards internally and mutates.
        // If the notifier throws, `updateLocked` rolls the transaction back
        // and the invoice stays draft in storage. Recorded domain events
        // are dispatched after commit so subscribers never observe an
        // in-flight aggregate. See ADR 0003.
        $this->invoices->updateLocked($command->id, function (Invoice $invoice) use (&$recorded): void {
            $invoice->assertCanBeSent();

            $this->notifier->notifyInvoiceReady(
                $invoice->id,
                $invoice->customerName,
                $invoice->customerEmail,
            );

            $invoice->send();

            $recorded = $invoice->pullRecordedEvents();
        });

        $this->events->dispatchAll($recorded);
    }
}
