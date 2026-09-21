<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Domain;

use Modules\Invoices\Domain\Exceptions\InvalidCustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CustomerEmailTest extends TestCase
{
    #[Test]
    public function valid_address_is_accepted(): void
    {
        $value = 'test@example.com';
        $customerEmail = CustomerEmail::fromString($value)->value;
        $this->assertSame($value, $customerEmail);
    }

    #[Test]
    #[DataProvider('invalidInputs')]
    public function invalid_address_throws_error(string $email): void
    {
        $this->expectException(InvalidCustomerEmail::class);
        CustomerEmail::fromString($email);
    }

    /** @return array<string, array{string}> */
    public static function invalidInputs(): array
    {
        return [
            'empty' => [''],
            'no at sign' => ['nope'],
            'missing domain' => ['a@'],
            'domain without a dot' => ['a@b'],
        ];
    }
}
