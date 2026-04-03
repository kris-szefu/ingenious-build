<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation;

use Illuminate\Testing\TestResponse;
use Modules\Invoices\Application\Ports\InvoiceNotifierInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class InvoicesApiExceptionMappingTest extends TestCase
{
    #[DataProvider('exceptionMappingProvider')]
    public function test_invoices_exception_mapping_returns_expected_status_and_message(
        string $scenario,
        int $expectedStatus,
    ): void {
        $response = $this->triggerScenario($scenario);

        $response->assertStatus($expectedStatus);
        $response->assertJsonStructure(['message']);
        $this->assertIsString($response->json('message'));
        $this->assertNotSame('', trim((string) $response->json('message')));
    }

    public static function exceptionMappingProvider(): array
    {
        return [
            'InvoiceNotFoundException' => ['invoice_not_found', 404],
            'InvalidInvoiceIdException' => ['invalid_invoice_id', 422],
            'InvoiceCannotBeSentException' => ['invoice_cannot_be_sent', 422],
            'InvalidInvoiceStateTransitionException' => ['invalid_state_transition', 409],
            'InvoiceNotificationFailedException' => ['notification_failed', 503],
        ];
    }

    private function triggerScenario(string $scenario): TestResponse
    {
        return match ($scenario) {
            'invoice_not_found' => $this->getJson(route('invoices.view', ['invoiceId' => 'missing-id'])),
            'invalid_invoice_id' => $this->getJson(route('invoices.view', ['invoiceId' => ' '])),
            'invoice_cannot_be_sent' => $this->triggerCannotBeSentScenario(),
            'invalid_state_transition' => $this->triggerInvalidTransitionScenario(),
            'notification_failed' => $this->triggerNotificationFailureScenario(),
        };
    }

    private function triggerCannotBeSentScenario(): TestResponse
    {
        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'productLines' => [],
        ])->assertCreated();

        return $this->postJson(route('invoices.send', [
            'invoiceId' => (string) $created->json('invoiceId'),
        ]));
    }

    private function triggerInvalidTransitionScenario(): TestResponse
    {
        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'productLines' => [
                ['name' => 'Hat', 'quantity' => 1, 'unitPrice' => 100],
            ],
        ])->assertCreated();

        $invoiceId = (string) $created->json('invoiceId');

        $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]))->assertOk();

        return $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]));
    }

    private function triggerNotificationFailureScenario(): TestResponse
    {
        $this->app->bind(InvoiceNotifierInterface::class, static fn () => new class implements InvoiceNotifierInterface
        {
            public function notify(
                string $invoiceId,
                string $toEmail,
                string $subject,
                string $message,
            ): void {
                throw new RuntimeException('Notification provider failure.');
            }
        });

        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'productLines' => [
                ['name' => 'Hat', 'quantity' => 1, 'unitPrice' => 100],
            ],
        ])->assertCreated();

        $invoiceId = (string) $created->json('invoiceId');

        return $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]));
    }
}
