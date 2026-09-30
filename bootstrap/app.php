<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Invoices\Domain\Exceptions\InvalidInvoiceId;
use Modules\Invoices\Domain\Exceptions\InvoiceNotFound;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $renderNotFound = static fn (string $message, Request $request): ?JsonResponse => ($request->expectsJson() || $request->is('api/*'))
            ? new JsonResponse(['message' => $message], Response::HTTP_NOT_FOUND)
            : null;

        $exceptions->render(fn (InvoiceNotFound $e, Request $request) => $renderNotFound($e->getMessage(), $request));
        $exceptions->render(fn (InvalidInvoiceId $e, Request $request) => $renderNotFound($e->getMessage(), $request));
    })->create();
