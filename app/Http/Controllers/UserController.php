<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_usuarios')->only(['index', 'show']);
        $this->middleware('permission:registrar_usuarios')->only('store');
        $this->middleware('permission:editar_usuarios')->only('update');
        $this->middleware('permission:excluir_usuarios')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authUser = auth()->user();

        $query =  User::with(['roles', 'unidade'])
        ->with('unidade')
        ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super admin'));
        if(!$authUser->hasRole('super admin')) {
            $query->where('setor_id', $authUser->setor_id);
        }

        if($request->has('user')) {
            $user = $request->input('user');
            $query->where('name', 'like', "%$user%");
        }

        $perPage = $request->input('per_page', 10);
        if($perPage == -1) {
            $perPage = User::count();
        }
        if (!$request->order) {
            $query->orderBy('updated_at', 'desc');
        }
        $sortBy = $request->sortBy;
        $query->when( $request->order, function ($query, $order) use ($sortBy) {
            switch ($sortBy) {
                case 'unidade_nome':
                    $query->whereHas('unidade')
                    ->orderBy(
                    Unidade::select('nome')
                            ->whereColumn('unidades.id', 'users.setor_id'),
                $order
                    );
                break;

                case 'funcao':
                    $query->addSelect([
                            'role_name' => Role::select('name')
                            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->whereColumn('model_has_roles.model_id', 'users.id')
                            ->orderBy('roles.name', $order)
                            ->limit(1)
                    ])->orderBy('role_name', $order);
                break;

                default:

                $query->orderBy($sortBy, $order);
                break;
            }

        });

        $usersPaginado= $query->paginate($perPage);
        $users = UserResource::collection($usersPaginado);

        return response()->json([
            'data' => $users,
            'meta' => [
                'current_page' => $usersPaginado->currentPage(),
                'last_page' => $usersPaginado->lastPage(),
                'per_page' => $usersPaginado->perPage(),
                'total' => $usersPaginado->total(),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $request->merge(['setor_id' => $user->setor_id]);
        }

        $request->validate([
            'user' => 'required|string|unique:users',
            'senha' => 'required|string|min:8',
            'funcao' => 'required|exists:roles,name',
            'unidade' => 'required|numeric|exists:unidades,id',
            'setor_id' => 'required|numeric|exists:setores,id'
        ]);


        if (!$request->user()->can('store', [User::class, $request->funcao])) {
            return response()->json(['message' => 'Não autorizado a criar este tipo de usuário.'], 403);
        }
        $user = User::create([
            'user' => $request->user,
            'password' => Hash::make($request->senha),
            'unidade_id' => (int)$request->unidade,
            'setor_id' => (int)$request->setor_id,

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
            'user' => 'sometimes|string|unique:users,user,' . $userToUpdate->id,
            'senha' => 'sometimes|string|min:8',
            'funcao' => 'sometimes|exists:roles,name',
            'unidade' => 'sometimes|numeric|exists:unidades,id',
            'setor_id' => 'sometimes|exists:setores,id'
        ]);

        $data = [
            'user' => $request->user,
            'unidade_id' => $request->unidade,
            'setor_id' => $request->setor_id,
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

    //metodos para o proprio usuario

    public function profile(Request $request)
    {
        return response()->json($request->user()->user);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'user' => 'sometimes|string|unique:users,user,' . $user->id,
            'senha' => 'sometimes|string|min:3',
            'novaSenha' => 'nullable|string|min:8',
        ]);

        if ($request->filled('novaSenha')) {
            if(!$request->filled('senha')) {
                throw ValidationException::withMessages([
                    'senhaAtual' => 'Senha atual necessária para essa alteraçao.',
                ]);
            }
            if (!Hash::check($request->senha, $user->password)) {
                throw ValidationException::withMessages([
                    'senhaAtual' => 'Senha atual incorreta.',
                ]);
            }
            $data['password'] = Hash::make($request->novaSenha);
        }

        $data['user'] = $request->user;
        $user->update($data);

        return response()->json(['message' => 'Conta atualizada com sucesso']);
    }

    public function deleteUser(Request $request)
    {
        $request->validate([
            'senha' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->senha, $user->password)) {
            return response()->json(['message' => 'Senha incorreta.'], 403);
        }

        app(AuthController::class)->logout($request);

        $user->delete();

        return response()->json(['message' => 'Conta excluída com sucesso'], 200)
            ->withoutCookie('laravel_session')
            ->withoutCookie('XSRF-TOKEN');
    }

}
