<?php

namespace App\Exports;

use App\Models\ImportLiquidacionOk;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ImportSicossOkExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithEvents
{
    private $empresa;

    public function __construct($empresa = null)
    {
        $this->empresa = $empresa;
    }

    public function collection()
    {
        return ImportLiquidacionOk::all([
            'registro',
            'legajo',
            'cuil',
            'detalle',
            'situacion_ant',
            'situacion_imp',
            'obra_social_ant',
            'obra_social_imp',
            'condicion_ant',
            'condicion_imp',
            'actividad_ant',
            'actividad_imp',
            'modalidad_ant',
            'modalidad_imp',
            'siniestro_ant',
            'siniestro_imp',
            'localidad_ant',
            'localidad_imp',
        ]);
    }

    public function headings(): array
    {
        return [
            'Registro',
            'Legajo',
            'CUIL',
            'Detalle',
            'Situación Anterior',
            'Situación Importada',
            'Obra Social Anterior',
            'Obra Social Importada',
            'Condición Anterior',
            'Condición Importada',
            'Actividad Anterior',
            'Actividad Importada',
            'Modalidad Anterior',
            'Modalidad Importada',
            'Siniestro Anterior',
            'Siniestro Importado',
            'Localidad Anterior',
            'Localidad Importada',
        ];
    }

    public function title(): string
    {
        return 'Resultado importación';
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insertar 3 filas al inicio (título, subtítulo, línea en blanco)
                $sheet->insertNewRowBefore(1, 3);

                // Fila 1: Título
                $sheet->setCellValue('A1', 'Resultado de importación de nómina desde Sicoss');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                ]);
                $sheet->mergeCells('A1:R1');

                // Fila 2: Subtítulo con empresa
                $nombreEmpresa = $this->empresa->detalle ?? 'N/A';
                $cuitEmpresa = $this->empresa->cuit ?? 'N/A';
                $sheet->setCellValue('A2', "Empresa: {$nombreEmpresa} - {$cuitEmpresa}");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
                $sheet->mergeCells('A2:R2');

                // Fila 3: vacía (línea en blanco)
                // Fila 4: encabezados (ya están por el insertNewRowBefore)

                $lastRow = $sheet->getHighestRow();

                // Estilo encabezados (fila 4)
                $sheet->getStyle('A4:R4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                ]);

                // Columnas "Anterior" en gris claro (E, G, I, K, M, O, Q) desde fila 5
                $colsAnteriores = ['E', 'G', 'I', 'K', 'M', 'O', 'Q'];
                foreach ($colsAnteriores as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E0E0E0'],
                        ],
                    ]);
                }

                // Auto-ajustar anchos de A a D
                foreach (range('A', 'D') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Columnas E a R: ancho 9 y títulos centrados con wrap
                foreach (range('E', 'R') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(9);
                }
                $sheet->getStyle('E4:R4')->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
            },
        ];
    }
}
