<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\MarkInvoiceDelivered;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeMarkedSent;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Psr\Log\LoggerInterface;

/**
 * Reacts to a delivery signal by transitioning the referenced invoice from
 * `sending` to `sent-to-client`.
 *
 * The delivery signal is a cross-module event whose source (webhook) is
 * external and inherently at-least-once / racy. Two conditions are therefore
 * treated as non-errors and silently logged instead of thrown:
 *
 *  - The referenced invoice does not exist (stale event, wrong reference).
 *  - The invoice is not in `sending` state (duplicate delivery, or the
 *    invoice never reached `sending`).
 *
 * Every other exception bubbles.
 */
final readonly class MarkInvoiceDeliveredHandler
{
    public function __construct(
        private InvoiceRepositoryInterface $invoices,
        private LoggerInterface $logger,
    ) {}

    public function handle(MarkInvoiceDeliveredCommand $command): void
    {
        try {
            $invoice = $this->invoices->getById($command->id);
        } catch (InvoiceNotFound $e) {
            $this->logger->warning('Delivery event ignored: invoice not found.', [
                'invoice_id' => $command->id->value,
                'reason' => $e->getMessage(),
            ]);

            return;
        }

        try {
            $invoice->markSentToClient();
        } catch (InvoiceCannotBeMarkedSent $e) {
            $this->logger->warning('Delivery event ignored: invoice not in sending state.', [
                'invoice_id' => $command->id->value,
                'reason' => $e->getMessage(),
            ]);

            return;
        }

        $this->invoices->save($invoice);
    }
}

