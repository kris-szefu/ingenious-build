<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Ports\InvoiceIdGeneratorInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Infrastructure\Persistence\IdGenerators\UuidInvoiceIdGenerator;
use Modules\Invoices\Infrastructure\Persistence\Repositories\EloquentInvoiceRepository;

final class InvoiceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->scoped(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->scoped(InvoiceIdGeneratorInterface::class, UuidInvoiceIdGenerator::class);
    }

    /** @return array<class-string> */
    public function provides(): array
    {
        return [
            InvoiceRepositoryInterface::class,
            InvoiceIdGeneratorInterface::class,
        ];
    }
}
