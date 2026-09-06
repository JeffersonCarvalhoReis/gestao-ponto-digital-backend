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
    /**
     * Limite máximo (em horas) admitido entre a entrada e a saída de um
     * mesmo turno. Existem plantões legítimos de até 24h, então o limite
     * fica acima disso apenas como uma rede de segurança contra erro ou
     * fraude (ex: esquecer de bater a saída por dias) — quando estourado,
     * a saída não é fechada automaticamente e um administrador precisa
     * corrigir o registro manualmente.
     */
    private const MAX_HORAS_TURNO = 30;

    public function buscarFuncionarioBiometria(Request $request)
    {

        $acao = $this->validarAcao($request->input('acao'));

        $biometria = new BiometriaController;

        $resposta = $biometria->identificar($request);
        $sucesso  = $resposta->original['sucesso'];

        if ($sucesso) {
            $funcionarioId = $resposta->original['funcionario'];
            return $this->registrarPonto($funcionarioId, $sucesso, $acao);
        } else {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }
    }

    public function buscarFuncionarioManualmente(Request $request, string $funcionario)
    {

        $acao = $this->validarAcao($request->input('acao'));

        $funcionarioExiste = Funcionario::with('unidade.localidade.setor')->where('id', $funcionario)->first();

        if (! $funcionarioExiste) {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }

        $setor = $funcionarioExiste->unidade?->localidade?->setor;

        if ($setor && $setor->bloquear_ponto_sem_biometria) {
            return response()->json([
                'message' => 'O registro de ponto sem biometria está desabilitado. Caso esteja com problemas com a biometria, entre em contato com a administração.',
            ], 403);
        }

        return $this->registrarPonto($funcionario, false, $acao);
    }

    /**
     * Garante que a ação recebida do front-end é sempre explícita
     * ("entrada" ou "saida"). Isso evita que o sistema tenha que "adivinhar"
     * a intenção do funcionário a partir do último registro em aberto —
     * adivinhação que, historicamente, é a principal fonte de erro/fraude
     * (ex: bater duas entradas seguidas, ou não conseguir bater a saída
     * depois da meia-noite porque o sistema achava que já era "outro dia").
     */
    private function validarAcao(?string $acao): string
    {
        if (! in_array($acao, ['entrada', 'saida'], true)) {
            abort(response()->json([
                'message' => 'Ação inválida. Selecione "Registrar Entrada" ou "Registrar Saída".',
            ], 422));
        }

        return $acao;
    }

    public function registrarPonto(string $funcionarioId, bool $biometria, string $acao)
    {
        $funcionario = Funcionario::find($funcionarioId);

        if (! $funcionario) {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }

        $setorId = $funcionario->unidade->localidade->setor_id;

        // Busca o turno em aberto (entrada sem saída) independente do dia em
        // que a entrada ocorreu, para permitir fechar a saída depois da
        // meia-noite sem "perder" o registro do dia anterior.
        $registroAberto = RegistroPonto::where('funcionario_id', $funcionarioId)
            ->whereNull('hora_saida')
            ->orderByDesc('id')
            ->first();

        if ($acao === 'entrada') {
            if ($registroAberto) {
                return response()->json([
                    'message'  => 'Já existe uma entrada em aberto sem saída registrada. Registre a saída antes de uma nova entrada.',
                    'registro' => $registroAberto,
                ], 422);
            }

            $novoRegistro = RegistroPonto::create([
                'funcionario_id' => $funcionarioId,
                'hora_entrada'   => Carbon::now(),
                'biometrico'     => (bool) $biometria,
            ]);

            broadcast(new NovoRegistroPonto($novoRegistro, $setorId))->toOthers();

            return response()->json([
                'message'  => 'Hora de entrada registrada com sucesso!',
                'registro' => $novoRegistro,
                'criado '  => $novoRegistro->created_at->timezone('America/Sao_Paulo')->format('Y-m-d H:i:s'),
            ], 200);
        }

        // $acao === 'saida'
        if (! $registroAberto) {
            return response()->json([
                'message' => 'Nenhuma entrada em aberto foi encontrada para registrar a saída.',
            ], 422);
        }

        $entradaCompleta = Carbon::parse($registroAberto->data_local . ' ' . $registroAberto->hora_entrada);
        $agora           = Carbon::now();

        if ($entradaCompleta->diffInHours($agora) > self::MAX_HORAS_TURNO) {
            return response()->json([
                'message'  => sprintf(
                    'Já se passaram mais de %d horas desde a entrada registrada em %s. Por segurança, a saída não foi fechada automaticamente — solicite a um administrador que corrija este registro manualmente.',
                    self::MAX_HORAS_TURNO,
                    $entradaCompleta->timezone('America/Sao_Paulo')->format('d/m/Y H:i')
                ),
                'registro' => $registroAberto,
            ], 422);
        }

        $registroAberto->update(['hora_saida' => $agora]);

        broadcast(new NovoRegistroPonto($registroAberto, $setorId))->toOthers();

        return response()->json([
            'message'  => 'Hora de saída registrada com sucesso!',
            'registro' => $registroAberto,
        ], 200);
    }
    public function registroDoDia()
    {

        $user  = auth()->user();
        $query = RegistroPonto::with('funcionario')->whereDate('data_local', Carbon::today());

        if (! $user->hasAnyRole(['admin', 'super admin'])) {
            $query->whereHas('funcionario', function ($q) use ($user) {
                $q->where('unidade_id', $user->unidade_id);
            });
        }
        if ($user->hasAnyRole('admin')) {
            $query->whereHas('funcionario', function ($q) use ($user) {
                $q->whereHas('unidade', function ($q2) use ($user) {
                    $q2->whereHas('localidade', function ($q3) use ($user) {
                        $q3->where('setor_id', $user->setor_id);
                    });
                });
            });
        }

        $registros = $query->orderBy('updated_at', 'desc')->get();
        $registros = RegistroPontoResource::collection($registros);

        return response()->json([
            'registros_do_dia' => $registros,
        ], 200);
    }
}
