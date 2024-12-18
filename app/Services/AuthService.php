<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AuthService
{
    public function authenticate(array $credentials, Request $request): UserResource
    {
        // Determina se o login é por email ou nome
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        // Modifica as credenciais para incluir o campo correto
        $authCredentials = [
            $field => $credentials['login'],
            'password' => $credentials['password'],
        ];


         $this->attemptLogin($authCredentials, $request);

         // Recupera o usuário autenticado
         $user = Auth::user();


        // Retorna o recurso do usuário
        return new UserResource($user);
    }

    protected function attemptLogin(array $credentials, Request $request): void
    {
        $this->ensureIsNotRateLimited($request);

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        // Limpa as tentativas falhas após sucesso
        RateLimiter::clear($this->throttleKey($request));
    }


    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
                'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('login')).'|'.$request->ip();
    }
}
