<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\RelatorioConsolidadoSheet;
use App\Exports\RelatorioBancoHorasSheet;


class RelatorioPontoExport implements WithMultipleSheets
{
    protected $dados;
    protected $dadosSemanais;
    protected $dadosBancoHoras;
    protected $ano;
    protected $mes;
    protected $unidade;

    public function __construct($dados, $dadosSemanais, $dadosBancoHoras, $ano, $mes, $unidade) {
        $this->dados = $dados;
        $this->dadosSemanais = $dadosSemanais;
        $this->dadosBancoHoras = $dadosBancoHoras;
        $this->ano = $ano;
        $this->mes = $mes;
        $this->unidade = $unidade;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function sheets(): array
    {
        return [
            new RelatorioConsolidadoSheet($this->dados, $this->ano, $this->mes, $this->unidade, true ),
            new RelatorioConsolidadoSheet($this->dados, $this->ano, $this->mes, $this->unidade ),
            new RelatorioHorasTrabalhdasSemanaisSheet($this->dadosSemanais, $this->ano, $this->mes, $this->unidade),
            new RelatorioBancoHorasSheet($this->dadosBancoHoras, $this->ano, $this->mes, $this->unidade),
        ];
    }
}
