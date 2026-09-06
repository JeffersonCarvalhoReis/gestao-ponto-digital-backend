<?php

namespace App\Exports;

use DateTime;
use IntlDateFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioBancoHorasSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithEvents, WithTitle
{
    protected $dadosBancoHoras;
    protected $ano;
    protected $mes;
    protected $unidade;

    public function __construct($dadosBancoHoras, $ano, $mes, $unidade)
    {
        $this->dadosBancoHoras = $dadosBancoHoras;
        $this->ano = $ano;
        $this->mes = $mes;
        $this->unidade = $unidade;
    }

    public function array(): array
    {
        $cabecalho = ['Funcionário', 'Previsto (Escala)', 'Realizado (Ponto)', 'Extra (Dobra/Viagem)', 'Saldo do Mês'];

        $linhas = $this->dadosBancoHoras->map(function ($linha) {
            return [
                $linha['funcionario'],
                $linha['previsto'],
                $linha['realizado'],
                $linha['extra'],
                $linha['saldo'],
            ];
        })->toArray();

        array_unshift($linhas, $cabecalho);

        return $linhas;
    }

    public function headings(): array
    {
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            IntlDateFormatter::GREGORIAN,
            "MMMM 'de' Y"
        );

        $data = new DateTime("$this->ano-$this->mes-01");

        return [
            ['Banco de Horas do Mês'],
            [],
            ["Unidade: $this->unidade"],
            ['Data: '.ucfirst($formatter->format($data))],
            ['Obs: saldo não acumula para o mês seguinte — fechamento mensal.'],
            [' '],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $totalColunas = count($this->array()[0]);

        $sheet->mergeCells('A1:'.Coordinate::stringFromColumnIndex($totalColunas).'1');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $totalColunas = count($this->array()[0]);
                $totalLinhasDados = count($this->array());

                $linhaCabecalhoTabela = 7; // depois das 6 linhas de headings()
                $ultimaLinha = $linhaCabecalhoTabela + $totalLinhasDados - 1;

                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFF');
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('0070C0');
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                foreach (['A3', 'A4'] as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(12);
                }

                $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(10);

                $rangeCabecalhoTabela = 'A'.$linhaCabecalhoTabela.':'.Coordinate::stringFromColumnIndex($totalColunas).$linhaCabecalhoTabela;
                $sheet->getStyle($rangeCabecalhoTabela)->getFont()->setBold(true);
                $sheet->getStyle($rangeCabecalhoTabela)->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D9E1F2');

                $rangeTabela = 'A'.$linhaCabecalhoTabela.':'.Coordinate::stringFromColumnIndex($totalColunas).$ultimaLinha;
                $sheet->getStyle($rangeTabela)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Destaca em vermelho o saldo negativo do mês (coluna E).
                for ($linha = $linhaCabecalhoTabela + 1; $linha <= $ultimaLinha; $linha++) {
                    $valorSaldo = $sheet->getCell("E{$linha}")->getValue();
                    if (is_string($valorSaldo) && str_starts_with($valorSaldo, '-')) {
                        $sheet->getStyle("E{$linha}")->getFont()->getColor()->setARGB('CC0000');
                        $sheet->getStyle("E{$linha}")->getFont()->setBold(true);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return "Banco de Horas $this->mes-$this->ano";
    }
}
