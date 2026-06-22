<?php

use App\Contracts\WalletRepositoryInterface;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OperationAlreadyReversedException;
use App\Exceptions\OperationNotReversibleException;
use App\Exceptions\UnauthorizedOperationException;
use App\Repositories\WalletRepository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (InsufficientBalanceException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'INSUFFICIENT_BALANCE',
                ], 422);
            }
        });

        $exceptions->render(function (OperationAlreadyReversedException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'OPERATION_ALREADY_REVERSED',
                ], 422);
            }
        });

        $exceptions->render(function (OperationNotReversibleException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'OPERATION_NOT_REVERSIBLE',
                ], 422);
            }
        });

        $exceptions->render(function (UnauthorizedOperationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'UNAUTHORIZED_OPERATION',
                ], 403);
            }
        });
    })->create();
