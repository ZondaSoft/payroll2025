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

            $legajo = $legajoExistente->codigo;
            $descripcion = 'Legajo actualizado';
            $obra = $row[2];
            $reduccion = $row[3];
            if ($reduccion === 'Sí' || $reduccion === 'si' || $reduccion === 'SI') {
                $reduccion = true;
            } else {
                $reduccion = false;
            }
            
            $cobScvo = $row[4];
            if ($cobScvo === 'Sí' || $cobScvo === 'si' || $cobScvo === 'SI') {
                $cobScvo = true;
            } else {
                $cobScvo = false;
            }

            $situacion = $row[5];
            $condicion = $row[6];
            $actividad = $row[7];
            $modalidad = $row[8];
            $siniestro = $row[9];
            $localidad = $row[10];
            $porcReduccion = $row[11];
            $conyugue = $row[12];
            if ($conyugue === 'Sí' || $conyugue === 'si' || $conyugue === 'SI') {
                $conyugue = true;
            } else {
                $conyugue = false;
            }

            $hijos = $row[13];
            $adherente = $row[14];
            
            $situ_revista1 = $row[27];
            $dia_revista1 = $row[28];
            $situ_revista2 = $row[29];
            $dia_revista2 = $row[30];
            $situ_revista3 = $row[31];
            $dia_revista3 = $row[32];

            if ($legajoExistente) {
                // Recopilo datos antes de actualizarlos
                $actividadAnterior = $legajoExistente->sicoss_activ;
                $situacionAnterior = $legajoExistente->sicoss_situa;
                $modalidadAnterior = $legajoExistente->sicoss_modal;
                $condicionAnterior = $legajoExistente->sicoss_condi;
                $siniestroAnterior = $legajoExistente->sicoss_sini;
                $localidadAnterior = $legajoExistente->sicoss_zona;
                $obraAnterior = $legajoExistente->obra_sijp;
                
                $reduccionAnterior = $legajoExistente->sicoss_reduccion;
                $cobScvoAnterior = $legajoExistente->sicoss_cob_scvo;
                $porcReduccionAnterior = $legajoExistente->sicoss_porc_reduc;
                $conyugueAnterior = $legajoExistente->sicoss_conyuge;
                $hijosAnterior = $legajoExistente->sicoss_hijos;
                $adherenteAnterior = $legajoExistente->sicoss_adherentes;

                // Actualizo el legajo
                $legajoExistente->update([
                    'sicoss_activ' => $actividad,
                    'sicoss_situa' => $situacion,
                    'sicoss_modal' => $modalidad,
                    'sicoss_condi' => $condicion,
                    'sicoss_sini' => $siniestro,
                    'sicoss_zona' => $localidad,
                    'obra_sijp' => $obra,
                    'sicoss_reduccion' => $reduccion,
                    'sicoss_cob_scvo' => $cobScvo,
                    'sicoss_porc_reduc' => $porcReduccion,
                    'sicoss_conyuge' => $conyugue,
                    'sicoss_hijos' => $hijos,
                    'sicoss_adherentes' => $adherente,
                ]);

                if ($actividadAnterior != $actividad || $situacionAnterior != $situacion || $modalidadAnterior != $modalidad 
                    || $condicionAnterior != $condicion || $siniestroAnterior != $siniestro || $localidadAnterior != $localidad 
                    || $obraAnterior != $obra || $reduccionAnterior != $reduccion || $cobScvoAnterior != $cobScvo 
                    || $porcReduccionAnterior != $porcReduccion || $conyugueAnterior != $conyugue || $hijosAnterior != $hijos || $adherenteAnterior != $adherente) {

                    $descripcion = 'Legajo actualizado';

                    if ($actividadAnterior != $actividad)
                        $descripcion = $descripcion . ' - Actividad: ' . $actividadAnterior . ' -> ' . $actividad;

                    if ($situacionAnterior != $situacion)
                        $descripcion = $descripcion . ' - Situación: ' . $situacionAnterior . ' -> ' . $situacion;

                    if ($modalidadAnterior != $modalidad)
                        $descripcion = $descripcion . ' - Modalidad: ' . $modalidadAnterior . ' -> ' . $modalidad;

                    if ($condicionAnterior != $condicion)
                        $descripcion = $descripcion . ' - Condición: ' . $condicionAnterior . ' -> ' . $condicion;

                    if ($siniestroAnterior != $siniestro)
                        $descripcion = $descripcion . ' - Siniestro: ' . $siniestroAnterior . ' -> ' . $siniestro;

                    if ($localidadAnterior != $localidad)
                        $descripcion = $descripcion . ' - Localidad: ' . $localidadAnterior . ' -> ' . $localidad;

                    if ($obraAnterior != $obra)
                        $descripcion = $descripcion . ' - Obra: ' . $obraAnterior . ' -> ' . $obra;

                    if ($reduccionAnterior != $reduccion)
                        $descripcion = $descripcion . ' - Reducción: ' . ($reduccionAnterior ? 'Sí' : 'No') . ' -> ' . ($reduccion ? 'Sí' : 'No');

                    if ($cobScvoAnterior != $cobScvo)
                        $descripcion = $descripcion . ' - Cob. Scvo: ' . ($cobScvoAnterior ? 'Sí' : 'No') . ' -> ' . ($cobScvo ? 'Sí' : 'No');

                    if ($porcReduccionAnterior != $porcReduccion)
                        $descripcion = $descripcion . ' - % Reducción: ' . ($porcReduccionAnterior ?? '0') . ' -> ' . ($porcReduccion ?? '0');

                    if ($conyugueAnterior != $conyugue)
                        $descripcion = $descripcion . ' - Cónyuge: ' . ($conyugueAnterior ? 'Sí' : 'No') . ' -> ' . ($conyugue ? 'Sí' : 'No');

                    if ($hijosAnterior != $hijos)
                        $descripcion = $descripcion . ' - Hijos: ' . ($hijosAnterior ? 'Sí' : 'No') . ' -> ' . ($hijos ? 'Sí' : 'No');

                    if ($adherenteAnterior != $adherente)
                        $descripcion = $descripcion . ' - Adherentes: ' . ($adherenteAnterior ? 'Sí' : 'No') . ' -> ' . ($adherente ? 'Sí' : 'No');



                } else {
                    $descripcion = 'Legajo no actualizado (Todos los datos importados coinciden)';
                }

                $descripcion = mb_substr($descripcion, 0, 255);

                // Grabo log del registro con exito
                ImportLiquidacionOk::create([
                    'registro' => $this->count,
                    'legajo' => $legajo,
                    'cuil' => $cuil,
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
                    'detalle' => "CUIL no encontrado: " . $cuil,
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
