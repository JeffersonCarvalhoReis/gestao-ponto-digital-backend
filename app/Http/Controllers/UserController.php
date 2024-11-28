<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
    public function index(Request $request)
    {
        $query =  User::with('roles')
        ->with('unidade')
        ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super admin'));

        if($request->has('nome')) {
            $nome = $request->input('nome');
            $query->where('name', 'like', "%$nome%");
        }
        $users = $query->get();
        $users = UserResource::collection($users);

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
        $user = new UserResource($user);

        return response()->json([
            'message' => 'Usuário criado com sucesso.',
             'user' => $user
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('roles')->with('unidade')->findOrFail($id);

        $user = new UserResource($user);

        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $userToUpdate = User::with('roles')->findOrFail($id);
        Gate::authorize('update', $userToUpdate);

        $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userToUpdate->id,
            'senha' => 'sometimes|string|min:8',
            'funcao' => 'sometimes|exists:roles,name',
            'unidade' => 'sometimes|numeric|exists:unidades,id',
        ]);

        $data = [
            'name' => $request->nome,
            'email' => $request->email,
            'unidade_id' => $request->unidade,
        ];

        if(!empty($request->senha)){
            $data['password'] = Hash::make($request->senha);
        }

        $userToUpdate->update($data);

        if(isset($request->funcao)){
            $userToUpdate->syncRoles($request->funcao);
        }

        $userToUpdate = new UserResource($userToUpdate);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
             'user' => $userToUpdate
            ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $userToDelete = User::findOrFail($id);
        Gate::authorize('delete', $userToDelete);

        $userToDelete->delete();

        return response()->json([
            'message' => 'Usuário excluído com sucesso.'
        ], 200);
    }
}
