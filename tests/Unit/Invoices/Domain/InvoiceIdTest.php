<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Exceptions\InvalidInvoiceId;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceIdTest extends TestCase
{
    #[Test]
    public function generated_id_can_be_parsed_back(): void
    {
        $invoiceId = InvoiceId::generate()->value;
        $this->assertSame($invoiceId, InvoiceId::fromString($invoiceId)->value);
    }

    #[Test]
    public function two_generate_calls_produce_different_values(): void
    {
        $this->assertNotSame(InvoiceId::generate()->value, InvoiceId::generate()->value);
    }

    #[Test]
    public function from_string_keeps_given_value(): void
    {
        $string = '123e4567-e89b-12d3-a456-426614174000';
        $this->assertSame($string, InvoiceId::fromString($string)->value);
    }

    #[Test]
    public function invalid_uuid_throws_error(): void
    {
        $this->expectException(InvalidInvoiceId::class);
        InvoiceId::fromString('test');
    }
}
