<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Delivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Invoices\Infrastructure\Listeners\MarkInvoiceSentToClientListener;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;
use Modules\Notifications\Infrastructure\Drivers\DriverInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\Support\Notifications\FakeDriver;
use Tests\TestCase;

final class DeliveredWebhookTransitionsInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeDriver $fakeDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDriver = new FakeDriver;
        $this->app->instance(DriverInterface::class, $this->fakeDriver);
    }

    #[Test]
    public function delivered_hook_transitions_sending_invoice_to_sent_to_client(): void
    {
        $id = $this->createInvoice();
        $this->postJson("/api/invoices/{$id}/send")->assertStatus(202);

        // Sanity: the send endpoint parked the invoice in `sending`.
        $this->getJson("/api/invoices/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'sending');

        // Fire the external delivery webhook that the Notifications module exposes.
        $this->getJson("/api/notification/hook/delivered/{$id}")->assertNoContent();

        $this->getJson("/api/invoices/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'sent-to-client');

        $this->assertDatabaseHas('invoices', ['id' => $id, 'status' => 'sent-to-client']);
    }

    #[Test]
    public function delivered_hook_is_ignored_for_unknown_reference(): void
    {
        $unknownId = Uuid::uuid4()->toString();

        $this->getJson("/api/notification/hook/delivered/{$unknownId}")->assertNoContent();

        $this->assertDatabaseMissing('invoices', ['id' => $unknownId]);
    }

    #[Test]
    public function delivered_hook_is_ignored_when_invoice_is_still_draft(): void
    {
        $id = $this->createInvoice();

        $this->getJson("/api/notification/hook/delivered/{$id}")->assertNoContent();

        $this->getJson("/api/invoices/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'draft');
    }

    #[Test]
    public function listener_is_registered_for_the_webhook_delivered_event(): void
    {
        self::assertTrue(
            Event::hasListeners(WebhookDeliveredEvent::class),
            'Invoices module must listen for WebhookDeliveredEvent.',
        );

        Event::fake([WebhookDeliveredEvent::class]);

        Event::dispatch(new WebhookDeliveredEvent(Uuid::uuid4()));

        Event::assertListening(
            WebhookDeliveredEvent::class,
            [MarkInvoiceSentToClientListener::class, 'handle'],
        );
    }

    private function createInvoice(): string
    {
        return $this->postJson('/api/invoices', [
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
            'product_lines' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'unit_price' => 300],
            ],
        ])->assertStatus(201)->json('id');
    }
}




