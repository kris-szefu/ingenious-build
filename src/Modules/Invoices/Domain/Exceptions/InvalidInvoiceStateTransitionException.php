<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvalidInvoiceStateTransitionException extends DomainException
{
    public static function from(StatusEnum $from, StatusEnum $to): self
    {
        return new self(sprintf(
            'Invalid invoice status transition from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
