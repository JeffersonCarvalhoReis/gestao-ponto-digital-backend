<?php

namespace App\Http\Controllers;

use App\Events\NovoRegistroPonto;
use App\Http\Controllers\BiometriaController;
use App\Http\Resources\RegistroPontoResource;
use App\Models\Funcionario;
use App\Models\RegistroPonto;
use App\Models\RegistroPontoEdicao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function buscarFuncionarioBiometria(Request $request) {

        $acao = $this->validarAcao($request->input('acao'));

        $biometria = new BiometriaController;

        $resposta = $biometria->identificar($request);
        $sucesso = $resposta->original['sucesso'];

        if($sucesso) {
            $funcionarioId = $resposta->original['funcionario'];
            return $this->registrarPonto($funcionarioId, $sucesso, $acao);
        } else {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }
    }

    public function buscarFuncionarioManualmente(Request $request, string $funcionario) {

        $acao = $this->validarAcao($request->input('acao'));

        $funcionarioExiste = Funcionario::with('unidade.localidade.setor')->where('id', $funcionario)->first();

        if(! $funcionarioExiste) {
            return response()->json(['message' => 'Funcionário não encontrado'], 404);
        }

        $setor = $funcionarioExiste->unidade?->localidade?->setor;

        if ($setor && $setor->bloquear_ponto_sem_biometria) {
            return response()->json([
                'message' => 'O registro de ponto sem biometria está desabilitado para este setor.',
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
    // meia-noite sem "perder" o registro do dia anterior. Registros já
    // arquivados por um administrador (arquivado_em preenchido) não contam
    // como "em aberto" — foram encerrados de propósito sem apurar horas e
    // não devem voltar a bloquear o funcionário.
    $registroAberto = RegistroPonto::where('funcionario_id', $funcionarioId)
        ->whereNull('hora_saida')
        ->whereNull('arquivado_em')
        ->orderByDesc('id')
        ->first();

    if ($acao === 'entrada') {
        if ($registroAberto) {
            return response()->json([
                'message' => 'Já existe uma entrada em aberto sem saída registrada. Registre a saída antes de uma nova entrada.',
                'registro' => $registroAberto,
            ], 422);
        }

        $novoRegistro = RegistroPonto::create([
            'funcionario_id' => $funcionarioId,
            'hora_entrada' => Carbon::now(),
            'biometrico' => (bool) $biometria,
        ]);

        broadcast(new NovoRegistroPonto($novoRegistro, $setorId))->toOthers();

        return response()->json([
            'message' => 'Hora de entrada registrada com sucesso!',
            'registro' => $novoRegistro,
            'criado ' => $novoRegistro->created_at->timezone('America/Sao_Paulo')->format('Y-m-d H:i:s'),
        ], 200);
    }

    // $acao === 'saida'
    if (! $registroAberto) {
        return response()->json([
            'message' => 'Nenhuma entrada em aberto foi encontrada para registrar a saída.',
        ], 422);
    }

    $entradaCompleta = Carbon::parse($registroAberto->data_local . ' ' . $registroAberto->hora_entrada);
    $agora = Carbon::now();

    if ($entradaCompleta->diffInHours($agora) > self::MAX_HORAS_TURNO) {
        return response()->json([
            'message' => sprintf(
                'Já se passaram mais de %d horas desde a entrada registrada em %s. Por segurança, a saída não foi fechada automaticamente — peça a um administrador para corrigi-la em "Ponto > Pendências de Correção".',
                self::MAX_HORAS_TURNO,
                $entradaCompleta->timezone('America/Sao_Paulo')->format('d/m/Y H:i')
            ),
            'registro' => $registroAberto,
        ], 422);
    }

    $registroAberto->update(['hora_saida' => $agora]);

    broadcast(new NovoRegistroPonto($registroAberto, $setorId))->toOthers();

    return response()->json([
        'message' => 'Hora de saída registrada com sucesso!',
        'registro' => $registroAberto,
    ], 200);
   }

    /**
     * Lista registros "em aberto" (sem hora de saída) para que um
     * administrador possa revisar e corrigir manualmente — é para cá que a
     * mensagem de erro do limite de 30h direciona o usuário. Mostra
     * registros de qualquer dia (não só hoje), já que um turno travado pode
     * ter aberto há vários dias e por isso não apareceria na lista de
     * "registros do dia".
     *
     * Suporta paginação (`per_page`) e um filtro `dias_min` (só mostra
     * registros abertos há pelo menos N dias) — necessário porque o volume
     * de pendências acumuladas pode ser grande demais para carregar tudo
     * de uma vez.
     */
    public function pendentes(Request $request)
    {
        $this->garantirAdmin();

        $user    = auth()->user();
        $perPage = min((int) $request->input('per_page', 50), 200);
        $diasMin = $request->input('dias_min');

        $query = RegistroPonto::with('funcionario')
            ->whereNull('hora_saida')
            ->whereNull('arquivado_em');

        if (! $user->hasRole('super admin')) {
            $query->whereHas('funcionario.unidade.localidade', function ($q) use ($user) {
                $q->where('setor_id', $user->setor_id);
            });
        }

        if ($diasMin) {
            $limite = Carbon::now()->subDays((int) $diasMin)->toDateString();
            $query->where('data_local', '<=', $limite);
        }

        $paginado = $query->orderBy('data_local')->orderBy('hora_entrada')->paginate($perPage);

        return response()->json([
            'pendentes' => $paginado->getCollection()->map(function ($registro) {
                return [
                    'id'             => $registro->id,
                    'funcionario'    => $registro->funcionario?->nome,
                    'funcionario_id' => $registro->funcionario_id,
                    'data_local'     => $registro->data_local,
                    'hora_entrada'   => $registro->hora_entrada,
                    'hora_saida'     => $registro->hora_saida,
                    'horas_aberto'   => round(
                        Carbon::parse($registro->data_local . ' ' . $registro->hora_entrada)->diffInHours(Carbon::now()),
                        1
                    ),
                ];
            }),
            'meta' => [
                'total'        => $paginado->total(),
                'current_page' => $paginado->currentPage(),
                'last_page'    => $paginado->lastPage(),
                'per_page'     => $paginado->perPage(),
            ],
        ], 200);
    }

    /**
     * Arquiva em massa registros travados em aberto, sem inventar um
     * horário de saída — a hora_saida continua NULL de propósito (não é
     * contabilizada como horas trabalhadas), o registro só sai da fila de
     * pendências. Pensado para dar vazão a um grande volume de registros
     * antigos, de antes desta funcionalidade existir, onde não há como
     * saber com confiança o horário real de saída.
     *
     * Aceita dois modos, mutuamente exclusivos:
     * - "ids": arquiva exatamente os registros selecionados manualmente.
     * - "dias_min": arquiva TODOS os registros em aberto há pelo menos N
     *   dias (dentro do escopo do usuário), sem precisar carregar a lista
     *   inteira no front-end antes — essencial quando o volume é grande.
     *
     * Sempre exige um motivo, e cada registro afetado fica com uma linha
     * própria no histórico de auditoria.
     */
    public function arquivarEmMassa(Request $request)
    {
        $this->garantirAdmin();

        $user = auth()->user();

        $validated = $request->validate([
            'ids'      => 'nullable|array|min:1',
            'ids.*'    => 'integer|exists:registro_pontos,id',
            'dias_min' => 'nullable|integer|min:1',
            'motivo'   => 'required|string|min:10|max:1000',
        ]);

        if (empty($validated['ids']) && empty($validated['dias_min'])) {
            return response()->json([
                'message' => 'Selecione registros específicos ou informe um filtro de "dias em aberto" para arquivar em massa.',
            ], 422);
        }

        $query = RegistroPonto::whereNull('hora_saida')->whereNull('arquivado_em');

        if (! $user->hasRole('super admin')) {
            $query->whereHas('funcionario.unidade.localidade', function ($q) use ($user) {
                $q->where('setor_id', $user->setor_id);
            });
        }

        if (! empty($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        } else {
            $limite = Carbon::now()->subDays((int) $validated['dias_min'])->toDateString();
            $query->where('data_local', '<=', $limite);
        }

        $registros = $query->get();

        if ($registros->isEmpty()) {
            return response()->json([
                'message' => 'Nenhum registro encontrado para arquivar com esse filtro.',
            ], 422);
        }

        DB::transaction(function () use ($registros, $user, $validated) {
            foreach ($registros as $registro) {
                RegistroPontoEdicao::create([
                    'registro_ponto_id'     => $registro->id,
                    'tipo'                  => 'arquivamento',
                    'editado_por_id'        => $user->id,
                    'data_local_anterior'   => $registro->data_local,
                    'hora_entrada_anterior' => $registro->hora_entrada,
                    'hora_saida_anterior'   => $registro->hora_saida,
                    'motivo'                => $validated['motivo'],
                ]);

                $registro->update([
                    'arquivado_em'        => now(),
                    'arquivado_por_id'    => $user->id,
                    'motivo_arquivamento' => $validated['motivo'],
                ]);
            }
        });

        return response()->json([
            'message' => count($registros) . ' registro(s) arquivado(s) com sucesso.',
            'total'   => count($registros),
        ], 200);
    }

    /**
     * Corrige manualmente um registro de ponto (entrada e/ou saída).
     * Restrito a administradores e sempre exige um motivo, que fica salvo
     * junto com os valores antigos/novos em `registro_ponto_edicoes` —
     * a correção nunca é silenciosa, para que o histórico continue
     * confiável e auditável.
     */
    public function corrigir(Request $request, RegistroPonto $registro)
    {
        $this->garantirAdmin();

        $user = auth()->user();
        if (! $user->hasRole('super admin')) {
            $setorDoRegistro = $registro->funcionario?->unidade?->localidade?->setor_id;
            if ($setorDoRegistro !== $user->setor_id) {
                return response()->json(['message' => 'Você não tem permissão para corrigir este registro.'], 403);
            }
        }

        $validated = $request->validate([
            'data_local'   => 'required|date',
            'hora_entrada' => 'required|date_format:H:i',
            'data_saida'   => 'required_with:hora_saida|nullable|date|after_or_equal:data_local',
            'hora_saida'   => 'nullable|date_format:H:i',
            'motivo'       => 'required|string|min:10|max:1000',
        ]);

        $entradaNova = Carbon::parse($validated['data_local'] . ' ' . $validated['hora_entrada']);
        $saidaNova = null;

        if ($validated['hora_saida']) {
            // A data da saída é sempre explícita (nunca "adivinhada" a
            // partir do horário) — isso evita ambiguidade em plantões de
            // 24h exatas ou em qualquer turno que atravesse a meia-noite.
            $saidaNova = Carbon::parse($validated['data_saida'] . ' ' . $validated['hora_saida']);

            if ($saidaNova->lessThanOrEqualTo($entradaNova)) {
                return response()->json([
                    'message' => 'A saída precisa ser depois da entrada. Confira a data e a hora informadas.',
                ], 422);
            }

            if ($entradaNova->diffInHours($saidaNova) > self::MAX_HORAS_TURNO) {
                return response()->json([
                    'message' => sprintf(
                        'O intervalo informado ultrapassa %d horas. Revise os horários antes de salvar.',
                        self::MAX_HORAS_TURNO
                    ),
                ], 422);
            }
        }

        RegistroPontoEdicao::create([
            'registro_ponto_id'     => $registro->id,
            'editado_por_id'        => $user->id,
            'data_local_anterior'   => $registro->data_local,
            'hora_entrada_anterior' => $registro->hora_entrada,
            'hora_saida_anterior'   => $registro->hora_saida,
            'data_local_nova'       => $validated['data_local'],
            'hora_entrada_nova'     => $validated['hora_entrada'],
            'hora_saida_nova'       => $validated['hora_saida'] ?? null,
            'motivo'                => $validated['motivo'],
        ]);

        $registro->update([
            'data_local'   => $validated['data_local'],
            'hora_entrada' => $entradaNova,
            'hora_saida'   => $saidaNova,
        ]);

        return response()->json([
            'message'  => 'Registro de ponto corrigido com sucesso!',
            'registro' => $registro->fresh('edicoes.editadoPor'),
        ], 200);
    }

    private function garantirAdmin(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'super admin'])) {
            abort(response()->json([
                'message' => 'Apenas administradores podem corrigir registros de ponto.',
            ], 403));
        }
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
