<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Ids;

use Modules\Invoices\Application\Ports\IdGeneratorInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Ramsey\Uuid\Uuid;

final class UuidGenerator implements IdGeneratorInterface
{
    public function next(): InvoiceId
    {
        return InvoiceId::fromString(Uuid::uuid4()->toString());
    }
}
