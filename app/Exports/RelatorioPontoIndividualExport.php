<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class RelatorioPontoIndividualExport implements FromArray, WithHeadings, WithTitle, WithStyles, WithEvents
{
    protected array $dados;

    public function __construct(array $dados)
    {
        $this->dados = $dados;
    }

    public function array(): array
    {
        $dadosOrganizados =  array_map(function ($item) {
            return [
                Carbon::parse($item['data'])->format('d/m/Y') ,
                $item['status'] ?? '',
                implode("\n", $item['entrada']),
                implode("\n", $item['saida']),
                $item['horas_trabalhadas'],
                $item['justificativa'] ?? '',
                $item['justificativa_status'] ?? '',
                $item['descricao_dia_nao_util'] ?? '',
            ];
        }, $this->dados['registros']);

        array_unshift($dadosOrganizados, []);

        setlocale(LC_TIME, 'pt_BR.UTF-8');
        Carbon::setLocale('pt_BR');

        return $dadosOrganizados;
    }

    public function headings(): array
    {
        $dataFormatada = Carbon::parse($this->dados['registros'][0]['data'])->translatedFormat('F \d\e Y'); // Ex: "Junho de 2025"
        $dataFormatada = ucfirst($dataFormatada);
        return [
            ['Relátorio Individual'],
            ['Funcionário: '. $this->dados['nome']],
            ['Mês: '. $dataFormatada],
            [' '],
            [
                'Data',
                'Status',
                'Entradas',
                'Saídas',
                'Horas Trabalhadas',
                'Justificativa',
                'Status Justificativa',
                'Descrição Dia Não Útil',
            ],
        ];
    }

    public function title(): string
    {
        return 'Relatório de Ponto';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->dados['registros']) + 5;

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:D2');
                $sheet->mergeCells('E2:H2');
                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells('C3:H3');
                $sheet->mergeCells('A4:H4');

                $sheet->getStyle("C5:D{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle('A5:H5')->getAlignment()->setWrapText(true);
                $sheet->getStyle('A5:H5')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getFont()->setSize(13);
                $sheet->getStyle('A3')->getFont()->setBold(true);
                $sheet->getStyle("A6:H{$lastRow}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'color' => ['argb' => 'FFFFFF'],
                        'size' => 14,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => '0070C0'],
                    ],
                ]);

                $sheet->getStyle('A5:H' . ($lastRow))
                ->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Ajusta largura automática
                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                foreach (range('F', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(25);
                }
                $sheet->getStyle("F5:H{$lastRow}")->getAlignment()->setWrapText(true);

                // Aplica cores por status
                for ($row = 6; $row <= $lastRow; $row++) {
                    $status = $sheet->getCell("B{$row}")->getValue();
                    $color = match ($status) {
                        'Presente' => 'C6EFCE', // Verde
                        'Falta' => 'FFC7CE', // Vermelho
                        'Final de Semana' => 'BDD7EE', // Azul claro
                        default => 'F0E68C',
                    };

                    if ($color) {
                        $sheet->getStyle("B{$row}")->getFill()->setFillType('solid')->getStartColor()->setRGB($color);
                    }
                }

                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.25);
                $sheet->getPageMargins()->setRight(0.2);
                $sheet->getPageMargins()->setLeft(0.2);
                $sheet->getPageMargins()->setBottom(0.25);
            },
        ];
    }
}

