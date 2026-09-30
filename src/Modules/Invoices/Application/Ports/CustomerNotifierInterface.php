<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

/**
 * Outbound port owned by the Invoices module for notifying a customer that
 * an invoice is ready. Application code depends on this port only — the
 * Infrastructure adapter maps to the Notifications module's public API
 * (`Modules\Notifications\Api\*`).
 *
 * Kept intentionally narrow: three invoice-shaped value objects, no
 * cross-module DTOs, no message body concerns. Wire-format concerns
 * (subject, body, correlation id shape) belong to the Infrastructure
 * adapter.
 */
interface CustomerNotifierInterface
{
    public function notifyInvoiceReady(
        InvoiceId $invoiceId,
        CustomerName $customerName,
        CustomerEmail $customerEmail,
    ): void;
}
