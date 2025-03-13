<?php

use App\Exceptions\BiometricException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
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
                    'message' => 'O recurso solicitado não foi encontrado.'
                ], 404);
            }
        });
        $exceptions->render(function (Throwable $e) {

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Sua sessão expirou. Por favor, faça login novamente para continuar'
            ], 401);
            }

        });

        $exceptions->render(function (Throwable $e) {

            if ($e instanceof TokenMismatchException) {
                return response()->json([
                    'message' => 'Sua sessão expirou. Atualize a página e tente novamente.'
                ], 419);
            }

        });

        $exceptions->render(function (Throwable $e) {

            // if ($e instanceof BiometricException) {
            //     return response()->json([
            //         'message' => 'Falha ao se contectar com o aparelho biometrico.'
            //     ], 500);
            // }

        });
        $exceptions->render(function (Throwable $e) {
            if ($e instanceof QueryException) {
                // Verifica se o erro é de restrição de chave estrangeira
                if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                    return response()->json([
                        'message' => 'Este registro não pode ser excluído porque está relacionado a outros dados.'
                    ], 400);
                }
            }

        });
    })->create();
