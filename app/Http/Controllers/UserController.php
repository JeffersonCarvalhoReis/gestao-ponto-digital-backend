<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_usuarios')->only('index');
        $this->middleware('permission:registrar_usuarios')->only('store');
        $this->middleware('permission:visualizar_usuarios')->only('show');
        $this->middleware('permission:editar_usuarios')->only('update');
        $this->middleware('permission:excluir_usuarios')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('roles')->get();
        $users = $users->filter( function($user){
            return !$user->roles->contains('name', 'super admin');
        });
        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'senha' => 'required|string|min:8',
            'funcao' => 'required|exists:roles,name',
            'unidade' => 'required|numeric|exists:unidades,id'

        ]);

        $user = User::create([
            'name' => $request->nome,
            'email' => $request->email,
            'password' => Hash::make($request->input('senha')),
            'unidade_id' => (int)$request->input('unidade'),

        ]);
        $user->assignRole($request->funcao);

        return response()->json(['message' => 'Usuário criado com sucesso.', 'user' => $user], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json($user, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        $userToUpdate = User::with('roles')->findOrFail($id);

        if ($user->id !== $userToUpdate->id && $user->roles->contains('name', 'super admin')) {
            return response()->json(['error' => 'Super administradores não podem editar outros super administradores.'], 403);
        }

        // Impede que administradores editem outros administradores ou super administradores
        if ($userToUpdate->hasRole(['admin', 'super admin']) && $user->id !== $userToUpdate->id) {
            return response()->json(['error' => 'Não é permitido editar usuários administradores ou super administradores.'], 403);
        }



        $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userToUpdate->id,
            'senha' => 'sometimes|string|min:8',
            'funcao' => 'sometimes|exists:roles,name',
            'unidade' => 'sometimes|numeric|exists:unidades,id',
        ]);

        $userToUpdate->update(array_filter([
            'name' => $request->nome ?? null,
            'email' => $request->email ?? null,
            'password' => isset($request->senha) ? Hash::make($request->senha) : null,
            'unidade_id' => $request->unidade ?? null,
        ]));

        if(isset($request->funcao)){
            $userToUpdate->syncRoles($request->funcao);
        }

        return response()->json(['message' => 'Usuário atualizado com sucesso', 'user' => $userToUpdate, 'role' => $userToUpdate->roles->pluck('name')], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $userToDelete = User::with('roles')->findOrFail($id);


        if ($user->roles->contains('name', 'super admin') && !$userToDelete->hasRole('super admin') ) {

            $userToDelete->delete();

            return response()->json(['message' => 'Usuário excluído com sucesso.'], 200);
        }

        if ($userToDelete->hasRole('admin') || $userToDelete->hasRole('super admin')) {
            return response()->json(['error' => 'Não é permitido excluir usuários administradores.'], 403);
        }
        $userToDelete->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso.'], 200);
    }
}
