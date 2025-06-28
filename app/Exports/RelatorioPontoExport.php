<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\RelatorioConsolidadoSheet;


class RelatorioPontoExport implements WithMultipleSheets
{
    protected $dados;
    protected $dadosSemanais;
    protected $ano;
    protected $mes;
    protected $unidade;

    public function __construct($dados, $dadosSemanais, $ano, $mes, $unidade) {
        $this->dados = $dados;
        $this->dadosSemanais = $dadosSemanais;
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
            new RelatorioHorasTrabalhdasSemanaisSheet($this->dadosSemanais, $this->ano, $this->mes, $this->unidade)
        ];
    }
}
