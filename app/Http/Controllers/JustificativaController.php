<?php

namespace App\Http\Controllers;

use App\Http\Resources\JustificativaListResource;
use App\Http\Resources\JustificativaResource;
use App\Models\Funcionario;
use App\Models\Justificativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Storage;

class JustificativaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_justificativas')->only('index');
        $this->middleware('permission:registrar_justificativas')->only('store');
        $this->middleware('permission:editar_justificativas')->only('update');
        $this->middleware('permission:excluir_justificativas')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Inicia a query com joins
        $user = auth()->user();
        $query = Justificativa::query()

        ->join('funcionarios', 'justificativas.funcionario_id', '=', 'funcionarios.id')
        ->join('unidades', 'funcionarios.unidade_id', '=', 'unidades.id')
        ->selectRaw('
            MIN(justificativas.id) as id,
            justificativas.funcionario_id,
            MAX(justificativas.motivo) as motivo,
            MAX(justificativas.anexo) as anexo,
            MAX(justificativas.status) as status,
            MAX(justificativas.created_at) as criado,
            justificativas.data_inicio,
            justificativas.data_fim,
            funcionarios.nome as funcionario,
            unidades.nome as unidade
        ')
        ->groupBy(
            'justificativas.funcionario_id',
            'funcionarios.nome',
            'unidades.nome',
            'justificativas.data_inicio',
            'justificativas.data_fim',
            'justificativas.motivo',
            'justificativas.status'
        );
        if (!$user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('funcionarios.unidade_id', $user->unidade_id);
        }

        // Filtro por nome do funcionário
        $query->when($request->nome, function ($query, $nome) {
            $query->where('funcionarios.nome', 'like', "%$nome%");
        });
        $query->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        });

        // Filtro por unidade
        $query->when($request->unidade, function ($query, $unidade){
            $query->where('unidades.id', $unidade);
        });

        // Ordenação
        $order = $request->input('order', 'asc');
        if ($request->filled('sortBy')) {
            $sortBy = $request->input('sortBy');

            // Mapeando sortBy para nomes reais das colunas
            $columnsMap = [
                'funcionario' => 'funcionarios.nome',
                'unidade'     => 'unidades.nome',
                'data_inicio' => 'justificativas.data_inicio',
                'data_fim'    => 'justificativas.data_fim',
                'status'      => 'status'
            ];

            if (isset($columnsMap[$sortBy])) {
                $query->orderBy($columnsMap[$sortBy], $order);
            }
        } else {
            $query->orderBy('criado', 'desc');
        }

        // Paginação
        $perPage = $request->input('per_page', 10);
        if ($perPage == -1) {
            $perPage = $query->count();
        }

        $justificativasPaginado = $query->paginate($perPage);

        $justificativas = JustificativaListResource::collection($justificativasPaginado);

        return response()->json([
            'data' => $justificativas,
            'meta' => [
                'current_page' => $justificativasPaginado->currentPage(),
                'last_page'    => $justificativasPaginado->lastPage(),
                'per_page'     => $justificativasPaginado->perPage(),
                'total'        => $justificativasPaginado->total(),
            ],
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $funcionario = Funcionario::findOrFail($request->funcionario_id);

        Gate::authorize('store', $funcionario);

        $validated = $request->validate([
            'funcionario_id' => 'required|exists:funcionarios,id',
            'motivo' => 'required|string',
            'anexo' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date',
        ]);

        if ($request->hasFile('anexo')) {
            $validated['anexo'] = $request->file('anexo')->store('justificativas', 'public');
        }

        $dataInicio = Carbon::create($validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::create( $validated['data_fim']) : $dataInicio;


        $datas = $dataInicio->daysUntil($dataFim)->map(function($data) use ($validated, $dataInicio, $dataFim){
            return [
                'data' => $data->toDateString(),
                'funcionario_id' => $validated['funcionario_id'],
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'motivo' => $validated['motivo'],
                'anexo' => $validated['anexo'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Justificativa::insert(collect($datas)->toArray() );


        return response()->json([
            'message' => 'Justificativa criada com sucesso',
        ], 201);
    }

    public function show($id)
    {
            $justificativa = Justificativa::with('funcionario')->findOrFail($id);

            Gate::authorize('show', $justificativa);


            $justificativa = new JustificativaResource($justificativa);
            return response()->json($justificativa, 200);
    }


/**
 * Update the specified resource in storage.
 */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $justificativa = Justificativa::findOrFail($id);

        if (!$user->hasAnyRole(['admin', 'super admin']) && isset($request->status)) {
            return response()->json(['message' => 'Ação não autorizada'],403);
        }

         // Regras de validação condicionais
        $rules = [
            'motivo' => 'nullable|string',
            'status' => 'nullable|in:pendente,aprovado,recusado',
        ];

        if($request->status == 'recusado') {
            $rules['motivo_recusa'] = 'required|string';
        }

        // Adiciona regra para anexo apenas se estiver presente na requisição
        if ($request->hasFile('anexo')) {
            $rules['anexo'] = 'file|mimes:jpg,jpeg,png,pdf,docx';
        }

        $validated = $request->validate($rules);

        // Obter os dados necessários para identificar todas as justificativas relacionadas
        $funcionarioId = $justificativa->funcionario_id;
        $unidadeId     = $justificativa->funcionario->unidade_id;
        $dataInicio    = $justificativa->data_inicio;
        $dataFim       = $justificativa->data_fim;
        $motivo       = $justificativa->motivo;

        // Buscar todas as justificativas com os mesmos critérios
        $justificativasParaAtualizar = Justificativa::where('funcionario_id', $funcionarioId)
            ->whereHas('funcionario', function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
            })
            ->where('data_inicio', $dataInicio)
            ->where('data_fim', $dataFim)
            ->where('motivo', $motivo)
            ->get();

        // Processar o anexo apenas uma vez
        if ($request->hasFile('anexo')) {
            // Excluir o anexo antigo se existir
            if ($justificativa->anexo) {
                Storage::disk('public')->delete($justificativa->anexo);
            }
            $validated['anexo'] = $request->file('anexo')->store('justificativas', 'public');
        }

        // Atualizar todas as justificativas encontradas
        foreach ($justificativasParaAtualizar as $just) {
            $just->update($validated);
        }

        return response()->json([
            'message' => 'Justificativa atualizada com sucesso',

        ], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $justificativa = Justificativa::findOrFail($id);
        Gate::authorize('delete', $justificativa);

        $funcionarioId = $justificativa->funcionario_id;
        $unidadeId     = $justificativa->funcionario->unidade_id;
        $dataInicio    = $justificativa->data_inicio;
        $dataFim       = $justificativa->data_fim;
        // Busca todas as justificativas com os mesmos critérios

        $justificativasParaExcluir = Justificativa::where('funcionario_id', $funcionarioId)
            ->whereHas('funcionario', function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
            })
            ->where('data_inicio', $dataInicio)
            ->where('data_fim', $dataFim)
            ->get();

        // Remove os anexos (se houver) e exclui
        foreach ($justificativasParaExcluir as $just) {
            if ($just->anexo) {
                Storage::disk('public')->delete($just->anexo);
            }
            $just->delete();
        }

        return response()->json([
            'message' => 'Justificativa excluída com sucesso'
        ], 200);
    }
}
