<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain\ValueObjects;

use Modules\Invoices\Domain\Exceptions\InvalidCustomer;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CustomerEmailTest extends TestCase
{
    #[Test]
    public function it_accepts_and_trims_a_valid_email(): void
    {
        $email = new CustomerEmail('  ada@example.com  ');

        self::assertSame('ada@example.com', $email->value);
        self::assertSame('ada@example.com', (string) $email);
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith(['not-an-email'])]
    #[TestWith(['ada@'])]
    #[TestWith(['@example.com'])]
    public function it_rejects_invalid_emails(string $value): void
    {
        $this->expectException(InvalidCustomer::class);

        new CustomerEmail($value);
    }
}
