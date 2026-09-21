<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Exceptions;
use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use Modules\Notifications\Api\NotificationFacadeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceEndpointsTest extends TestCase
{
    #[Test]
    public function it_creates_a_draft_invoice_with_product_lines(): void
    {
        $response = $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme.test',
            'product_lines' => [
                ['name' => 'Desk', 'quantity' => 2, 'unit_price' => 15000],
                ['name' => 'Chair', 'quantity' => 4, 'unit_price' => 7500],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('customer_name', 'Acme Corp')
            ->assertJsonPath('product_lines.1.total_unit_price', 30000)
            ->assertJsonPath('total_price', 60000);

        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_product_lines', 2);
    }

    #[Test]
    public function it_creates_an_invoice_without_product_lines(): void
    {
        $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme.test',
        ])
            ->assertCreated()
            ->assertJsonPath('product_lines', [])
            ->assertJsonPath('total_price', 0);
    }

    #[Test]
    public function it_rejects_invalid_input(): void
    {
        $this->postJson('/api/invoices', [
            'customer_name' => '',
            'customer_email' => 'not-an-email',
            'product_lines' => [
                ['name' => 'Desk', 'quantity' => 'two', 'unit_price' => 15000],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_email',
                'product_lines.0.quantity',
            ]);
    }

    #[Test]
    public function it_accepts_the_largest_amounts_and_computes_their_total(): void
    {
        $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme.test',
            'product_lines' => [
                ['name' => 'Crane', 'quantity' => 1000000, 'unit_price' => 1000000000],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('total_price', 1000000000000000);
    }

    #[Test]
    #[DataProvider('amountsThatCouldOverflow')]
    public function it_rejects_amounts_whose_total_could_overflow(string $field, int $quantity, int $unitPrice): void
    {
        $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme.test',
            'product_lines' => [
                ['name' => 'Crane', 'quantity' => $quantity, 'unit_price' => $unitPrice],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertDatabaseCount('invoices', 0);
    }

    /** @return array<string, array{string, int, int}> */
    public static function amountsThatCouldOverflow(): array
    {
        return [
            'quantity too large' => ['product_lines.0.quantity', 1000001, 100],
            'quantity too small' => ['product_lines.0.quantity', -1000001, 100],
            'unit price too large' => ['product_lines.0.unit_price', 1, 1000000001],
            'unit price too small' => ['product_lines.0.unit_price', 1, -1000000001],
        ];
    }

    #[Test]
    public function it_rejects_more_than_a_hundred_product_lines(): void
    {
        $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme.test',
            'product_lines' => array_fill(0, 101, ['name' => 'Desk', 'quantity' => 1, 'unit_price' => 100]),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_lines']);
    }

    #[Test]
    public function it_rejects_an_email_the_domain_would_reject(): void
    {
        $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_email']);

        $this->assertDatabaseCount('invoices', 0);
    }

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
    public function it_does_not_report_a_missing_invoice_as_an_error(): void
    {
        Exceptions::fake();

        $this->getJson('/api/invoices/'.InvoiceId::generate()->value)
            ->assertNotFound();

        Exceptions::assertNotReported(InvoiceNotFound::class);
    }

    #[Test]
    public function it_sends_a_draft_invoice_to_the_customer(): void
    {
        $invoice = $this->storedDraft([new ProductLine('Desk', 2, 15000)]);

        $this->mock(NotificationFacadeInterface::class)
            ->shouldReceive('notify')
            ->once();

        $this->postJson('/api/invoices/'.$invoice->id->value.'/send')
            ->assertOk()
            ->assertJsonPath('status', 'sending');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id->value, 'status' => 'sending']);
    }

    #[Test]
    public function it_refuses_to_send_an_invoice_without_product_lines(): void
    {
        $invoice = $this->storedDraft([]);

        $this->postJson('/api/invoices/'.$invoice->id->value.'/send')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'An invoice without product lines cannot be sent.');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id->value, 'status' => 'draft']);
    }

    #[Test]
    public function it_refuses_to_send_an_invoice_twice(): void
    {
        $invoice = $this->storedDraft([new ProductLine('Desk', 2, 15000)]);

        $this->postJson('/api/invoices/'.$invoice->id->value.'/send')->assertOk();

        $this->postJson('/api/invoices/'.$invoice->id->value.'/send')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'An invoice cannot move from "sending" to "sending".');
    }

    #[Test]
    public function it_answers_404_when_sending_an_unknown_invoice(): void
    {
        $this->postJson('/api/invoices/'.InvoiceId::generate()->value.'/send')
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_404_for_an_id_that_is_not_a_uuid(): void
    {
        $this->getJson('/api/invoices/not-a-uuid')
            ->assertNotFound();
    }

    /** @param list<ProductLine> $productLines */
    private function storedDraft(array $productLines): Invoice
    {
        $invoice = Invoice::create('Acme Corp', CustomerEmail::fromString('billing@acme.test'), $productLines);
        $this->app->make(InvoiceRepository::class)->save($invoice);

        return $invoice;
    }
}
