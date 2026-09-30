<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\UseCases\GetInvoice;

use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

final readonly class GetInvoiceHandler
{
    public function __construct(private InvoiceRepositoryInterface $invoices) {}

    public function handle(InvoiceId $id): InvoiceView
    {
        return InvoiceView::fromDomain($this->invoices->getById($id));
    }
}
