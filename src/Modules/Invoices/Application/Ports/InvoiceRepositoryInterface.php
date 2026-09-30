<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Ports;

use Closure;
use Modules\Invoices\Domain\Entities\Invoice;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Modules\Invoices\Domain\ValueObjects\InvoiceId;

interface InvoiceRepositoryInterface
{
    /**
     * @throws InvoiceNotFound when no invoice exists with the given id.
     */
    public function getById(InvoiceId $id): Invoice;

    public function save(Invoice $invoice): void;

    /**
     * Load the invoice under a pessimistic row lock and hand it to the mutator
     * inside a database transaction. When the mutator returns normally, any
     * changes it made are persisted (via {@see self::save()}) and the
     * transaction commits. If the mutator throws, the transaction rolls back
     * and no changes are persisted.
     *
     * This is the seam used by transitions that must serialise concurrent
     * writers (e.g. `draft → sending`) so that a losing request cannot trigger
     * a duplicate side effect (customer notification) before failing its own
     * domain guard.
     *
     * @param  Closure(Invoice): void  $mutator
     *
     * @throws InvoiceNotFound when no invoice exists with the given id.
     */
    public function updateLocked(InvoiceId $id, Closure $mutator): void;
}
