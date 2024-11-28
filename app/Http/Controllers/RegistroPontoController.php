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
        ->first();
    if ($registroAberto) {
        $registroAberto->update(['hora_saida' => Carbon::now()->format('H:i:s')]);
        return response()->json([
            'message' => 'Hora de saída registrada com sucesso!',
             'registro' => $registroAberto
            ], 200);
        }

        $novoRegistro = RegistroPonto::create([
            'funcionario_id' => $funcionarioId,
            'hora_entrada' => Carbon::now()->format('H:i:s'),
            'biometrico' => (bool)$request->input('biometrico'),
        ]);

        return response()->json([
            'message' => 'Hora de entrada registrada com sucesso!',
            'registro' => $novoRegistro,
        ], 200);
   }
}
