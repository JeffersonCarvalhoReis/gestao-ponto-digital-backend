<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        // Processa o login com o serviço
        $userResource = $this->authService->authenticate($request->validated(), $request);

        // Retorna resposta de sucesso com os dados do usuário
        return response()->json($userResource, 200);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();


        return response()->json(['message' => 'Logged out']);
    }

    public function user()
    {
        $user = auth()->user();

        return response()->json([
            'id' => $user->id,
            'user' => $user->user,
            'setor_id' => $user->unidade->localidade->setor_id,
            'funcao' => $user->roles[0]->name,
            'unidade' => $user->unidade ? [
                'id' => $user->unidade->id,
               'nome' => $user->unidade->nome,
            ] : null,
        ]);

    }
}
