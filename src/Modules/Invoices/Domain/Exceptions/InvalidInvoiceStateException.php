<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvalidInvoiceStateException extends DomainException
{
    public static function missingProductLinesForStatus(StatusEnum $status): self
    {
        return new self(sprintf(
            'Invalid persisted invoice state: status "%s" requires at least one product line.',
            $status->value,
        ));
    }
}
