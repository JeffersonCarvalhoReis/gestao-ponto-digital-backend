<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\Justificativa;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
    public function index(string $id)
    {
        $user = auth()->user();
        $funcionario = Funcionario::findOrFail($id);

        if (!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }

        $justificativa = Justificativa::where('funcionario_id', $id)->get();

        return response()->json($justificativa, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $funcionario = Funcionario::findOrFail($request->funcionario_id);

        if (!$user->hasAnyRole(['admin', 'super admin']) && $funcionario->unidade_id !== $user->unidade_id) {
            return response()->json(['message' => 'Acesso não autorizado'], 403);
        }
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
                'anexo' => $validated['anexo'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Justificativa::insert(collect($datas)->toArray() );


        return response()->json([
            'message' => 'Justificativa criada com sucesso',
        ], 201);
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

        $validated = $request->validate([
            'motivo' => 'nullable|string',
            'status' => 'nullable|in:pendente,aprovado,recusado',
            'anexo' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx'
        ]);

        if ($request->hasFile('anexo')) {
            if ($justificativa->anexo) {
                Storage::disk('public')->delete($justificativa->anexo);
            }

            $validated['anexo'] = $request->file('anexo')->store('justificativas', 'public');
        }

        $justificativa->update($validated);

        return response()->json([
            'message' => 'Justificativa atualizada com sucesso',
            'justificativa' => $justificativa
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();
        $justificativa = Justificativa::findOrFail($id);

        if (!$user->hasAnyRole(['admin', 'super admin']) && $justificativa->funcionario->unidade_id !== $user->unidade_id) {

            return response()->json(['message' => 'Acesso não autorizado'],403);
        }

        if ($justificativa->anexo) {
            Storage::disk('public')->delete($justificativa->anexo);
        }

        $justificativa->delete();

        return response()->json([
            'message' => 'Justificativa excluída com sucesso'
        ], 200);
    }
}
