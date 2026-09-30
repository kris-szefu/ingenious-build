<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\ProductLine;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\CustomerEmail;
use Modules\Invoices\Domain\ValueObjects\CustomerName;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Domain\ValueObjects\ProductName;
use Modules\Invoices\Domain\ValueObjects\Quantity;
use Modules\Invoices\Domain\ValueObjects\UnitPrice;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceModel;
use Modules\Invoices\Infrastructure\Eloquent\InvoiceProductLineModel;
use Ramsey\Uuid\Uuid;

final class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function getById(InvoiceId $id): Invoice
    {
        $model = InvoiceModel::query()
            ->with('productLines')
            ->find($id->value);

        if ($model === null) {
            throw InvoiceNotFound::withId($id->value);
        }

        return $this->toDomain($model);
    }

    public function save(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            InvoiceModel::query()->updateOrCreate(
                ['id' => $invoice->id->value],
                [
                    'customer_name' => $invoice->customerName->value,
                    'customer_email' => $invoice->customerEmail->value,
                    'status' => $invoice->status(),
                ],
            );

            // Full-replace strategy for product lines: simple and safe for the
            // draft-only write path of the current use-cases. Revisit if we
            // ever mutate individual lines after send.
            InvoiceProductLineModel::query()
                ->where('invoice_id', $invoice->id->value)
                ->delete();

            foreach ($invoice->productLines() as $line) {
                InvoiceProductLineModel::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'invoice_id' => $invoice->id->value,
                    'name' => $line->productName->value,
                    'unit_price' => $line->unitPrice->value,
                    'quantity' => $line->quantity->value,
                ]);
            }
        });
    }

    private function toDomain(InvoiceModel $model): Invoice
    {
        $lines = $model->productLines
            ->map(static fn (InvoiceProductLineModel $line): ProductLine => new ProductLine(
                new ProductName($line->name),
                new Quantity($line->quantity),
                new UnitPrice($line->unit_price),
            ))
            ->values()
            ->all();

        return Invoice::reconstitute(
            InvoiceId::fromString($model->id),
            new CustomerName($model->customer_name),
            new CustomerEmail($model->customer_email),
            $model->status,
            $lines,
        );
    }
}
