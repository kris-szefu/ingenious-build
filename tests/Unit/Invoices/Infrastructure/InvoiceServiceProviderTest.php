<?php

declare(strict_types=1);

namespace Tests\Unit\Invoices\Infrastructure;

use Illuminate\Contracts\Foundation\Application;
use Modules\Invoices\Infrastructure\Providers\InvoiceServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceServiceProviderTest extends TestCase
{
    #[Test]
    public function it_is_not_deferred_so_the_webhook_listener_is_registered_on_every_request(): void
    {
        $provider = new InvoiceServiceProvider($this->createStub(Application::class));

        $this->assertFalse($provider->isDeferred());
    }
}
