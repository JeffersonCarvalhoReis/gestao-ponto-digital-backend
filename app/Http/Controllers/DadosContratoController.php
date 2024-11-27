<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDadosContratoRequest;
use App\Http\Requests\UpdateDadosContratoRequest;
use App\Models\DadosContrato;

class DadosContratoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:registrar_dados-contratos')->only('store');
        $this->middleware('permission:editar_dados_contratos')->only('update');
    }
     /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDadosContratoRequest $request)
    {
        $dado = $request->validated();
        DadosContrato::create($dado);

        return response()->json(['message' => 'Dados cadastrados com sucesso.'], 201);

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDadosContratoRequest $request, string $id)
    {
        $contrato = DadosContrato::findOrFail($id);

        $data = $request->validated();

        $contrato->update($data);

        return response()->json(['message' => 'Dados atualizados com sucesso', 'Funcionario'], 200);

    }
}
