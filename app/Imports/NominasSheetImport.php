<?php

namespace App\Imports;

use App\Models\Sue001;  // Empleados
use App\Models\Sue086;  // Empresas
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
    private bool $empresaValidada = false;
    private bool $errorImportando = false;

    public function __construct(
        private string $periodo,   // YYYYMM
        private int $idEmpresa,      // 1..5
        private string $nom_arch,
        private int $tam_arch
    )
    {}


    public function model(array $row)
    {
        if (!$this->empresaValidada) {
            $cuit = $row[1];
            $cuitSinGuiones = str_replace('-', '', $cuit);
            $empresa = Sue086::where('cuit', $cuit)
                ->orWhereRaw("REPLACE(cuit, '-', '') = ?", [$cuitSinGuiones])
                ->first();

            if (!$empresa) {
                $this->rechazados++;
                $this->count++;

                // ✅ Empresa validada, no vuelvo a chequear
                $this->empresaValidada = true;
                $this->errorImportando = true;
                
                // Grabo log del registro con error
                ImportLiquidacionErr::create([
                    'registro' => $this->count,
                    'detalle' => "Cuit de la empresa no encontrado: " . $cuit,
                ]);

                return null;
            }

            if ($empresa->id != $this->idEmpresa) {
                $this->rechazados++;
                $this->count++;

                // ✅ Empresa validada, no vuelvo a chequear
                $this->empresaValidada = true;
                $this->errorImportando = true;

                // Grabo log del registro con error
                ImportLiquidacionErr::create([
                    'registro' => $this->count,
                    'detalle' => "El CUIT de la empresa seleccionada no coincide con el importado: " . $cuit,
                ]);

                return null;
            }

            // ✅ Empresa validada, no vuelvo a chequear
            $this->empresaValidada = true;
        }
        
        if (!$this->errorImportando) {
            //dd($row[1], $row[2], $row[3], $row[4]);
            // if ($row[0] == 'empresa' or $row[0] == 'Empresa') {
            //     return null;
            // }
            // if ($row[0] == '') {
            //     return null;
            // }
            // // Control de 5ta columna (Legajo)
            // if ($row[4] == '') {
            //     return null;
            // }

            // Busco el cuil enviado en la tabla de legajos (SUE001)
            $cuil = $row[0];
            $legajoExistente = Sue001::where('cuil', $cuil)->first();

            if (!$legajoExistente) {
                $this->count++;

                if ($this->count > 5) {
                    $this->rechazados++;

                    // Grabo log del registro con error
                    ImportLiquidacionErr::create([
                        'registro' => $this->count,
                        'detalle' => "El CUIL importado no existe en la nomina de legajos activos : " . $cuil,
                    ]);
                }

                return null;
            }

            $legajo = $legajoExistente->legajo;
            $descripcion = 'Legajo actualizado';
            $condicion = $row[7];
            $situacion = $row[6];
            $actividad = $row[8];
            $modalidad = $row[9];
            $siniestro = $row[10];
            $localidad = $row[11];

            if ($legajoExistente) {
                // Recopilo datos antes de actualizarlos
                $actividadAnterior = $legajoExistente->sicoss_activ;
                $situacionAnterior = $legajoExistente->sicoss_situa;
                $modalidadAnterior = $legajoExistente->sicoss_modal;
                $condicionAnterior = $legajoExistente->sicoss_condi;
                $siniestroAnterior = $legajoExistente->sicoss_sini;
                $localidadAnterior = $legajoExistente->sicoss_zona;

                // Actualizo el legajo
                $legajoExistente->update([
                    'sicoss_activ' => $actividad,
                    'sicoss_situa' => $situacion,
                    'sicoss_modal' => $modalidad,
                    'sicoss_condi' => $condicion,
                    'sicoss_sini' => $siniestro,
                    'sicoss_zona' => $localidad,
                ]);

                if ($actividadAnterior != $actividad || $situacionAnterior != $situacion || $modalidadAnterior != $modalidad || $condicionAnterior != $condicion || $siniestroAnterior != $siniestro || $localidadAnterior != $localidad) {
                    $descripcion = 'Legajo actualizado';

                    if  ($actividadAnterior != $actividad)
                        $descripcion = $descripcion . ' - Actividad: ' . $actividadAnterior . ' -> ' . $actividad;

                    if  ($situacionAnterior != $situacion)
                        $descripcion = ' - Situación: ' . $situacionAnterior . ' -> ' . $situacion;

                    if  ($modalidadAnterior != $modalidad)
                        $descripcion = ' - Modalidad: ' . $modalidadAnterior . ' -> ' . $modalidad;

                    if  ($condicionAnterior != $condicion)
                        $descripcion = ' - Condición: ' . $condicionAnterior . ' -> ' . $condicion;

                    if  ($siniestroAnterior != $siniestro)
                        $descripcion = ' - Siniestro: ' . $siniestroAnterior . ' -> ' . $siniestro;

                    if  ($localidadAnterior != $localidad)
                        $descripcion = ' - Localidad: ' . $localidadAnterior . ' -> ' . $localidad;

                } else {
                    $descripcion = 'Legajo no actualizado (Todos los datos importados coinciden)';
                }

                $descripcion = mb_substr($descripcion, 0, 255);

                // Grabo log del registro con exito
                ImportLiquidacionOk::create([
                    'registro' => $this->count,
                    'legajo' => $legajo,
                    'descripcion' => $descripcion,
                    'importe' => 0,
                    'detalle' => $descripcion,
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
        }

        $this->count++;

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
