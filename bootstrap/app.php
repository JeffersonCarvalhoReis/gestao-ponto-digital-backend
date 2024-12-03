<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e) {
            if($e->getPrevious() instanceof ModelNotFoundException){

                return response()->json([
                    'error' => 'O recurso solicitado não foi encontrado.'
                ], 404);
            }
        });
        // $exceptions->render(function (ValidationException $e) {
        //     return response()->json([
        //         'message' => 'Erro de validação.',
        //         'errors' => $e->errors(),
        //     ], 422);
        // });

        // $exceptions->render(function (Throwable $e) {
        //     \Log::error($e);
        //     return response()->json(['message' => 'Erro interno do servidor.'], 500);
        // });
    })->create();
