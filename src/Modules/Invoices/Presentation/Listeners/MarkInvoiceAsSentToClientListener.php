<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Listeners;

use Modules\Invoices\Application\Exceptions\InvoiceNotFound;
use Modules\Invoices\Application\UseCases\MarkInvoiceAsSentToClient;
use Modules\Invoices\Domain\Exceptions\InvalidStatusTransition;
use Modules\Notifications\Api\Events\WebhookDeliveredEvent;
use Psr\Log\LoggerInterface;

final readonly class MarkInvoiceAsSentToClientListener
{
    public function __construct(
        private MarkInvoiceAsSentToClient $markAsSentToClient,
        private LoggerInterface $logger,
    ) {}

    public function handle(WebhookDeliveredEvent $event): void
    {
        $resourceId = $event->resourceId->toString();

        try {
            $this->markAsSentToClient->handle($resourceId);
        } catch (InvoiceNotFound) {
            $this->logger->debug('Delivered resource is not an invoice.', ['resource_id' => $resourceId]);
        } catch (InvalidStatusTransition $e) {
            $this->logger->warning($e->getMessage(), ['invoice_id' => $resourceId]);
        }
    }
}
