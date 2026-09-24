<?php

namespace App\Services;

use App\Models\DiaNaoUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Log;

class DiaNaoUtilService
{
    public function preencherFinaisDeSemana()
    {
        $user = auth()->user();

        $periodo = Carbon::now()->startOfYear()->startOfWeek()->daysUntil(Carbon::now()->endOfYear()->endOfWeek());

        $finaisDeSemana = collect($periodo)->filter(function ($data) {
            return $data->isWeekend();
        })->map(function ($data) use ($user) {
            return [
                'data' => $data->toDateString(),
                'tipo' => 'final_de_semana',
                'descricao' => $data->isSaturday() ? 'Sábado' : 'Domingo',
                'setor_id' => $user->setor_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        foreach ($finaisDeSemana as $finalDeSemana) {
            DiaNaoUtil::updateOrCreate(
                ['data' => $finalDeSemana['data'],'tipo' => $finalDeSemana['tipo'], 'setor_id' => $user->setor_id
            ],
                $finalDeSemana
            );
        }
    }

    public function preencherFeriados()
    {
        $ano = Carbon::now()->year;
        $user = auth()->user();

        // Se os feriados desse ano já foram cadastrados antes, não precisa
        // bater na API externa de novo a cada geração de relatório.
        $jaTemFeriados = DiaNaoUtil::where('tipo', 'feriado')
            ->whereYear('data', $ano)
            ->exists();

        if ($jaTemFeriados) {
            return;
        }

        try {
            $resposta = Http::timeout(5)->get("https://brasilapi.com.br/api/feriados/v1/$ano");

            if (!$resposta->successful()) {
                Log::warning("BrasilAPI retornou status {$resposta->status()} ao buscar feriados de {$ano}.");
                return;
            }
        } catch (\Throwable $e) {
            // Instabilidade/timeout na API externa não deve travar a geração do relatório.
            Log::warning("Falha ao buscar feriados de {$ano}: {$e->getMessage()}");
            return;
        }

        $feriados = $resposta->json();

        foreach ($feriados as $feriado) {
            DiaNaoUtil::updateOrCreate(
                [
                    'data' => $feriado['date'],
                    'tipo' => 'feriado',
                ],
                [
                    'descricao' => $feriado['name'],
                    'setor_id' => $user->setor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
