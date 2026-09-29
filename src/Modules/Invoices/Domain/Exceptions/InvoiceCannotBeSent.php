<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use DomainException;
use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvoiceCannotBeSent extends DomainException
{
    public static function notInDraft(StatusEnum $current): self
    {
        return new self("Invoice can only be sent from draft status, current: {$current->value}.");
    }

    public static function hasNoProductLines(): self
    {
        return new self('Invoice must contain at least one product line to be sent.');
    }

    public static function hasInvalidProductLine(): self
    {
        return new self('All product lines must have positive quantity and unit price.');
    }
}

