<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices;

use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductLine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoiceDeliveryWebhookTest extends TestCase
{
    #[Test]
    public function an_invoice_goes_from_draft_to_sent_to_client(): void
    {
        $invoiceId = $this->postJson('/api/invoices', [
            'customer_name' => 'Acme Corp',
            'customer_email' => 'billing@acme.test',
            'product_lines' => [
                ['name' => 'Desk', 'quantity' => 2, 'unit_price' => 15000],
            ],
        ])->assertCreated()->json('id');

        $this->postJson("/api/invoices/{$invoiceId}/send")
            ->assertOk()
            ->assertJsonPath('status', 'sending');

        $this->getJson("/api/notification/hook/delivered/{$invoiceId}")
            ->assertOk();

        $this->getJson("/api/invoices/{$invoiceId}")
            ->assertOk()
            ->assertJsonPath('status', 'sent-to-client');
    }

    #[Test]
    public function a_repeated_webhook_keeps_the_invoice_sent_to_client(): void
    {
        $invoice = $this->stored(StatusEnum::Sending);

        $this->getJson('/api/notification/hook/delivered/'.$invoice->id->value)->assertOk();
        $this->getJson('/api/notification/hook/delivered/'.$invoice->id->value)->assertOk();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id->value, 'status' => 'sent-to-client']);
    }

    #[Test]
    public function a_webhook_for_a_draft_invoice_leaves_it_a_draft(): void
    {
        $invoice = $this->stored(StatusEnum::Draft);

        $this->getJson('/api/notification/hook/delivered/'.$invoice->id->value)->assertOk();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id->value, 'status' => 'draft']);
    }

    #[Test]
    public function a_webhook_for_a_resource_that_is_not_an_invoice_is_ignored(): void
    {
        $this->getJson('/api/notification/hook/delivered/'.InvoiceId::generate()->value)->assertOk();

        $this->assertDatabaseCount('invoices', 0);
    }

    private function stored(StatusEnum $status): Invoice
    {
        $invoice = Invoice::reconstitute(
            InvoiceId::generate(),
            'Acme Corp',
            CustomerEmail::fromString('billing@acme.test'),
            $status,
            [new ProductLine('Desk', 2, 15000)],
        );
        $this->app->make(InvoiceRepository::class)->save($invoice);

        return $invoice;
    }
}
