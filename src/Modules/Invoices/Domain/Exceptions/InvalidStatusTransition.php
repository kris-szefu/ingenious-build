<?php

declare(strict_types=1);

namespace Modules\Invoices\Domain\Exceptions;

use Modules\Invoices\Domain\Enums\StatusEnum;

final class InvalidStatusTransition extends \DomainException
{
    public static function from(StatusEnum $from, StatusEnum $to): self
    {
        return new self(sprintf('An invoice cannot move from "%s" to "%s".', $from->value, $to->value));
    }
}
