<?php

declare(strict_types=1);

namespace Tests\Support\Invoices;

use Modules\Invoices\Application\Ports\IdGeneratorInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final class FixedIdGenerator implements IdGeneratorInterface
{
    public function __construct(private readonly InvoiceId $id) {}

    public function next(): InvoiceId
    {
        return $this->id;
    }
}
