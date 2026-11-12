<?php

use App\Exceptions\TaskStoreException;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.token' => ApiTokenMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $e) =>
                $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (TaskStoreException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($e);

            return response()->json([
                'message' => 'Penyimpanan data tugas tidak dapat dibaca.',
            ], 500);
        });

        // API tidak pernah membocorkan trace, path, atau query meski APP_DEBUG=true.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof HttpResponseException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            if ($status >= 500) {
                report($e);

                return response()->json([
                    'message' => 'Terjadi kesalahan di server.',
                ], $status, $headers);
            }

            $message = $e->getMessage() !== ''
                ? $e->getMessage()
                : (SymfonyResponse::$statusTexts[$status] ?? 'Terjadi kesalahan.');

            return response()->json(['message' => $message], $status, $headers);
        });
    })
    ->create();
