<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvoiceCannotBeMarkedSent extends DomainException
{
    public static function notInSending(StatusEnum $current): self
    {
        return new self("Invoice can only be marked sent-to-client from sending status, current: {$current->value}.");
    }
}

