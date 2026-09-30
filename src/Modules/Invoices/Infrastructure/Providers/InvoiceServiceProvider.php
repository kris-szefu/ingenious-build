<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Ports\IdGeneratorInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Infrastructure\Ids\UuidGenerator;
use Modules\Invoices\Infrastructure\Listeners\MarkInvoiceSentToClientListener;
use Modules\Invoices\Infrastructure\Repositories\EloquentInvoiceRepository;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(IdGeneratorInterface::class, UuidGenerator::class);
    }

    public function boot(): void
    {
        Event::listen(WebhookDeliveredEvent::class, [MarkInvoiceSentToClientListener::class, 'handle']);
    }
}
