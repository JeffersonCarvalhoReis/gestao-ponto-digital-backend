<?php

namespace App\Services;

use App\Models\DiaNaoUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class DiaNaoUtilService
{
    public function preencherFinaisDeSemana()
    {
        $periodo = Carbon::now()->startOfYear()->startOfWeek()->daysUntil(Carbon::now()->endOfYear()->endOfWeek());

        $finaisDeSemana = collect($periodo)->filter(function ($data) {
            return $data->isWeekend();
        })->map(function ($data) {
            return [
                'data' => $data->toDateString(),
                'tipo' => 'final_de_semana',
                'descricao' => $data->isSaturday() ? 'Sábado' : 'Domingo',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        foreach ($finaisDeSemana as $finalDeSemana) {
            DiaNaoUtil::updateOrCreate(
                ['data' => $finalDeSemana['data'],'tipo' => $finalDeSemana['tipo'],
            ],
                $finalDeSemana
            );
        }
    }

    public function preencherFeriados()
    {
        $ano = Carbon::now()->year;

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
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
