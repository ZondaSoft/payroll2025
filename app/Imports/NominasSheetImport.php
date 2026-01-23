<?php

namespace App\Imports;

use App\Models\Sue001;
//use Illuminate\Support\Collection;
//use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\ImportLiquidacionOk;
use App\Models\ImportLiquidacionErr;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class NominasSheetImport implements ToModel, WithChunkReading
{
    /**
    * @param array $row
    */
    private int $count = 0;
    private int $rechazados = 0; // Contador de registros importados exitosamente


    public function __construct(
        private string $periodo,   // YYYYMM
        private int $tipoliq,      // 1..5
        private string $nom_arch,
        private int $tam_arch
    )
    {}


    public function model(array $row)
    {
        if ($row[0] == 'empresa' or $row[0] == 'Empresa') {
            return null;
        }
        if ($row[0] == '') {
            return null;
        }
        // Control de 5ta columna (Legajo)
        if ($row[4] == '') {
            return null;
        }

        $this->count++;

        $tipoliq = $row['3'];
        $legajo = $row['4'];

        $legajoExistente = Sue001::where('codigo', $legajo)->first();

        if ($legajoExistente) {
            $excelDate = $row[1];
            $periodo = Date::excelToDateTimeObject($excelDate)->format('Ym');
            $legajo = substr($row[4], 0, 6);
            $descripcion = $row[11];
            $importe = floatval(str_replace(',', '.', $row[14]));

            //dd($row[0], $row[1], $periodo, $row[2], $row[3]);

            // Verifica si el periodo es válido
            if ($periodo != $this->periodo) {
                $this->rechazados++;

                // Grabo log del registro con error
                ImportLiquidacionErr::create([
                    'registro' => $this->count,
                    'detalle' => "El periodo no coincide con el solicitado: " . $periodo,
                ]);

                return null;
            }

            // Verifica si el tipo de liquidacion
            if ($this->tipoliq == 1 && $tipoliq == 'Normal') {
            } elseif ($this->tipoliq == 2 && $tipoliq == '1ra Quincena') {
            } elseif ($this->tipoliq == 3 && $tipoliq == '2da Quincena') {
            } elseif ($this->tipoliq == 4 && $tipoliq == 'SAC') {
            } elseif ($this->tipoliq == 5 && $tipoliq == 'Liq. Final') {
            } else {
                $this->rechazados++;

                // Grabo log del registro con error
                ImportLiquidacionErr::create([
                    'registro' => $this->count,
                    'detalle' => "El tipo de liquidación no coincide con el solicitado (" . $this->tipoliq . ") : " . $tipoliq,
                ]);

                return null;
            }

            // Creo el periodo si no existe
            $existePeriodo = Sue100::where('periodo', $this->periodo)
                ->where('tipoliq', $this->tipoliq)
                ->first();

            if (!$existePeriodo) {
                $periodo = Sue100::create([
                    'periodo' => $this->periodo,
                    'tipoliq' => $this->tipoliq,
                    'user' => auth()->user()->id,
                    'nombrearchivo' => $this->nom_arch,
                    'tamanio' => $this->tam_arch,
                ]);
            }

            // Grabo log del registro con exito
            ImportLiquidacionOk::create([
                'registro' => $this->count,
                'periodo' => $periodo,
                'legajo' => $legajo,
                'descripcion' => $descripcion,
                'importe' => $importe,
                'detalle' => 'Importación exitosa',
            ]);

            return new Sue090([
                'empresa' => $row[0],
                'periodo' => $periodo,
                'detalle' => $row[2],
                'tipoliq' => $this->tipoliq,
                'legajo' => $legajo,
                'categoria' => $row[6],
                'fechaingreso' => $row[7],
                'zona' => $row[8],
                'concepto' => $row[10],
                'descripcion' => $descripcion,
                'valor'     => floatval(str_replace(',', '.', $row[12])),
                'cantidad'  => floatval(str_replace(',', '.', $row[13])),
                'importe'   => $importe,
                'obra_social' => $row[19],
                'modalidad' => $row[20],
                'tarea'     => $row[21],
                'sueldo'    => floatval(str_replace(',', '.', $row[22])),
                'tiporem'   => $row[23],
                'convenio'  => $row[24],
            ]);
        } else {
            // Si el legajo no existe, no se importa el registro
            // Puedes registrar un mensaje de error o manejarlo de otra manera si es necesario
            //Log::error("Legajo no encontrado: " . $legajo);

            $this->rechazados++;

            // Grabo log del registro con exito
            ImportLiquidacionErr::create([
                'registro' => $this->count,
                'detalle' => "Legajo no encontrado: " . $legajo,
            ]);
        }

        return null;
    }

    // Método para obtener el conteo de registros importados
    public function getRowCount(): int
    {
        return $this->count;
    }

    // Método para obtener el conteo de registros rechazados
    public function getRejectedCount(): int
    {
        return $this->rechazados;
    }

    public function chunkSize(): int
    {
        return 1000; // o lo que te convenga
    }
}
