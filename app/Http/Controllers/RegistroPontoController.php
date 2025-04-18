<?php

namespace App\Http\Controllers;

use App\Events\NovoRegistroPonto;
use App\Http\Controllers\BiometriaController;
use App\Http\Resources\RegistroPontoResource;
use App\Models\Funcionario;
use App\Models\RegistroPonto;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegistroPontoController extends Controller
{

    public function buscarFuncionarioBiometria(Request $request) {



        $biometria = new BiometriaController;

        $resposta = $biometria->identificar($request);
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
    $funcionario = Funcionario::find($funcionarioId);
    $setorId = $funcionario->unidade->localidade->setor_id;

    if ($registroAberto) {

        $horaEntrada = Carbon::parse($registroAberto->data_local);

        if ($horaEntrada->isToday()) {
            $registroAberto->update(['hora_saida' => Carbon::now()]);
            broadcast(new NovoRegistroPonto($registroAberto, $setorId))->toOthers();
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

        broadcast(new NovoRegistroPonto($novoRegistro, $setorId ))->toOthers();

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
    if ($user->hasAnyRole( 'admin')) {
        $query->whereHas('funcionario', function ($q) use ($user) {
            $q->whereHas('unidade', function($q2) use ($user) {
                $q2->whereHas('localidade', function($q3) use ($user) {
                    $q3->where('setor_id', $user->setor_id);
                });
            });
        });
    }

    $registros = $query->orderBy('updated_at', 'desc')->get();
    $registros = RegistroPontoResource::collection($registros);

     return response()->json( [
        'registros_do_dia' => $registros,
     ], 200);
   }
}
