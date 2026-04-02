<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Ports\InvoiceIdGeneratorInterface;
use Modules\Invoices\Application\Ports\InvoiceNotifierInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Infrastructure\Notifications\NotificationInvoiceNotifier;
use Modules\Invoices\Infrastructure\Persistence\IdGenerators\UuidInvoiceIdGenerator;
use Modules\Invoices\Infrastructure\Persistence\Repositories\EloquentInvoiceRepository;

final class InvoiceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->scoped(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->scoped(InvoiceIdGeneratorInterface::class, UuidInvoiceIdGenerator::class);
        $this->app->scoped(InvoiceNotifierInterface::class, NotificationInvoiceNotifier::class);
    }

    /** @return array<class-string> */
    public function provides(): array
    {
        return [
            InvoiceRepositoryInterface::class,
            InvoiceIdGeneratorInterface::class,
            InvoiceNotifierInterface::class,
        ];
    }
}
