<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware
            ->alias([
                // Spatie Permission middlewares
                'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
                'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
                'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            ])
            ->redirectGuestsTo(fn () => route('home'))
            ->redirectUsersTo(fn () => route('home'));

        $environment = env('APP_ENV', 'production');

        match ($environment) {
            'production' => $middleware->trustProxies(
                at: '*',
                headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
            ),
            default => $middleware->trustProxies(
                at: ['127.0.0.1', '::1'],
                headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_PROTO
            )
        };
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 404 Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return response()->view('errors.404', [
                    'message' => $e->getMessage() ?: 'Page not found.',
                ], 404);
            }
        });

        // 403 Forbidden
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return response()->view('errors.403', [
                    'message' => $e->getMessage() ?: 'Unauthorized access.',
                ], 403);
            }
        });

        // 503 Service Unavailable (Maintenance Mode)
        $exceptions->render(function (ServiceUnavailableHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return response()->view('errors.503', [
                    'message' => $e->getMessage() ?: 'Service currently unavailable.',
                ], 503);
            }
        });

        // 500 Internal Server Error (Catch-all for fatal crashes)
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Safety check: Only override if it's a web request, NOT in debug mode,
            // and NOT a standard HTTP exception (like a 404 or 403 we already caught).
            if (! $request->expectsJson() && ! config('app.debug') && ! $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return response()->view('errors.500', [
                    'message' => 'Whoops, something went wrong on our servers.',
                ], 500);
            }
        });
    })->create();
