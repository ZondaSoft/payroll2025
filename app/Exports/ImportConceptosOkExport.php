<?php

namespace App\Exports;
use App\Models\ImportConceptosArcaOk;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ImportConceptosOkExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ImportConceptosArcaOk::all([
            'registro',
            'codigo_afip',
            'descripcion',
            'codigo_contribuyente',
            'descripcion_contribuyente',
        ]);
    }

    /**
     * Define los encabezados del archivo Excel
     */
    public function headings(): array
    {
        return [
            'Registro',
            'Código AFIP',
            'Descripción',
            'Código Contribuyente',
            'Descripción Contribuyente',
        ];
    }

    /**
     * Registra los eventos para aplicar estilos
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // 1. Agregar título general en la fila 1
                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'Resultado de la importación');
                
                // Combinar celdas para el título (A1:E1)
                $sheet->mergeCells('A1:E1');
                
                // 2. Aplicar estilos al título general
                $titleCell = $sheet->getStyle('A1');
                $titleCell->getFont()->setBold(true)->setSize(14); // 14pt = 11pt (cuerpo) + 3
                $titleCell->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $titleCell->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                
                // 3. Aplicar estilos a los encabezados (ahora en fila 3)
                $headerRange = 'A3:E3';
                $headerStyle = $sheet->getStyle($headerRange);
                
                // Negrita
                $headerStyle->getFont()->setBold(true);
                
                // Bordes gruesos arriba y abajo
                $headerStyle->applyFromArray([
                    'borders' => [
                        'top' => [
                            'style' => Border::BORDER_THICK,
                        ],
                        'bottom' => [
                            'style' => Border::BORDER_THICK,
                        ],
                    ],
                ]);
                
                // Centrar texto
                $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                
                // 4. Establecer altura de fila para el título
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(18);
                
                // 5. Ajustar ancho de columnas
                $sheet->getColumnDimension('A')->setWidth(11);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(38);
                $sheet->getColumnDimension('D')->setWidth(19);
                $sheet->getColumnDimension('E')->setWidth(38);
            }
        ];
    }
}
