<?php

namespace App\Imports;

use App\Models\Sue001;
use App\Models\ImportLiquidacionOk;
use App\Models\ImportLiquidacionErr;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
//use Illuminate\Support\Collection;
//use Maatwebsite\Excel\Concerns\ToCollection;

class NominasImport implements WithMultipleSheets  // ToCollection
{
    /**
    * @param Collection $collection
    */

    private NominasSheetImport $sheetImport;
    
    public function __construct(
        private string|int $onlySheet = 0,
        private string $periodo = '',   // YYYYMM
        private int $idEmpresa = 1,
        private string $nom_arch = '',
        private int $tam_arch = 0
    ) {
        $this->sheetImport = new NominasSheetImport($this->periodo, $this->idEmpresa, $this->nom_arch, $this->tam_arch);
    }

    public function sheets(): array
    {
        // Importa solo la hoja que te interesa
        return [
            $this->onlySheet => $this->sheetImport,
        ];
    }

    // 🚀 Métodos que exponen los contadores del hijo
    public function getRowCount(): int
    {
        return $this->sheetImport->getRowCount();
    }

    public function getRejectedCount(): int
    {
        return $this->sheetImport->getRejectedCount();
    }
}
