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
        Log::info($user->setor_id);

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

        $resposta = Http::get("https://brasilapi.com.br/api/feriados/v1/$ano");

        if (!$resposta->successful()) {
            throw new \Exception('Erro ao buscar feriados.');
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
