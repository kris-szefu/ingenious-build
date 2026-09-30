<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Invoices\Application\Ports\CustomerNotifierInterface;
use Modules\Invoices\Application\Ports\DomainEventDispatcherInterface;
use Modules\Invoices\Application\Ports\IdGeneratorInterface;
use Modules\Invoices\Application\Ports\InvoiceRepositoryInterface;
use Modules\Invoices\Infrastructure\Events\LaravelEventDispatcher;
use Modules\Invoices\Infrastructure\Ids\UuidGenerator;
use Modules\Invoices\Infrastructure\Listeners\MarkInvoiceSentToClientListener;
use Modules\Invoices\Infrastructure\Notifications\NotificationsCustomerNotifier;
use Modules\Invoices\Infrastructure\Repositories\EloquentInvoiceRepository;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final class InvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(IdGeneratorInterface::class, UuidGenerator::class);
        $this->app->bind(CustomerNotifierInterface::class, NotificationsCustomerNotifier::class);
        $this->app->bind(DomainEventDispatcherInterface::class, LaravelEventDispatcher::class);
    }

    public function boot(): void
    {
        Event::listen(WebhookDeliveredEvent::class, [MarkInvoiceSentToClientListener::class, 'handle']);
    }
}
