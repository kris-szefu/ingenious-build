<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Exceptions;

use RuntimeException;
use Throwable;

final class InvoiceNotificationFailedException extends RuntimeException
{
    public static function forInvoice(string $invoiceId, Throwable $previous): self
    {
        return new self(
            message: sprintf('Notification sending failed for invoice "%s".', $invoiceId),
            previous: $previous,
        );
    }
}
