<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoTurnoRequest;
use App\Http\Requests\UpdateTipoTurnoRequest;
use App\Http\Resources\TipoTurnoResource;
use App\Models\TipoTurno;
use Illuminate\Http\Request;

class TipoTurnoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_turnos')->only(['index', 'show']);
        $this->middleware('permission:registrar_turnos')->only('store');
        $this->middleware('permission:editar_turnos')->only('update');
        $this->middleware('permission:excluir_turnos')->only('destroy');
    }

    /**
     * Lista os tipos de turno. Se um setor_id for informado, retorna os
     * turnos específicos daquele setor junto com os genéricos (setor_id nulo).
     */
    public function index(Request $request
    ) {
        $query = TipoTurno::query()->where('ativo', true);
        $setor = auth()->user()->setor_id;

        $query->where(function ($q) use ($setor) {
            $q->where('setor_id', $setor);
        });
        $query->when($request->nome, function ($query, $nome) {
            $query->where('nome', 'like', "%$nome%");
        });

        if (! $request->order) {
            $query->orderBy('codigo');
        }

        $perPage = $request->input('per_page', 10);
        if ($perPage == -1) {
            $perPage = $query->count();
        }

        $tipoTurnoPaginado = $query->paginate($perPage);

        $tipoTurnos = TipoTurnoResource::collection($tipoTurnoPaginado);

        return response()->json([
            'data' => $tipoTurnos,
            'meta' => [
                'current_page' => $tipoTurnoPaginado->currentPage(),
                'last_page'    => $tipoTurnoPaginado->lastPage(),
                'per_page'     => $tipoTurnoPaginado->perPage(),
                'total'        => $tipoTurnoPaginado->total(),
            ],
        ], 200);

    }

    public function store(StoreTipoTurnoRequest $request)
    {
        $tipoTurno = TipoTurno::create([
             ...$request->validated(),
            'setor_id' => auth()->user()->setor_id,
        ]);

        return response()->json([
            'message' => 'Tipo de turno criado com sucesso!',
            'data'    => $tipoTurno,
        ], 201);
    }

    public function show(TipoTurno $tipoTurno)
    {
        return new TipoTurnoResource($tipoTurno);
    }

    public function update(UpdateTipoTurnoRequest $request, string $id)
    {

        $data = $request->validated();

        $tipoTurno = TipoTurno::findOrFail($id);
        $tipoTurno->update($data);

        $tipoTurno = new TipoTurnoResource($tipoTurno);

        return response()->json([
            'message' => 'Tipo de turno atualizado com sucesso!',
            'data'    => $tipoTurno,
        ], 200);
    }

    public function destroy(TipoTurno $tipoTurno)
    {
        // Não apaga fisicamente: turnos já usados em escalas antigas precisam
        // continuar existindo para relatórios históricos. Apenas inativa.
        $tipoTurno->update(['ativo' => false]);

        return response()->json([
            'message' => 'Tipo de turno inativado com sucesso!',
        ], 200);
    }
}
