<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Ports\InvoiceNotifier;
use Modules\Invoices\Domain\Repositories\InvoiceRepository;
use Modules\Invoices\Infrastructure\Notifications\NotificationFacadeInvoiceNotifier;
use Modules\Invoices\Infrastructure\Repositories\EloquentInvoiceRepository;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepository::class, EloquentInvoiceRepository::class);
        $this->app->bind(InvoiceNotifier::class, NotificationFacadeInvoiceNotifier::class);
    }
}
