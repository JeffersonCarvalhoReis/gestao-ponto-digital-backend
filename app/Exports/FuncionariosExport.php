<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FuncionariosExport implements FromCollection, ShouldAutoSize, WithMapping, WithHeadings, WithStyles
{
    private Collection $funcionariosExport;

    public function __construct($funcionariosExport) {
        // Garante que os dados sejam uma coleção válida
        $this->funcionariosExport = collect($funcionariosExport);
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->funcionariosExport;
    }
    public function map($funcionario): array {
        return [
            $funcionario->nome,
            $funcionario->cargo->nome ?? 'Sem Cargo',
            $funcionario->unidade->nome,
            $funcionario->dadosContrato->vinculo ?? '',
            $funcionario->status ? 'Ativo' : 'Inativo',
        ];
    }
    public function headings(): array {
        return [
            'Nome',
            'Cargo',
            'Unidade',
            'Vinculo',
            'Status'
        ];
    }
    public function styles(Worksheet $sheet) {
        $totalColunas = count($this->headings());
        $totalLinhas = $this->funcionariosExport->count() + 1;

        $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('0070C0');
        $sheet->getStyle('A1:E1')->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFont()->setSize(12);
        $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColunas) . ($totalLinhas))
        ->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $totalLinhasSemCabecalho = $this->funcionariosExport->count();
        for ($linha = 1; $linha <= $totalLinhasSemCabecalho; $linha++) {
            if ($linha % 2 == 0) { // Linhas pares
                     $sheet->getStyle("A{$linha}:" . "E" . "{$linha}")
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('D9E1F2'); // Cor azul clara
            }
        }
        $linhaTotal = $totalLinhas + 2;

        $sheet->setCellValue("A{$linhaTotal}", "Total de Funcionários: {$totalLinhasSemCabecalho}");
        $sheet->getStyle("A{$linhaTotal}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('0070C0');
        $sheet->getStyle("A{$linhaTotal}")->getFont()->getColor()->setARGB('FFFFFF');
        $sheet->getStyle("A{$linhaTotal}")->getFont()->setBold(true);
        $sheet->getStyle("A{$linhaTotal}")->getFont()->setSize(12);
    }
}

