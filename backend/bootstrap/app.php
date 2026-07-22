<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // This is a pure JSON API with no web login page, so guests should
        // never be redirected — always fall back to a plain 401 instead.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Registration order matters: renderViaCallbacks() uses the first
        // matching handler, so specific exception types must come before
        // the generic HttpExceptionInterface/Throwable catch-alls below.

        $exceptions->render(function (ValidationException $e, Request $request) {
            return ApiResponse::error('validation_error', $e->getMessage(), $e->status, $e->errors());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ApiResponse::error('unauthenticated', $e->getMessage(), 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            // Laravel normalizes AuthorizationException (and Sanctum's
            // MissingAbilityException, which extends it) into this class
            // before render() callbacks run, but the original is kept as
            // the "previous" exception — use it to tell "wrong role" apart
            // from "wrong token scope" without matching on message text.
            $code = $e->getPrevious() instanceof MissingAbilityException ? 'invalid_scope' : 'forbidden';

            return ApiResponse::error($code, $e->getMessage(), $e->getStatusCode());
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();
            $customCodes = [419 => 'csrf_token_mismatch'];
            $statusText = Response::$statusTexts[$status] ?? 'Error';
            $code = $customCodes[$status] ?? Str::snake($statusText);

            return ApiResponse::error($code, $e->getMessage() ?: $statusText, $status);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = app()->hasDebugModeEnabled()
                ? ($e->getMessage() ?: $e::class)
                : 'An unexpected error occurred.';

            return ApiResponse::error('server_error', $message, 500);
        });
    })->create();
