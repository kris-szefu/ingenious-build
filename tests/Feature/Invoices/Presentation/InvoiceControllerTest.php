<?php

declare(strict_types=1);

namespace Tests\Feature\Invoices\Presentation;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class InvoiceControllerTest extends TestCase
{
    /**
     * @param  array{
     *     customerName: string,
     *     customerEmail: string,
     *     productLines: array<int, array{name: string, quantity: int, unitPrice: int}>
     * }  $payload
     * @param  array<int, array{
     *     productName: string,
     *     totalUnitPrice: int
     * }>  $expectedLineSummaries
     */
    #[DataProvider('createInvoiceProvider')]
    public function test_create_invoice_scenarios_return_expected_payload(
        array $payload,
        string $expectedStatus,
        int $expectedTotalPrice,
        array $expectedLineSummaries,
    ): void {
        $response = $this->postJson(route('invoices.create'), $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('customerName', $payload['customerName'])
            ->assertJsonPath('customerEmail', $payload['customerEmail'])
            ->assertJsonPath('status', $expectedStatus)
            ->assertJsonPath('totalPrice', $expectedTotalPrice);

        if ($expectedLineSummaries === []) {
            $response->assertJsonPath('productLines', []);

            return;
        }

        foreach ($expectedLineSummaries as $index => $expectedLineSummary) {
            $response->assertJsonPath("productLines.$index.productName", $expectedLineSummary['productName']);
            $response->assertJsonPath("productLines.$index.totalUnitPrice", $expectedLineSummary['totalUnitPrice']);
        }
    }

    public function test_view_invoice_returns_payload(): void
    {
        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'Alice',
            'customerEmail' => 'alice@example.com',
            'productLines' => [
                ['name' => 'Bag', 'quantity' => 1, 'unitPrice' => 300],
            ],
        ])->assertCreated();

        $invoiceId = (string) $created->json('invoiceId');

        $this->getJson(route('invoices.view', ['invoiceId' => $invoiceId]))
            ->assertOk()
            ->assertJsonPath('invoiceId', $invoiceId)
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('customerName', 'Alice')
            ->assertJsonPath('customerEmail', 'alice@example.com')
            ->assertJsonPath('productLines.0.productName', 'Bag')
            ->assertJsonPath('productLines.0.totalUnitPrice', 300)
            ->assertJsonPath('totalPrice', 300);
    }

    public function test_view_missing_invoice_returns_not_found(): void
    {
        $this->getJson(route('invoices.view', ['invoiceId' => 'invoice-missing']))
            ->assertNotFound();
    }

    public function test_send_invoice_with_valid_draft_transitions_to_sending(): void
    {
        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'productLines' => [
                ['name' => 'Hat', 'quantity' => 1, 'unitPrice' => 100],
            ],
        ])->assertCreated();

        $invoiceId = (string) $created->json('invoiceId');

        $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]))
            ->assertOk()
            ->assertJsonPath('invoiceId', $invoiceId)
            ->assertJsonPath('status', 'sending');
    }

    public function test_send_invoice_with_empty_lines_returns_unprocessable_entity(): void
    {
        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'productLines' => [],
        ])->assertCreated();

        $invoiceId = (string) $created->json('invoiceId');

        $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]))
            ->assertUnprocessable();
    }

    public function test_send_invoice_in_non_draft_state_returns_conflict(): void
    {
        $created = $this->postJson(route('invoices.create'), [
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'productLines' => [
                ['name' => 'Hat', 'quantity' => 1, 'unitPrice' => 100],
            ],
        ])->assertCreated();

        $invoiceId = (string) $created->json('invoiceId');

        $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]))
            ->assertOk()
            ->assertJsonPath('status', 'sending');

        $this->postJson(route('invoices.send', ['invoiceId' => $invoiceId]))
            ->assertConflict();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidCreateInvoicePayloadProvider')]
    public function test_create_with_invalid_payload_returns_validation_errors(array $payload): void
    {
        $this->postJson(route('invoices.create'), $payload)->assertUnprocessable();
    }

    public static function createInvoiceProvider(): array
    {
        return [
            'empty product lines' => [
                [
                    'customerName' => 'John Doe',
                    'customerEmail' => 'john@example.com',
                    'productLines' => [],
                ],
                'draft',
                0,
                [],
            ],
            'valid product lines' => [
                [
                    'customerName' => 'John Doe',
                    'customerEmail' => 'john@example.com',
                    'productLines' => [
                        ['name' => 'Hat', 'quantity' => 2, 'unitPrice' => 100],
                        ['name' => 'Shoes', 'quantity' => 1, 'unitPrice' => 250],
                    ],
                ],
                'draft',
                450,
                [
                    ['productName' => 'Hat', 'totalUnitPrice' => 200],
                    ['productName' => 'Shoes', 'totalUnitPrice' => 250],
                ],
            ],
        ];
    }

    public static function invalidCreateInvoicePayloadProvider(): array
    {
        return [
            'missing customerEmail' => [
                [
                    'customerName' => 'John Doe',
                    'productLines' => [
                        ['name' => 'Hat', 'quantity' => 1, 'unitPrice' => 100],
                    ],
                ],
            ],
            'non integer quantity' => [
                [
                    'customerName' => 'John Doe',
                    'customerEmail' => 'john@example.com',
                    'productLines' => [
                        ['name' => 'Hat', 'quantity' => 'two', 'unitPrice' => 100],
                    ],
                ],
            ],
            'missing line name' => [
                [
                    'customerName' => 'John Doe',
                    'customerEmail' => 'john@example.com',
                    'productLines' => [
                        ['quantity' => 1, 'unitPrice' => 100],
                    ],
                ],
            ],
        ];
    }
}
