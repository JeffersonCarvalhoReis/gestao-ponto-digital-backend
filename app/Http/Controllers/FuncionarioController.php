<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuncionarioRequest;
use App\Http\Requests\UpdateFuncionarioRequest;
use App\Http\Resources\FuncionarioResource;
use App\Models\Funcionario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FuncionarioController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:visualizar_funcionarios')->only('index');
        // $this->middleware('permission:registrar_funcionarios')->only('store');
        // $this->middleware('permission:visualizar_funcionarios')->only('show');
        // $this->middleware('permission:editar_funcionarios')->only('update');
        // $this->middleware('permission:excluir_funcionarios')->only('destroy');

    }
    public function index(Request $request)
    {
        $query = Funcionario::with('dadosContrato');

        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('unidade_id', $user->unidade_id);
        }

        if ($request->filled('nome')) {
            $query->where('nome', 'like', "%$request->nome%");
        }

        if ($request->filled('vinculo')) {
            $query->whereHas('dadosContrato', function($q) use ($request){
                $q->where('vinculo', 'like', "%$request->vinculo%");
            });
        }

        if ($request->filled('unidade_id')) {
            $query->whereHas('unidade', function ($q) use ($request) {
                $q->where('nome', 'like', "%$request->unidade%");
            });
        }

        if ($request->filled('cargo_id')) {
            $query->whereHas('cargo', function ($q) use ($request) {
                $q->where('nome', 'like', "%$request->cargo%");
            });
        }

        $funcionarios = $query->get();
        $funcionarios = FuncionarioResource::collection($funcionarios);

        return response()->json($funcionarios, 200);

    }

    public function show($id)
    {
            $user = auth()->user();

            $funcionario = Funcionario::findOrFail($id);

            if(!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id) {

                    return response()->json([
                        'message' => 'Acesso não autorizado'
                    ], 403);
            }

            $funcionario = new FuncionarioResource($funcionario);
            return response()->json($funcionario, 200);
    }

    public function store(StoreFuncionarioRequest $request)
    {
        $data = $request->validated();

        $data['data_nascimento'] = Carbon::createFromFormat('d/m/Y', $data['data_nascimento'])->format('Y-m-d');

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('fotos_funcionarios', 'public');
        }

        $funcionario = Funcionario::create($data);
        $funcionario = new FuncionarioResource($funcionario);

        return response()->json([
            'message' => 'Funcionário criado com sucesso.',
            'Funcionario' => $funcionario
        ], 201);
    }

    public function update(UpdateFuncionarioRequest $request, string $id)
    {
        $user = auth()->user();

        $funcionario = Funcionario::findOrFail($id);

        if(!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id ) {

                return response()->json([
                    'message' => 'Acesso não autorizado'
                ], 403);
        }

        $data = $request->validated();

        if (isset($data['data_nascimento'])) {
            $data['data_nascimento'] = Carbon::createFromFormat('d/m/Y', $data['data_nascimento'])->format('Y-m-d');
        }

        if ($request->hasFile('foto')) {
            if ($funcionario->foto) {
                Storage::disk('public')->delete($funcionario->foto);
            }
            $data['foto'] = $request->file('foto')->store('fotos_funcionarios', 'public');
        }

        $funcionario->update($data);
        $funcionario = new FuncionarioResource($funcionario);

        return response()->json([
            'message' => 'Funcionário atualizado com sucesso',
            'Funcionario' => $funcionario
        ], 200);
    }

    // Deletar um funcionário
    public function destroy(string $id)
    {
        $user = auth()->user();

        $funcionario = Funcionario::findOrFail($id);

        if(!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id ) {

            return response()->json([
                'message' => 'Acesso não autorizado'
            ], 403);
        }

        if ($funcionario->foto) {
            Storage::disk('public')->delete($funcionario->foto);
        }

        $funcionario->delete();
        return response()->json([
            'message' => 'Funcionário excluído com sucesso.'
        ], 200);
    }
}
