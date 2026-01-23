<?php

namespace App\Exports;

use App\Models\ImportLiquidacionErr;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportSicossErrExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ImportLiquidacionErr::all([
            'registro',
            'detalle',
        ]);
    }

    public function headings(): array
    {
        return [
            'Registro',
            'Detalle',
        ];
    }
}
