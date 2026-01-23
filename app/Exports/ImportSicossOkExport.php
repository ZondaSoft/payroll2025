<?php

namespace App\Exports;
use App\Models\ImportLiquidacionOk;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportSicossOkExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ImportLiquidacionOk::all([
            'registro',
            'legajo',
            'detalle',
        ]);
    }

    /**
     * Define los encabezados del archivo Excel
     */
    public function headings(): array
    {
        return [
            'Registro',
            'Legajo',
            'Detalle',
        ];
    }
}
