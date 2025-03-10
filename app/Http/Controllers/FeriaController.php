<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeriasResource;
use App\Models\Feria;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_ferias')->only('index');
        $this->middleware('permission:registrar_ferias')->only('store');
        $this->middleware('permission:excluir_ferias')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Inicia a query a partir do modelo Feria
        $query = Feria::query();

        // Realiza os joins com as tabelas 'funcionarios' e 'unidades'
        $query->join('funcionarios', 'ferias.funcionario_id', '=', 'funcionarios.id')
              ->join('unidades', 'funcionarios.unidade_id', '=', 'unidades.id');

        // Seleciona os campos necessários, incluindo os nomes dos relacionamentos
        $query->selectRaw('
            MIN(ferias.id) as id,
            ferias.funcionario_id,
            ferias.data_inicio,
            ferias.data_fim,
            ferias.descricao,
            funcionarios.nome as funcionario,
            unidades.nome as unidade,
            COUNT(*) as total_dias
        ');

        $query->when($request->status, function( $query, $status ) {
            $hoje = now()->toDateString();
            if ($status === 'ativas') {
                $query->where('ferias.data_inicio', '<=', $hoje)
                      ->where('ferias.data_fim', '>=', $hoje);
            } elseif ($status === 'agendadas') {
                $query->where('ferias.data_inicio', '>', $hoje);
            } elseif ($status === 'finalizadas') {
                $query->where('ferias.data_fim', '<', $hoje);
            }
        });
               // Filtragem:
        $query->when($request->nome , function ($query, $nome) {
            $query->where('funcionarios.nome', 'like', "%$nome%");
        });

        $query->when($request->unidade, function ($query, $unidade){
            $query->where('unidades.id',  $unidade);

        });

        // Agrupamento para evitar registros duplicados por período
        $query->groupBy(
            'ferias.funcionario_id',
            'ferias.data_inicio',
            'ferias.data_fim',
            'ferias.descricao',
            'funcionarios.nome',
            'unidades.nome'
        );

        // Ordenação: permite ordenar pelos campos 'funcionario', 'unidade', 'data_inicio' ou 'data_fim'
        $order = $request->input('order', 'asc');
        if ($request->filled('sortBy')) {
            $sortBy = $request->input('sortBy');
            if (in_array($sortBy, ['funcionario', 'unidade', 'data_inicio', 'data_fim'])) {
                $query->orderBy($sortBy, $order);
            }
        }

        // Paginação:
        $perPage = $request->input('per_page', 10);
        if ($perPage == -1) {
            // Se for -1, retorna todos os registros (agrupados)
            $perPage = $query->get()->count();
        }

        $feriasPaginado = $query->paginate($perPage);

        // Caso utilize um Resource para transformar os dados:
        $ferias = FeriasResource::collection($feriasPaginado);
        return response()->json([
            'data' => $ferias,
            'meta' => [
                'current_page' => $feriasPaginado->currentPage(),
                'last_page'    => $feriasPaginado->lastPage(),
                'per_page'     => $feriasPaginado->perPage(),
                'total'        => $feriasPaginado->total(),
            ],
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date',
            'funcionario_id' => 'required|exists:funcionarios,id',
            'descricao' => 'nullable|string',
        ]);

        $dataInicio = Carbon::create($validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::create($validated['data_fim']) : $dataInicio;

        // Verifica se já existem férias cadastradas para esse funcionário nesse período
        $feriasExistentes = Feria::with('funcionario')->where('funcionario_id', $validated['funcionario_id'])
            ->where(function ($query) use ($dataInicio, $dataFim) {
                $query->whereBetween('data_inicio', [$dataInicio, $dataFim])
                    ->orWhereBetween('data_fim', [$dataInicio, $dataFim])
                    ->orWhere(function ($query) use ($dataInicio, $dataFim) {
                        $query->where('data_inicio', '<=', $dataInicio)
                                ->where('data_fim', '>=', $dataFim);
                    });
            })->first();

            if ($feriasExistentes) {
                $dataInicioExistente = Carbon::parse($feriasExistentes->data_inicio)->format('d/m/Y');
                $dataInicioFim = Carbon::parse($feriasExistentes->data_fim)->format('d/m/Y');
                $nome = $feriasExistentes->funcionario->nome;
                $motivo = $feriasExistentes->descricao;

            return response()->json([
                'error' => 'Conflito de férias!',
                'message' => $dataInicioExistente != $dataInicioFim
                 ? "$nome já possui dispensa concedida por motivo de $motivo marcadas entre $dataInicioExistente e $dataInicioFim. Escolha outra data ou altere o registro existente"
                 : "$nome já possui dispensa concedida por motivo de $motivo marcadas na data $dataInicioExistente. Escolha outra data ou altere o registro existente"
            ], 422);
        }

        // Criação dos dados de férias
        $datas = $dataInicio->daysUntil($dataFim)->map(function ($data) use ($validated, $dataFim) {
            return [
                'data' => $data->toDateString(),
                'funcionario_id' => $validated['funcionario_id'],
                'data_inicio' => $validated['data_inicio'],
                'data_fim' => $dataFim,
                'tipo' => 'ferias',
                'descricao' => $validated['descricao'] ?? 'Férias',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        // Inserção no banco de dados
        Feria::insert(collect($datas)->toArray());

        return response()->json([
            'message' => 'Férias adicionadas com sucesso!',
        ], 201);
    }
       /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|after_or_equal:data_inicio|date',
            'funcionario_id' => 'required|exists:funcionarios,id',
        ]);

        $dataInicio = Carbon::create($validated['data_inicio'])->toDateString();
        $dataFim = Carbon::create($validated['data_fim'])->toDateString();

        $totalApagado = Feria::where('funcionario_id', $validated['funcionario_id'])
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->delete();

            return response()->json([
                'message' => 'Férias removidas com sucesso!',
                'registro_apagados' => $totalApagado
                ], 200);
    }
}
