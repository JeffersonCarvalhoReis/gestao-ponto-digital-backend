<?php

namespace App\Exports;

use DateTime;
use IntlDateFormatter;
use Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioHorasTrabalhdasSemanaisSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithEvents, WithTitle
{
    protected $dadosSemanais;
    protected $ano;
    protected $mes;
    protected $unidade;

    public function __construct($dadosSemanais, $ano, $mes, $unidade)
    {
        $this->dadosSemanais = $dadosSemanais;
        $this->ano = $ano;
        $this->mes = $mes;
        $this->unidade = $unidade;
    }

    public function array(): array
    {
        $larguraTabela =  count($this->dadosSemanais->first());
        $cabecalho = ['Semana'];
        for ($i=1; $i < $larguraTabela ; $i++) {
            $cabecalho[] = $i . 'ª';
        }
        $dadosSemanaisFormatados[] = $this->dadosSemanais;

        array_unshift($dadosSemanaisFormatados, $cabecalho);
        return $dadosSemanaisFormatados;
    }

    public function headings(): array {
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            IntlDateFormatter::GREGORIAN,
            "MMMM 'de' Y" // 'MMMM' para o nome completo do mês, 'Y' para o ano
        );

        $data = new DateTime("$this->ano-$this->mes-01"); // Criar a data no formato correto

        return [
            ['Relatório de Horas Trabalhas Por Semana no Mês'],
            [],
            ["Unidade: $this->unidade"], // Unidade está fixo ou você pode dinamicamente substituir esse valor
            ['Data: ' . ucfirst($formatter->format($data))], // A data será dinâmica
            [' '],
        ];
     }
     public function styles(Worksheet $sheet){
        $totalColunas = count($this->array()[0]);

        // Mesclar a primeira linha (cabeçalho) de A1 até a última coluna da tabela
        $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColunas) . '1');

     }

     public function registerEvents(): array
     {
         return [
             AfterSheet::class => function (AfterSheet $event) {
                 // Defina o estilo para o cabeçalho depois da exportação
                 $sheet = $event->sheet;
                 $sheet->getStyle('A1')->getFont()->setBold(true);
                 $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFF');
                 $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                     ->getStartColor()->setARGB('0070C0');
                 $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                     ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                     // Calcular a quantidade de colunas e o número de linhas com dados
                 $totalColunas = count($this->array()[0]);
                 $totalLinhas = count($this->array()[1]);


                 $totalLinhasDados = $totalLinhas + 6;

                 for ($linha = 6; $linha <= $totalLinhasDados; $linha++) {
                    if ($linha % 2 == 0) { // Linhas pares
                        $event->sheet->getStyle("A{$linha}:" . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColunas) . "{$linha}")
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB('D9E1F2'); // Cor azul clara
                    }
                }


                foreach (['A3', 'A4'] as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(12);
                }


                $sheet->getStyle('A6:A' . ($totalLinhasDados))
                    ->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $sheet->getStyle('A6:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColunas) . '6')->getFont()->setBold(true);
                $sheet->getStyle('A6:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColunas) . '6')->getFont()->setSize(12);

                // Aplicar a formatação de tabela nas outras colunas (da B em diante)
                $sheet->getStyle('B6:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColunas) . ($totalLinhasDados))
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    }];
        }

    public function title(): string
        {
            return "Horas Semanais $this->unidade $this->mes/$this->ano";
        }

}
