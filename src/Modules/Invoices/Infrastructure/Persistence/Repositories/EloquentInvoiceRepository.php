<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Entities\InvoiceProductLine;
use Modules\Invoices\Domain\Enums\StatusEnum;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceModel;
use Modules\Invoices\Infrastructure\Persistence\Models\InvoiceProductLineModel;
use Ramsey\Uuid\Uuid;

final class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function getById(InvoiceId $invoiceId): ?Invoice
    {
        $model = InvoiceModel::query()
            ->with('productLines')
            ->find($invoiceId->value());

        if ($model === null) {
            return null;
        }

        return $this->mapModelToDomain($model);
    }

    public function save(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $this->upsertInvoice($invoice);
            $this->replaceProductLines($invoice);
        });
    }

    private function mapModelToDomain(InvoiceModel $model): Invoice
    {
        $status = StatusEnum::from($model->status);
        $productLines = $model->productLines
            ->map(static fn (InvoiceProductLineModel $line): InvoiceProductLine => new InvoiceProductLine(
                name: $line->name,
                quantity: $line->quantity,
                unitPrice: $line->price,
            ))
            ->all();

        return Invoice::rehydrate(
            id: new InvoiceId($model->id),
            customerName: $model->customer_name,
            customerEmail: $model->customer_email,
            productLines: $productLines,
            status: $status,
        );
    }

    private function upsertInvoice(Invoice $invoice): void
    {
        InvoiceModel::query()->updateOrCreate(
            ['id' => $invoice->id()->value()],
            [
                'customer_name' => $invoice->customerName(),
                'customer_email' => $invoice->customerEmail(),
                'status' => $invoice->status()->value,
            ],
        );
    }

    private function replaceProductLines(Invoice $invoice): void
    {
        InvoiceProductLineModel::query()
            ->where('invoice_id', $invoice->id()->value())
            ->delete();

        foreach ($invoice->productLines() as $line) {
            InvoiceProductLineModel::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'invoice_id' => $invoice->id()->value(),
                'name' => $line->name(),
                'price' => $line->unitPrice(),
                'quantity' => $line->quantity(),
            ]);
        }
    }
}
