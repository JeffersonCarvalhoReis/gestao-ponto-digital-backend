<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuncionarioRequest;
use App\Http\Requests\UpdateFuncionarioRequest;
use App\Http\Resources\FuncionarioResource;
use App\Models\Cargo;
use App\Models\DadosContrato;
use App\Models\Funcionario;
use App\Models\Unidade;
use App\Models\Biometria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;

class FuncionarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_funcionarios')->only(['index', 'show']);
        $this->middleware('permission:registrar_funcionarios')->only(['store', 'verficarCPF']);
        $this->middleware('permission:editar_funcionarios')->only('update');
        $this->middleware('permission:excluir_funcionarios')->only('destroy');


    }
    public function index(Request $request)
    {
        $query = Funcionario::with(['dadosContrato', 'unidade', 'cargo', 'biometria']);

        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('unidade_id', $user->unidade_id);
        }

        $query->when($request->nome , function ($query, $nome) {
            $query->where('nome', 'like', "%$nome%");
        });

        $query->when($request->vinculo, function ($query,$vinculo) {
            $query->whereHas('dadosContrato', function($q) use ($vinculo){
                $q->where('vinculo', 'like', "%$vinculo%");
            });

        });

        $query->when($request->unidade, function ($query, $unidade){

            $query->whereHas('unidade', function ($q) use ($unidade) {
                $q->where('id',  $unidade);
            });
        });

        $query->when($request->cargo, function ($query, $cargo){
            $query->whereHas('cargo', function ($q) use ($cargo) {
                $q->where('id',  $cargo);
            });

        });
        $query->when($request->biometria, function ($query, $biometria) {
            if ($biometria === 'Pendente') {
                $query->whereDoesntHave('biometria'); // Filtra funcionários SEM biometria
            } elseif ($biometria === 'Cadastrado') {
                $query->whereHas('biometria'); // Filtra funcionários COM biometria
            }
        });

        $perPage = $request->input('per_page', 10);
        if($perPage == -1) {
            $perPage = Funcionario::count();
        }

        $sortBy = $request->sortBy;
        $query->when( $request->order, function ($query, $order) use ($sortBy) {
            switch ($sortBy) {
                case 'unidade':
                    $query->whereHas('unidade')
                    ->orderBy(
                        Unidade::select('nome')
                            ->whereColumn('unidades.id', 'funcionarios.unidade_id'),
                        $order
                    );
                break;
                case 'cargo':
                    $query->whereHas('cargo')
                    ->orderBy(
                        Cargo::select('nome')
                            ->whereColumn('cargos.id', 'funcionarios.cargo_id'),
                        $order
                    );
                break;

                case 'vinculo':
                    $query->whereHas('dadosContrato')
                    ->orderBy(
                        DadosContrato::select('vinculo')
                            ->whereColumn('dados_contratos.funcionario_id', 'funcionarios.id'),
                        $order
                    );
                break;
                case 'biometria':
                    $query->selectRaw(
                        '*, (SELECT COUNT(*) FROM biometrias WHERE biometrias.funcionario_id = funcionarios.id) as has_biometria'
                    )->orderBy('has_biometria', $order);

                break;

                default:

                $query->orderBy($sortBy, $order);
                break;
            }

        });
        $funcionariosPaginado = $query->paginate($perPage );
        $funcionarios = FuncionarioResource::collection($funcionariosPaginado);

        return response()->json([
            'data' => $funcionarios,
            'meta' => [
                'current_page' => $funcionariosPaginado->currentPage(),
                'last_page' => $funcionariosPaginado->lastPage(),
                'per_page' => $funcionariosPaginado->perPage(),
                'total' => $funcionariosPaginado->total(),
            ],
        ], 200);
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

        $data['data_nascimento'] = Carbon::create($data['data_nascimento']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('fotos_funcionarios', 'public');
        }

        $funcionario = Funcionario::create($data);
        $funcionario = new FuncionarioResource($funcionario);

        return response()->json([
            'message' => 'Funcionário criado com sucesso.',
            'funcionario' => $funcionario
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
            $data['data_nascimento'] = Carbon::create($data['data_nascimento']);
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
            'funcionario' => $funcionario
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

    public function apagarFoto(string $id) {
        $user = auth()->user();

        $funcionario = Funcionario::findOrFail($id);

        if(!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id ) {

            return response()->json([
                'message' => 'Acesso não autorizado'
            ], 403);
        }

        if ($funcionario->foto) {
            Storage::disk('public')->delete($funcionario->foto);
            $funcionario->foto = null;
            $funcionario->save();
            return response()->json([
                'message' => 'Foto excluída com sucesso.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Nenhuma foto encontrada'
            ], 404);
        }

    }
    public function verificaCPF($cpf)
     {

        $existe = Funcionario::where('cpf', $cpf)->exists();

        return response()->json(['existe'=> $existe]);

    }

}
