<?php

declare(strict_types=1);

namespace Modules\Invoices\Infrastructure\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Modules\Invoices\Application\EventHandlers\NotificationDeliveredHandler;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;

final class InvoiceEventServiceProvider extends EventServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        WebhookDeliveredEvent::class => [
            NotificationDeliveredHandler::class,
        ],
    ];
}
