<?php
namespace Database\Seeders;

use App\Models\Setor;
use App\Models\TipoTurno;
use Illuminate\Database\Seeder;

class TiposTurnoSeeder extends Seeder
{
    /**
     * Semeia os códigos de turno identificados nas escalas de agosto/2026
     * (enfermagem, agente de portaria, motoristas, higienização, lavanderia).
     *
     * IMPORTANTE: o código "SD" tem significado diferente entre enfermagem
     * (ACCR 12h), higienização (diurno 07h-15h) e lavanderia (diurno
     * 07h-19h, 40h semanais). O modelo atual só permite escopar um tipo de
     * turno por Setor (unidade organizacional ampla, ex: "Hospital Municipal
     * Amélia de Carvalho"), não por equipe/departamento dentro do hospital.
     * Por isso, aqui SD foi semeado apenas no nível genérico com a duração
     * de 12h como referência — antes de usar em produção, confirmar com a
     * coordenação se cada equipe deveria ter seu próprio código (ex:
     * "SD-ENF", "SD-HIG", "SD-LAV") para não gerar cálculo de horas errado.
     */
    public function run(): void
    {
        $setorHospital = Setor::where('nome', 'like', '%Hospital%')->first();

        $turnos = [
            [
                'codigo'              => 'P',
                'nome'                => 'Plantão 24h (07h às 07h)',
                'setor_id'            => 1,
                'hora_inicio'         => '07:00',
                'hora_fim'            => '07:00',
                'duracao_minutos'     => 24 * 60,
                'conta_como_trabalho' => true,
                'e_hora_extra'        => false,
                'cor'                 => '#8FBC8F',
            ],
            [
                'codigo'              => 'MT',
                'nome'                => '12h (07h às 19h)',
                'setor_id'            => 1,
                'hora_inicio'         => '07:00',
                'hora_fim'            => '19:00',
                'duracao_minutos'     => 12 * 60,
                'conta_como_trabalho' => true,
                'e_hora_extra'        => false,
                'cor'                 => '#87CEEB',
            ],
            [
                'codigo'              => 'SD',
                'nome'                => 'ACCR 12h (verificar duração exata por equipe)',
                'setor_id'            => 1,
                'hora_inicio'         => null,
                'hora_fim'            => null,
                'duracao_minutos'     => 12 * 60,
                'conta_como_trabalho' => true,
                'e_hora_extra'        => false,
                'cor'                 => '#FFD700',
            ],
            [
                'codigo'              => 'M',
                'nome'                => 'Manhã parcial (ex: 08:00 às 10:00)',
                'setor_id'            => 1,
                'hora_inicio'         => '08:00',
                'hora_fim'            => '10:00',
                'duracao_minutos'     => 2 * 60,
                'conta_como_trabalho' => true,
                'e_hora_extra'        => false,
                'cor'                 => '#DDA0DD',
            ],
            [
                'codigo'              => 'F',
                'nome'                => 'Folga',
                'setor_id'            => 1,
                'hora_inicio'         => null,
                'hora_fim'            => null,
                'duracao_minutos'     => null,
                'conta_como_trabalho' => false,
                'e_hora_extra'        => false,
                'cor'                 => '#D3D3D3',
            ],
            [
                'codigo'              => 'AL',
                'nome'                => 'Área Limpa (rotação de setor, não é turno)',
                'setor_id'            => 1,
                'hora_inicio'         => null,
                'hora_fim'            => null,
                'duracao_minutos'     => null,
                'conta_como_trabalho' => false,
                'e_hora_extra'        => false,
                'cor'                 => '#90EE90',
            ],
            [
                'codigo'              => 'AS',
                'nome'                => 'Área Suja (rotação de setor, não é turno)',
                'setor_id'            => 1,
                'hora_inicio'         => null,
                'hora_fim'            => null,
                'duracao_minutos'     => null,
                'conta_como_trabalho' => false,
                'e_hora_extra'        => false,
                'cor'                 => '#F4A460',
            ],
            [
                'codigo'              => 'DOBRA',
                'nome'                => 'Dobra de plantão (hora extra)',
                'setor_id'            => 1,
                'hora_inicio'         => null,
                'hora_fim'            => null,
                'duracao_minutos'     => 24 * 60,
                'conta_como_trabalho' => true,
                'e_hora_extra'        => true,
                'cor'                 => '#90EE90',
            ],
            [
                'codigo'              => 'VIAGEM',
                'nome'                => 'Dobra em viagem (hora extra)',
                'setor_id'            => 1,
                'hora_inicio'         => null,
                'hora_fim'            => null,
                'duracao_minutos'     => 24 * 60,
                'conta_como_trabalho' => true,
                'e_hora_extra'        => true,
                'cor'                 => '#87CEFA',
            ],
        ];

        foreach ($turnos as $turno) {
            TipoTurno::updateOrCreate(
                ['codigo' => $turno['codigo'], 'setor_id' => $turno['setor_id']],
                $turno
            );
        }
    }
}
