<?php

use Modules\Invoices\Infrastructure\Providers\InvoiceEventServiceProvider;
use Modules\Invoices\Infrastructure\Providers\InvoiceServiceProvider;
use Modules\Notifications\Infrastructure\Providers\NotificationServiceProvider;

return [
    InvoiceServiceProvider::class,
    InvoiceEventServiceProvider::class,
    NotificationServiceProvider::class,
];
