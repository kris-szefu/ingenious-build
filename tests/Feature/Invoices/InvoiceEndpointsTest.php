<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceEndpointsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_an_invoice(): void
    {
        $invoice = Invoice::create(
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            [new ProductLine('Desk', 2, 15000)],
        );

        $this->app->make(InvoiceRepository::class)->save($invoice);

        $this->getJson('/api/invoices/'.$invoice->id->value)
            ->assertOk()
            ->assertJsonPath('id', $invoice->id->value)
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('customer_name', 'Acme Corp')
            ->assertJsonPath('customer_email', 'billing@acme.test')
            ->assertJsonPath('product_lines.0.unit_price', 15000)
            ->assertJsonPath('product_lines.0.total_unit_price', 30000)
            ->assertJsonPath('total_price', 30000);
    }

    #[Test]
    public function it_answers_404_for_an_unknown_invoice(): void
    {
        $this->getJson('/api/invoices/'.InvoiceId::generate()->value)
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_404_for_an_id_that_is_not_a_uuid(): void
    {
        $this->getJson('/api/invoices/not-a-uuid')
            ->assertNotFound();
    }
}
