<?php

namespace App\Http\Controllers;

use App\Exports\FuncionariosExport;
use App\Http\Requests\StoreFuncionarioRequest;
use App\Http\Requests\StoreDadosContratoRequest;
use App\Http\Requests\UpdateFuncionarioRequest;
use App\Http\Requests\UpdateDadosContratoRequest;
use App\Http\Resources\FuncionarioResource;
use App\Models\Cargo;
use App\Models\DadosContrato;
use App\Models\Funcionario;
use App\Models\Unidade;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use Maatwebsite\Excel\Facades\Excel;

class FuncionarioController extends Controller
{
    private $funcionariosExport;
    public function __construct()
    {
        $this->middleware('permission:visualizar_funcionarios')->only(['index', 'show']);
        $this->middleware('permission:registrar_funcionarios')->only(['store', 'verficarCPF']);
        $this->middleware('permission:editar_funcionarios')->only('update');
        $this->middleware('permission:excluir_funcionarios')->only('destroy');


    }
    private function filtroFuncionarios(Request $request)
    {
        $query = Funcionario::with(['dadosContrato', 'unidade', 'cargo', 'biometria']);

        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('unidade_id', $user->unidade_id);
        }
        if (!$user->hasRole('super admin')) {
            $query->whereHas('unidade.localidade', function ($query) use ($user) {
                $query->where('setor_id', $user->setor_id);
            });
        }

        $query->when(!$request->has('allStatus'), function ($q) {
            $q->where('status', true);
        })->when($request->has('allStatus') && $request->filled('status'), function ($q) use ($request) {
            $q->where('status', filter_var($request->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
        });

        $query->when($request->nome, function ($query, $nome) {
            $query->where('nome', 'like', "%$nome%");
        });

        $query->when($request->vinculo, function ($query, $vinculo) {
            $query->whereHas('dadosContrato', function ($q) use ($vinculo) {
                $q->where('vinculo', 'like', "%$vinculo%");
            });
        });

        $query->when($request->unidade, function ($query, $unidade) {
            $query->whereHas('unidade', function ($q) use ($unidade) {
                $q->where('id', $unidade);
            });
        });

        $query->when($request->cargo, function ($query, $cargo) {
            $query->whereHas('cargo', function ($q) use ($cargo) {
                $q->where('id', $cargo);
            });
        });

        $query->when($request->biometria, function ($query, $biometria) {
            if ($biometria === 'Pendente') {
                $query->whereDoesntHave('biometria');
            } elseif ($biometria === 'Cadastrado') {
                $query->whereHas('biometria');
            }
        });

        if(!$request->order) {
            $query->orderBy('updated_at', 'desc');
        }

        $sortBy = $request->sortBy;
        $query->when($request->order, function ($query, $order) use ($sortBy) {
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

        return $query;
    }

    public function exportarFuncionarios(Request $request) {
        $funcionarios = $this->filtroFuncionarios($request)->get();
        return Excel::download(new FuncionariosExport($funcionarios), 'funcionarios.xlsx');
    }

    public function index(Request $request)
    {
        $query = $this->filtroFuncionarios($request);

        $perPage = $request->input('per_page', 10);
        if ($perPage == -1) {
            $perPage = $query->count();
        }

        $funcionariosPaginado = $query->paginate($perPage);
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

            $query = Funcionario::query();
            $query->where('id',$id);

            if (!$user->hasRole('super admin')) {
                $query->whereHas('unidade.localidade', function ($query) use ($user) {
                    $query->where('setor_id', $user->setor_id);
                });
            }
            $funcionario = $query->firstOrFail();

            if(!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id) {

                    return response()->json([
                        'message' => 'Acesso não autorizado'
                    ], 403);
            }

            $funcionario = new FuncionarioResource($funcionario);
            return response()->json($funcionario, 200);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            //funcionario
            $validatedFuncionario  = app(StoreFuncionarioRequest::class)->validated();

            $validatedFuncionario['data_nascimento'] = Carbon::create($validatedFuncionario['data_nascimento']);

            if ($request->hasFile('foto')) {
                $validatedFuncionario ['foto'] = $request->file('foto')->store('fotos_funcionarios', 'public');
            }

            $funcionario = Funcionario::create($validatedFuncionario );
            $funcionarioResource = new FuncionarioResource($funcionario);

            //dados contrato
            $validatedContrato = app(StoreDadosContratoRequest::class)->validated();

            $validatedContrato['funcionario_id'] = $funcionario->id;
            $validatedContrato['data_admissao'] = Carbon::create( $validatedContrato['data_admissao']);


            DadosContrato::create($validatedContrato);

            DB::commit();

            return response()->json([
                'message' => 'Funcionário criado com sucesso.',
                'funcionario' => $funcionarioResource
            ], 201);


        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao cadastrar funcionário '. $th], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            $query = Funcionario::query();
            $query->where('id',$id);

            if (!$user->hasRole('super admin')) {
                $query->whereHas('unidade.localidade', function ($query) use ($user) {
                    $query->where('setor_id', $user->setor_id);
                });
            }
            $funcionario = $query->firstOrFail();

            if(!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id ) {

                    return response()->json([
                        'message' => 'Acesso não autorizado'
                    ], 403);
            }

            $data = app(UpdateFuncionarioRequest::class)->validated();


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

            //contrato

            $contrato = DadosContrato::firstOrCreate(['id' => $request->dados_contrato_id]);

            $validatedContrato = app(UpdateDadosContratoRequest::class)->validated();

            if (isset($validatedContrato['data_admissao'])) {
                $validatedContrato['data_admissao'] = Carbon::create($validatedContrato['data_admissao']);
            }

            $contrato->update($validatedContrato);

            DB::commit();

            return response()->json([
                'message' => 'Funcionário atualizado com sucesso',
                'funcionario' => $funcionario
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao atualizar funcionário '. $th], 500);
        }

    }

    // Deletar um funcionário
    public function destroy(string $id)
    {
        $user = auth()->user();

        $query = Funcionario::query();
        $query->where('id',$id);

        if (!$user->hasRole('super admin')) {
            $query->whereHas('unidade.localidade', function ($query) use ($user) {
                $query->where('setor_id', $user->setor_id);
            });
        }
        $funcionario = $query->firstOrFail();
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

    public function apagarFoto(string $id)
    {

        $user = auth()->user();

        $query = Funcionario::query();
        $query->where('id',$id);

        if (!$user->hasRole('super admin')) {
            $query->whereHas('unidade.localidade', function ($query) use ($user) {
                $query->where('setor_id', $user->setor_id);
            });
        }
        $funcionario = $query->firstOrFail();

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
        $user = auth()->user();
        $query = Funcionario::query();

        if (!$user->hasRole('super admin')) {
            $query->whereHas('unidade.localidade', function ($query) use ($user) {
                $query->where('setor_id', $user->setor_id);
            });
        }

        $existe = $query->where('cpf', $cpf)->exists();
        return response()->json(['existe'=> $existe]);

    }

}
