<?php

namespace App\Http\Controllers;

use App\Models\RegistroPonto;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegistroPontoController extends Controller
{
   public function registrarPonto(Request $request)
   {
    $funcionarioId = $request->input('funcionario_id');

    $registroAberto = RegistroPonto::where('funcionario_id', $funcionarioId)
        ->whereNull('hora_saida')
        ->orderBy('data_local', 'desc')->first();
    if ($registroAberto) {

        $horaEntrada = Carbon::parse($registroAberto->data_local);

        if ($horaEntrada->isToday()) {
            $registroAberto->update(['hora_saida' => Carbon::now()]);
            return response()->json([
                'message' => 'Hora de saída registrada com sucesso!',
                 'registro' => $registroAberto
                ], 200);
             }
        }

        $novoRegistro = RegistroPonto::create([
            'funcionario_id' => $funcionarioId,
            'hora_entrada' => Carbon::now(),
            'biometrico' => (bool)$request->input('biometrico'),
        ]);

        return response()->json([
            'message' => 'Hora de entrada registrada com sucesso!',
            'registro' => $novoRegistro,
            'criado ' => $novoRegistro->created_at->timezone('America/Sao_Paulo')->format('Y-m-d H:i:s'),
        ], 200);
   }
}
