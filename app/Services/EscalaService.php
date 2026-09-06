<?php
namespace App\Services;

use App\Models\Escala;
use App\Models\Funcionario;
use App\Models\TipoTurno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EscalaService
{
    /**
     * Monta a grade do mês para um setor: cada funcionário do setor com o
     * código de turno cadastrado em cada dia do mês (ou null quando o dia
     * ainda não foi preenchido).
     *
     * Quando $unidadeId é informado, a grade é restrita aos funcionários
     * daquela unidade (dentro do mesmo setor); quando omitido, traz todas
     * as unidades do setor.
     */
    public function grade(int $setorId, string $mesReferencia, ?int $unidadeId = null): array
    {
        $inicio = Carbon::parse($mesReferencia)->startOfMonth();
        $fim    = Carbon::parse($mesReferencia)->endOfMonth();

        $funcionarios = Funcionario::doSetor($setorId)
            ->when($unidadeId, function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
            })
            ->where('status', true)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $escalas = Escala::with('tipoTurno')
            ->whereIn('funcionario_id', $funcionarios->pluck('id'))
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->get()
            ->groupBy('funcionario_id');

        $grade = [];

        foreach ($funcionarios as $funcionario) {
            $diasFuncionario = $escalas->get($funcionario->id, collect())
                ->keyBy(fn($escala) => $escala->data->toDateString());

            $dias = [];
            foreach ($inicio->daysUntil($fim->copy()->addDay()) as $dia) {
                $escalaDia                  = $diasFuncionario->get($dia->toDateString());
                $dias[$dia->toDateString()] = $escalaDia ? [
                    'escala_id'     => $escalaDia->id,
                    'tipo_turno_id' => $escalaDia->tipo_turno_id,
                    'codigo'        => $escalaDia->tipoTurno->codigo,
                    'cor'           => $escalaDia->tipoTurno->cor,
                    'observacao'    => $escalaDia->observacao,
                ] : null;
            }

            $grade[] = [
                'funcionario_id' => $funcionario->id,
                'nome'           => $funcionario->nome,
                'dias'           => $dias,
            ];
        }

        return [
            'setor_id'       => $setorId,
            'unidade_id'     => $unidadeId,
            'mes_referencia' => $inicio->toDateString(),
            'funcionarios'   => $grade,
        ];
    }

    /**
     * Cria ou atualiza o turno de um funcionário em um dia específico.
     */
    public function definirDia(int $funcionarioId, string $data, string $codigoTurno, ?int $setorId, ?string $observacao, int $criadoPor): Escala
    {
        $tipoTurno = TipoTurno::resolverPorCodigo($codigoTurno, $setorId);

        if (! $tipoTurno) {
            throw new \InvalidArgumentException("Código de turno '{$tipoTurno}' não encontrado para este setor.");
        }

        return Escala::updateOrCreate(
            ['funcionario_id' => $funcionarioId, 'data' => $data],
            [
                'tipo_turno_id' => $tipoTurno->id,
                'observacao'    => $observacao,
                'criado_por'    => $criadoPor,
            ]
        );
    }

    /**
     * Preenche em lote vários dias de uma vez (usado pela tela de grade,
     * que envia várias células alteradas em uma única chamada).
     *
     * @param array $lancamentos [['funcionario_id' => 1, 'data' => '2026-08-01', 'codigo' => 'P', 'observacao' => null], ...]
     */
    public function definirDiasEmLote(array $lancamentos, ?int $setorId, int $criadoPor): array
    {
        $resultados = [];

        DB::transaction(function () use ($lancamentos, $setorId, $criadoPor, &$resultados) {
            foreach ($lancamentos as $lancamento) {
                $resultados[] = $this->definirDia(
                    $lancamento['funcionario_id'],
                    $lancamento['data'],
                    $lancamento['codigo'],
                    $setorId,
                    $lancamento['observacao'] ?? null,
                    $criadoPor
                );
            }
        });

        return $resultados;
    }

    /**
     * Remove o turno de um funcionário em um dia (célula fica vazia).
     */
    public function removerDia(int $funcionarioId, string $data): void
    {
        Escala::where('funcionario_id', $funcionarioId)
            ->where('data', $data)
            ->delete();
    }

    /**
     * Copia toda a grade de um mês para outro, mantendo o mesmo padrão por
     * funcionário. Útil porque a coordenação confirmou que a sequência de
     * cada pessoa se repete mês a mês.
     */
    public function copiarMes(int $setorId, string $mesOrigem, string $mesDestino, int $criadoPor): int
    {
        $inicioOrigem  = Carbon::parse($mesOrigem)->startOfMonth();
        $fimOrigem     = Carbon::parse($mesOrigem)->endOfMonth();
        $inicioDestino = Carbon::parse($mesDestino)->startOfMonth();

        $funcionarios = Funcionario::doSetor($setorId)->pluck('id');

        $escalasOrigem = Escala::whereIn('funcionario_id', $funcionarios)
            ->whereBetween('data', [$inicioOrigem->toDateString(), $fimOrigem->toDateString()])
            ->get();

        $totalCopiado = 0;

        DB::transaction(function () use ($escalasOrigem, $inicioOrigem, $inicioDestino, $criadoPor, &$totalCopiado) {
            foreach ($escalasOrigem as $escalaOrigem) {
                $offsetDias = $inicioOrigem->diffInDays($escalaOrigem->data);
                $novaData   = $inicioDestino->copy()->addDays($offsetDias);

                Escala::updateOrCreate(
                    ['funcionario_id' => $escalaOrigem->funcionario_id, 'data' => $novaData->toDateString()],
                    [
                        'tipo_turno_id' => $escalaOrigem->tipo_turno_id,
                        'observacao'    => $escalaOrigem->observacao,
                        'criado_por'    => $criadoPor,
                    ]
                );

                $totalCopiado++;
            }
        });

        return $totalCopiado;
    }
}
