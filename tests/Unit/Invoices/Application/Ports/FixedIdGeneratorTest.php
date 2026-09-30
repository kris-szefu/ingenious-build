<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Application\Ports;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Invoices\FixedIdGenerator;
use Tests\Support\Invoices\InvoiceIds;

final class FixedIdGeneratorTest extends TestCase
{
    #[Test]
    public function it_returns_the_configured_id(): void
    {
        $id = InvoiceIds::random();
        $generator = new FixedIdGenerator($id);

        self::assertSame($id, $generator->next());
    }

    #[Test]
    public function it_returns_the_same_id_on_repeated_calls(): void
    {
        $id = InvoiceIds::random();
        $generator = new FixedIdGenerator($id);

        self::assertSame($id, $generator->next());
        self::assertSame($id, $generator->next());
    }
}
