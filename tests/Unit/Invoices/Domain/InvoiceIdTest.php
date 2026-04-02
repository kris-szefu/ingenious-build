<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceIdException;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\TestCase;

final class InvoiceIdTest extends TestCase
{
    public function test_rejects_empty_id_value(): void
    {
        $this->expectException(InvalidInvoiceIdException::class);

        new InvoiceId('');
    }

    public function test_accepts_non_empty_value(): void
    {
        $id = new InvoiceId('invoice-1');

        $this->assertSame('invoice-1', $id->value());
    }

    public function test_compares_invoice_ids(): void
    {
        $first = new InvoiceId('invoice-1');
        $same = new InvoiceId('invoice-1');
        $different = new InvoiceId('invoice-2');

        $this->assertTrue($first->equals($same));
        $this->assertFalse($first->equals($different));
    }
}
