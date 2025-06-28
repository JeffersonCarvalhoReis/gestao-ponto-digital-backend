<?php

namespace App\Exports;

use Log;
use \PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use DateTime;
use IntlDateFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class RelatorioConsolidadoSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithHeadings, WithEvents
{
    protected $dados;
    protected $ano;
    protected $mes;
    protected $unidade;
    protected $horasPorDia;
    protected $comentariosPorCelula = [];

    public function __construct($dados, $ano, $mes, $unidade, $horasPorDia = false)
    {
        $this->dados = $dados;
        $this->ano = $ano;
        $this->mes = $mes;
        $this->unidade = $unidade;
        $this->horasPorDia = $horasPorDia;
    }

    public function array(): array
    {
        setlocale(LC_TIME, 'pt_BR.UTF-8');
        $diasDoMes = $this->verificaQuaisOsDiasDoMes();
        sort($diasDoMes);

        $cabecalho = ['Funcionário'];
        $diasCabecalho = $this->diaDoMesNomeDoDia($diasDoMes);
        $cabecalho = array_merge($cabecalho, $diasCabecalho);
        $cabecalho[] = 'Faltas';

        if($this->horasPorDia) {
            $dadosAgrupados = $this->preencheConteudoComEntradaESaida($diasDoMes );

        } else {
            $dadosAgrupados = $this->preencheConteudoComStatus($diasDoMes );
        }

        $dadosOrganizados = $this->organizaDados($dadosAgrupados,  $diasDoMes);

        array_unshift($dadosOrganizados, $cabecalho);
        return $dadosOrganizados;
    }

    private function verificaQuaisOsDiasDoMes()
    {

        $diasDoMes = [];

        foreach ($this->dados as $registro) {
            if(isset($registro['data'])) {
                $dia = date('d', strtotime($registro['data']));
                if (!in_array($dia, $diasDoMes)) {
                    $diasDoMes[] = $dia;
                }
            }
        }

        return $diasDoMes;
    }

    private function diaDoMesNomeDoDia($diasDoMes)
    {
        $resultado = [];

        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            IntlDateFormatter::GREGORIAN,
            'E' // 'E' para abreviação do dia da semana, 'EEEE' para o nome completo
        );

        foreach ($diasDoMes as $dia) {
            $resultado [] = $dia . "\n" . $formatter->format((new DateTime("$this->ano-$this->mes-$dia"))->getTimestamp());
        }
        return $resultado;
    }

    private function preencheConteudoComStatus($diasDoMes)
    {
         $dadosAgrupados = [];

        foreach ($this->dados as $registro) {
            if(isset($registro['funcionario'])) {
                $func = $registro['funcionario'];
                $dia = date('d', strtotime($registro['data']));
                $sigla = $registro['sigla_status'];

                if (!isset($dadosAgrupados[$func])) {
                    $dadosAgrupados[$func] = array_fill_keys($diasDoMes, '');
                    $dadosAgrupados[$func]['Faltas'] = '0';
                }
                $dadosAgrupados[$func][$dia] = $sigla;
                if($sigla == 'F') {
                    $dadosAgrupados[$func]['Faltas']++;
                }
            }
        }
        return $dadosAgrupados;
    }

    private function preencheConteudoComEntradaESaida($diasDoMes)
    {
        $dadosAgrupados = [];

        foreach ($this->dados as $registro) {
            if(isset($registro['funcionario'])) {
                $func = $registro['funcionario'];
                $dia = date('d', strtotime($registro['data']));
                $sigla = $registro['sigla_status'];
                $entradas = $registro['entrada'];
                $saidas = $registro['saida'];

                if (!isset($dadosAgrupados[$func])) {
                    $dadosAgrupados[$func] = array_fill_keys($diasDoMes, '');
                    $dadosAgrupados[$func]['Faltas'] = '0';
                }

                $max = max(count($entradas), count($saidas));
                for ($i = 0; $i < $max; $i++) {
                    $entrada = isset($entradas[$i]) ? $entradas[$i] : '';
                    $saida = isset($saidas[$i]) ? $saidas[$i] : '';
                    $linha = trim($entrada . ' | ' . $saida);

                    $i == ($max - 1) ? $dadosAgrupados[$func][$dia] .= $linha : $dadosAgrupados[$func][$dia] .= $linha . "\n";
                }
                if(!$registro['entrada']) $dadosAgrupados[$func][$dia] = $this->traduzSiglaParaNomeDoStatus($sigla);

                if (!empty($registro['descricao_dia_nao_util'])) {
                    $this->comentariosPorCelula[$func][$dia] = $registro['descricao_dia_nao_util'];
                }
                if (!empty($registro['justificativa'])) {
                    $this->comentariosPorCelula[$func][$dia] = $registro['justificativa'];
                }

                if($sigla == 'F') {
                    $dadosAgrupados[$func]['Faltas']++;
                }
            }
        }
        return $dadosAgrupados;
    }

    private function organizaDados($dadosAgrupados, $diasDoMes)
    {
         $dadosOrganizados = [];

        foreach ($dadosAgrupados as $funcionario => $dias) {
            $linha = [$funcionario];
            foreach ($diasDoMes as $dia) {
                $linha[] = $dias[$dia];
            }
            $linha[] = $dias['Faltas'];
            $dadosOrganizados[] = $linha;
        }

        return $dadosOrganizados;
    }

    private function traduzSiglaParaNomeDoStatus($value)
    {
        $statusMap = [
            'F' => 'Falta',
            'FE' => 'Férias',
            'FR' => 'Feriado',
            'J' => 'Justificado',
            'PE' => "Justificativa \nPendente",
            'R' => 'Recesso',
            'FS' => "Final de \nSemana",
            'L' => "Licença"
        ];
        return isset($statusMap[$value]) ? $statusMap[$value] : '-';
    }
    public function styles(Worksheet $sheet)
    {
        $totalColunas = count($this->array()[0]);

        // Mesclar a primeira linha (cabeçalho) de A1 até a última coluna da tabela
        $sheet->mergeCells('A1:' . Coordinate::stringFromColumnIndex($totalColunas) . '1');

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        // Habilitar quebra de linha (wrap text) em todas as células
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                // Aplica a formatação para quebra de linha (wrap text) utilizando o novo método
                $sheet->getStyle($cellAddress)->getAlignment()->setWrapText(true);
            }
        }

        // Aplicar estilos conforme os valores das células
        for ($row = 7; $row <= $highestRow; $row++) {
            for ($col = 2; $col <= $highestColumnIndex; $col++) {
                  // Converte a posição numérica para a célula correspondente
                $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                // Obtém o valor da célula
                $value = $sheet->getCell($cellAddress)->getValue();
                $style = $sheet->getStyle($cellAddress);

                $funcionario = $sheet->getCell('A' . $row)->getValue();
                $cabecalho = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '6')->getValue();
                $dia = explode("\n", $cabecalho)[0];

                // Verifica se tem comentário
                if (isset($this->comentariosPorCelula[$funcionario][$dia])) {
                    $comentario = $this->comentariosPorCelula[$funcionario][$dia];
                    $sheet->getComment($cellAddress)
                        ->getText()->createTextRun($comentario);
                }
                // Definir cores de fundo e do texto
                $this->horasPorDia ? $this->estiloComEntradaESaida($value, $style) : $this->estiloParaStatus($value, $style);

                // Aplicando borda em todas as células
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
        }


        return [];
    }

    private function estiloParaStatus($value, $style)
    {
        $fill = $style->getFill();
        $font = $style->getFont();

            switch ($value) {
                        case 'P':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('c6efce'); // Verde
                            $font->getColor()->setRGB('006100'); // Preto
                            break;

                        case 'F':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('ffc7ce'); // Vermelho
                            $font->getColor()->setRGB('9c0006');
                            break;

                        case 'FE':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('4169E1'); // Azul escuro
                            $font->getColor()->setRGB('FFFFFF'); // Branco
                            break;
                        case 'L':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('4169E1'); // Azul escuro
                            $font->getColor()->setRGB('FFFFFF'); // Branco
                            break;

                        case 'FR':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('87CEFA'); // Azul claro
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case 'J':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('ffeb9c'); // Amarelo
                            $font->getColor()->setRGB('9c5700'); // Preto
                            break;

                        case 'FS':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('CCCCCC'); // Cinza
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case 'R':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('D8BFD8'); // Roxo claro
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case 'PE':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('FFA500'); // Laranja
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case '-':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('FFFFFF'); // Branco
                            $font->getColor()->setRGB('000000'); // Preto
                            break;
                        case $value < '0':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('c6efce'); // Verde
                            $font->getColor()->setRGB('006100'); // Preto
                            break;
                        case $value > 0:
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('ffc7ce'); // Vermelho
                            $font->getColor()->setRGB('9c0006');
                            break;

                        default:
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('A9A9A9'); // Cinza escuro
                            $font->getColor()->setRGB('FFFFFF'); // Branco
                            break;
                    }

    }
    private function estiloComEntradaESaida($value, $style)
    {
        $fill = $style->getFill();
        $font = $style->getFont();

        if (str_contains($value, '|')) {
            // Contém "|"
            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('c6efce'); // Verde
            $font->getColor()->setRGB('006100'); // Preto
        } else {
            switch ($value) {

                        case 'Falta':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('ffc7ce'); // Vermelho
                            $font->getColor()->setRGB('9c0006');
                            break;

                        case 'Férias':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('4169E1'); // Azul escuro
                            $font->getColor()->setRGB('FFFFFF'); // Branco
                            break;
                        case 'Licença':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('4169E1'); // Azul escuro
                            $font->getColor()->setRGB('FFFFFF'); // Branco
                            break;

                        case 'Feriado':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('87CEFA'); // Azul claro
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case 'Justificado':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('ffeb9c'); // Amarelo
                            $font->getColor()->setRGB('9c5700'); // Preto
                            break;

                        case "Final de \nSemana":
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('CCCCCC'); // Cinza
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case 'Recesso':
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('D8BFD8'); // Roxo claro
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        case "Justificativa \nPendente":
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('FFA500'); // Laranja
                            $font->getColor()->setRGB('000000'); // Preto
                            break;

                        default:
                            $fill->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                 ->getStartColor()->setRGB('FFFFFF'); // Branco
                            $font->getColor()->setRGB('000000'); // Preto
                            break;
                    }
        }
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
            ['Relatório Consolidado Mensal de Presença'],
            [],
            ["Unidade: $this->unidade"], // Unidade está fixo ou você pode dinamicamente substituir esse valor
            ['Data: ' . ucfirst($formatter->format($data))], // A data será dinâmica
            [' '],
        ];
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
                $totalLinhas = count($this->array());

                $totalLinhasDados = $totalLinhas + 5;
                // Por exemplo, se os dados começam na linha 2 e vão até a última linha
                $range = 'A6:' . Coordinate::stringFromColumnIndex($totalColunas) . $totalLinhasDados;
                // Ativar filtro automático
                $sheet->setAutoFilter($range);

            foreach (['A6', 'A3', 'A4'] as $cell) {
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
            // Aplicar a formatação de tabela nas outras colunas (da B em diante)
            $sheet->getStyle('B6:' . Coordinate::stringFromColumnIndex($totalColunas) . '6')
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle('B6:' . Coordinate::stringFromColumnIndex($totalColunas) . ($totalLinhasDados))
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
                $sheet->getStyle( Coordinate::stringFromColumnIndex($totalColunas - 1) . ($totalLinhasDados))
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    ],
                ]);
                $sheet->getStyle('A7:' . 'A' . ($totalLinhasDados))
                ->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    ],
                ]);

            $sheet->getStyle('B7:' . Coordinate::stringFromColumnIndex($totalColunas - 1) . ($totalLinhasDados))
                  ->applyFromArray([
                      'alignment' => [
                          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                          'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                      ],
                  ]);

            $sheet->getDelegate();
            $totalLinhas = count($this->array()); // Total de linhas de dado
            if(!$this->horasPorDia) {
                    $linhaLegenda = $totalLinhas + 8; // Adiciona algumas linhas abaixo dos dados

                    $legenda = [
                        ['Legenda:'],
                        ['P - Presente'],
                        ['F - Falta'],
                        ['FE - Férias'],
                        ['FR - Feriado'],
                        ['J - Justificado'],
                        ['FS - Final de Semana'],
                        ['R - Recesso'],
                        ['PE - Justificativa Pendente'],
                        ['L - Licença ou outro motivo'],
                    ];
                    foreach ($legenda as $index => $linha) {
                        $linhaExcel = $linhaLegenda + $index;
                        $sheet->setCellValue('A' . $linhaExcel, $linha[0]);
                        $sheet->getStyle('A' . $linhaExcel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    }

                    //titulo legenda
                    $sheet->getStyle('A'. $linhaLegenda)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => 'FFFFFF'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => '0070C0'],
                        ],
                    ]);
                    //presente
                    $sheet->getStyle('A'. $linhaLegenda + 1)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '006100'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'c6efce'],
                        ],
                    ]);
                    //falta
                    $sheet->getStyle('A'. $linhaLegenda + 2)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '9c0006'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'ffc7ce'],
                        ],
                    ]);
                    //ferias
                    $sheet->getStyle('A'. $linhaLegenda + 3)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => 'FFFFFF'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => '4169E1'],
                        ],
                    ]);
                    //feriado
                    $sheet->getStyle('A'. $linhaLegenda + 4)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '000000'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => '87CEFA'],
                        ],
                    ]);
                    //justificado
                    $sheet->getStyle('A'. $linhaLegenda + 5)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '9c5700'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'ffeb9c'],
                        ],
                    ]);
                    //final de semana
                    $sheet->getStyle('A'. $linhaLegenda + 6)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '000000'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'CCCCCC'],
                        ],
                    ]);
                    //recesso
                    $sheet->getStyle('A'. $linhaLegenda + 7)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '000000'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'D8BFD8'],
                        ],
                    ]);
                    //justificativa pendente
                    $sheet->getStyle('A'. $linhaLegenda + 8)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => '000000'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFA500'],
                        ],
                    ]);
                    $sheet->getStyle('A'. $linhaLegenda + 9)->applyFromArray([
                        'font' => [
                            'color' => ['argb' => 'FFFFFF'],
                            'size' => 12,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => '4169E1'],
                        ],
                    ]);
                }

                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.25);
                $sheet->getPageMargins()->setRight(0.2);
                $sheet->getPageMargins()->setLeft(0.2);
                $sheet->getPageMargins()->setBottom(0.25);
            }

        ];

    }

    public function title(): string
    {
        return "Relatório Ponto $this->unidade $this->mes/$this->ano";
    }

}
