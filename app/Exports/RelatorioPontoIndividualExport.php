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
    protected ?array $bancoHoras;

    public function __construct(array $dados)
    {
        $this->dados = $dados;
        // Opcional: quando o front-end envia 'banco_horas' junto (mesmo
        // formato retornado por /relatorio para o funcionário), a aba
        // individual também mostra o resumo do mês (previsto/realizado/
        // extra/saldo). Se não vier, a exportação segue igual a antes.
        $this->bancoHoras = $dados['banco_horas'] ?? null;
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

                $this->escreverBlocoBancoHoras($sheet, $lastRow);
            },
        ];
    }

    /**
     * Escreve, logo abaixo da tabela de dias, um pequeno bloco com o resumo
     * do banco de horas do mês (previsto x realizado x extra x saldo).
     * Só é escrito se o front-end enviou 'banco_horas' no payload.
     */
    private function escreverBlocoBancoHoras($sheet, int $lastRow): void
    {
        if (! $this->bancoHoras) {
            return;
        }

        $linhaTitulo = $lastRow + 2;
        $linhaCabecalho = $linhaTitulo + 1;
        $linhaValores = $linhaCabecalho + 1;

        $sheet->mergeCells("A{$linhaTitulo}:H{$linhaTitulo}");
        $sheet->setCellValue("A{$linhaTitulo}", 'Banco de Horas do Mês (o saldo não acumula para o mês seguinte)');
        $sheet->getStyle("A{$linhaTitulo}")->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue("A{$linhaCabecalho}", 'Previsto (Escala)');
        $sheet->setCellValue("C{$linhaCabecalho}", 'Realizado (Ponto)');
        $sheet->setCellValue("E{$linhaCabecalho}", 'Extra (Dobra/Viagem)');
        $sheet->setCellValue("G{$linhaCabecalho}", 'Saldo do Mês');

        $sheet->setCellValue("A{$linhaValores}", $this->bancoHoras['previsto_formatado'] ?? '00:00');
        $sheet->setCellValue("C{$linhaValores}", $this->bancoHoras['realizado_formatado'] ?? '00:00');
        $sheet->setCellValue("E{$linhaValores}", $this->bancoHoras['extra_formatado'] ?? '00:00');
        $sheet->setCellValue("G{$linhaValores}", $this->bancoHoras['saldo_formatado'] ?? '00:00');

        foreach (['A', 'C', 'E', 'G'] as $coluna) {
            $sheet->getStyle("{$coluna}{$linhaCabecalho}")->getFont()->setBold(true);
        }

        $rangeBloco = "A{$linhaCabecalho}:H{$linhaValores}";
        $sheet->getStyle($rangeBloco)->applyFromArray([
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

        $saldoFormatado = $this->bancoHoras['saldo_formatado'] ?? '00:00';
        if (str_starts_with($saldoFormatado, '-')) {
            $sheet->getStyle("G{$linhaValores}")->getFont()->getColor()->setARGB('CC0000');
            $sheet->getStyle("G{$linhaValores}")->getFont()->setBold(true);
        }
    }
}

