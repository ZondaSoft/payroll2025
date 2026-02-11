<?php

namespace App\Imports;

use App\Models\Conceptosarca;
use App\Models\Sue086;
use App\Models\ImportConceptosArcaOk;
use App\Models\ImportConceptosArcaErr;
use Illuminate\Support\Facades\Log;

class ConceptosArcaImport
{
    /**
     * Procesa un archivo de texto separado por comas (CSV)
     * del sistema ARCA con conceptos de contribuciones
     */
    private int $count = 0;
    private int $rechazados = 0;
    private bool $headerProcessed = false;
    private array $headerColumns = [];

    public function __construct(
        private string $filePath,
        private int $idEmpresa,
        private string $nom_arch,
        private int $tam_arch
    ) {}

    /**
     * Ejecuta la importación del archivo
     */
    public function execute(): bool
    {
        try {
            if (!file_exists($this->filePath)) {
                throw new \Exception("El archivo no existe: {$this->filePath}");
            }

            if (!is_readable($this->filePath)) {
                throw new \Exception("El archivo no es legible: {$this->filePath}");
            }

            // Limpiar registros de la empresa antes de importar
            Conceptosarca::where('id_empresa', $this->idEmpresa)->delete();

            // Abrir el archivo
            $handle = fopen($this->filePath, 'r');
            if ($handle === false) {
                throw new \Exception("No se pudo abrir el archivo: {$this->filePath}");
            }

            // Procesar línea por línea
            $lineNum = 0;
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $lineNum++;

                // Saltar líneas vacías
                if (empty(array_filter($row))) {
                    continue;
                }

                // Procesar el encabezado
                if (!$this->headerProcessed) {
                    $this->processHeader($row);
                    $this->headerProcessed = true;
                    continue;
                }

                // Procesar datos
                $this->processRow($row);
            }

            fclose($handle);
            return true;

        } catch (\Throwable $e) {
            Log::error('Error en ConceptosArcaImport::execute', [
                'error' => $e->getMessage(),
                'file' => $this->filePath,
            ]);

            ImportConceptosArcaErr::create([
                'registro' => $this->count,
                'id_empresa'     => $this->idEmpresa,
                'nombre_archivo' => $this->nom_arch,
                'tamanio_archivo'=> $this->tam_arch,
                'detalle' => 'Error general en la importación: ' . $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Procesa la fila de encabezados
     */
    private function processHeader(array $row): void
    {
        // Limpiar espacios en blanco
        $this->headerColumns = array_map('trim', $row);
    }

    /**
     * Procesa una fila de datos del archivo CSV
     */
    private function processRow(array $row): void
    {
        try {
            // Limpiar espacios en blanco
            $row = array_map('trim', $row);

            // Validar que al menos tenga los campos principales
            if (empty($row[0]) || empty($row[2])) {
                $this->count++;
                $this->rechazados++;

                ImportConceptosArcaErr::create([
                    'registro' => $this->count,
                    'id_empresa'     => $this->idEmpresa,
                    'nombre_archivo' => $this->nom_arch,
                    'tamanio_archivo'=> $this->tam_arch,
                    'detalle' => 'Fila incompleta: falta Código AFIP o Código Contribuyente',
                ]);
                return;
            }

            // Extraer datos principales
            $codigoAfip = intval($row[0]);
            $descripcion = substr($row[1] ?? '', 0, 80);
            $codigoContribuyente = intval($row[2]);
            $descripcionContribuyente = substr($row[3] ?? '', 0, 80);

            // Mapeo de índices para los campos de aportaciones/contribuciones
            $conceptosData = [
                'id_empresa'  => $this->idEmpresa,
                'codigo_afip' => $codigoAfip,
                'descripcion' => $descripcion,
                'codigo_contribuyente' => $codigoContribuyente,
                'descripcion_contribuyente' => $descripcionContribuyente,
                'marca_repetible' => $this->parseDecimal($row[4] ?? null),
                'aportes_sipa' => $this->parseDecimal($row[5] ?? null),
                'contribuciones_sipa' => $this->parseDecimal($row[6] ?? null),
                'aportes_inssjyp' => $this->parseDecimal($row[7] ?? null),
                'contribuciones_inssjyp' => $this->parseDecimal($row[8] ?? null),
                'aportes_obra_social' => $this->parseDecimal($row[9] ?? null),
                'contribuciones_obra_social' => $this->parseDecimal($row[10] ?? null),
                'aportes_fsr' => $this->parseDecimal($row[11] ?? null),
                'contribuciones_fsr' => $this->parseDecimal($row[12] ?? null),
                'aportes_renatea' => $this->parseDecimal($row[13] ?? null),
                'contribuciones_renatea' => $this->parseDecimal($row[14] ?? null),
                'contribuciones_aaff' => $this->parseDecimal($row[15] ?? null),
                'contribuciones_fne' => $this->parseDecimal($row[16] ?? null),
                'contribuciones_lrt' => $this->parseDecimal($row[17] ?? null),
                'aportes_diferenciales' => $this->parseDecimal($row[18] ?? null),
                'aportes_especiales' => $this->parseDecimal($row[19] ?? null),
            ];

            // Guardar en la base de datos
            Conceptosarca::create($conceptosData);

            $this->count++;

            ImportConceptosArcaOk::create([
                'registro' => $this->count,
                'id_empresa'     => $this->idEmpresa,
                'nombre_archivo' => $this->nom_arch,
                'tamanio_archivo'=> $this->tam_arch,
                'codigo_afip' => $codigoAfip,
                'descripcion' => $descripcion,
                'codigo_contribuyente' => $codigoContribuyente,
                'descripcion_contribuyente' => $descripcionContribuyente,
                'observaciones' => '',
            ]);

        } catch (\Throwable $e) {
            $this->count++;
            $this->rechazados++;

            ImportConceptosArcaErr::create([
                'registro' => $this->count,
                'id_empresa'     => $this->idEmpresa,
                'nombre_archivo' => $this->nom_arch,
                'tamanio_archivo'=> $this->tam_arch,
                'detalle' => 'Error procesando fila: ' . $e->getMessage(),
                'observaciones' => '',
            ]);

            Log::error('Error procesando fila en ConceptosArcaImport', [
                'error' => $e->getMessage(),
                'row' => $row,
            ]);
        }
    }

    /**
     * Convierte un valor a decimal válido (9,3)
     */
    private function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim($value);
        if ($value === '0' || $value === '') {
            return null;
        }

        try {
            return floatval($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retorna el total de registros procesados exitosamente
     */
    public function getRowCount(): int
    {
        return $this->count - $this->rechazados;
    }

    /**
     * Retorna el total de registros rechazados
     */
    public function getRejectedCount(): int
    {
        return $this->rechazados;
    }

    /**
     * Retorna el total de registros procesados
     */
    public function getTotalCount(): int
    {
        return $this->count;
    }
}

