<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BiometriaController;
use App\Http\Resources\RegistroPontoResource;
use App\Models\Funcionario;
use App\Models\RegistroPonto;
use Carbon\Carbon;

class RegistroPontoController extends Controller
{

    public function buscarFuncionarioBiometria() {
        $biometria = new BiometriaController;

        $resposta = $biometria->identificar();
        $sucesso = $resposta->original['sucesso'];

        if($sucesso) {
            $funcionarioId = $resposta->original['funcionario'];
            return $this->registrarPonto($funcionarioId, $sucesso );
        } else {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }
    }

    public function buscarFuncionarioManualmente(string $funcionario) {

        $funcionarioExiste = Funcionario::where('id', $funcionario)->first();

        if($funcionarioExiste) {
            return $this->registrarPonto($funcionario, false );
        } else {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }
    }

   public function registrarPonto(string $funcionarioId, bool $biometria)
   {


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
            'biometrico' => (bool)$biometria,
        ]);

        return response()->json([
            'message' => 'Hora de entrada registrada com sucesso!',
            'registro' => $novoRegistro,
            'criado ' => $novoRegistro->created_at->timezone('America/Sao_Paulo')->format('Y-m-d H:i:s'),
        ], 200);
   }
   public function registroDoDia() {

    $user = auth()->user();
    $query = RegistroPonto::with('funcionario')->whereDate('data_local', Carbon::today());


    if (!$user->hasAnyRole(['admin', 'super admin'])) {
        $query->whereHas('funcionario', function ($q) use ($user) {
            $q->where('unidade_id', $user->unidade_id);
        });
    }

    $registros = $query->orderBy('updated_at', 'desc')->get();
    $registros = RegistroPontoResource::collection($registros);

     return response()->json( [
        'registros_do_dia' => $registros,
     ], 200);
   }
}
