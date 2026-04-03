<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Exception;

use Illuminate\Foundation\Configuration\Exceptions;
use Modules\Invoices\Application\Exceptions\InvoiceNotFoundException;
use Modules\Invoices\Application\Exceptions\InvoiceNotificationFailedException;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceIdException;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateException;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceStateTransitionException;
use Modules\Invoices\Domain\Exceptions\InvoiceCannotBeSentException;
use Symfony\Component\HttpFoundation\Response;

final class InvoicesApiExceptionMapper
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(static function (InvoiceNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(static function (InvalidInvoiceIdException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(static function (InvoiceCannotBeSentException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(static function (InvalidInvoiceStateTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        });

        $exceptions->render(static function (InvalidInvoiceStateException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        });

        $exceptions->render(static function (InvoiceNotificationFailedException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        });
    }
}
