<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoTurnoRequest;
use App\Http\Requests\UpdateTipoTurnoRequest;
use App\Models\TipoTurno;

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
    public function index()
    {
        $query = TipoTurno::query()->where('ativo', true);
        $setor = auth()->user()->setor_id;

        $query->where(function ($q) use ($setor) {
            $q->where('setor_id', $setor)->orWhereNull('setor_id');
        });

        return $query->orderBy('codigo')->get();
    }

    public function store(StoreTipoTurnoRequest $request)
    {
        $tipoTurno = TipoTurno::create($request->validated());

        return response()->json([
            'message' => 'Tipo de turno criado com sucesso!',
            'data'    => $tipoTurno,
        ], 201);
    }

    public function show(TipoTurno $tipoTurno)
    {
        return $tipoTurno;
    }

    public function update(UpdateTipoTurnoRequest $request, TipoTurno $tipoTurno)
    {
        $tipoTurno->update($request->validated());

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
