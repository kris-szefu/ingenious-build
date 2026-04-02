<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

interface InvoiceNotifierInterface
{
    public function notify(
        string $invoiceId,
        string $toEmail,
        string $subject,
        string $message,
    ): void;
}
