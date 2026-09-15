<?php

namespace App\Console\Commands\Motor;

use App\Motor\Aplicacion\CasosDeUso\NormalizarNovedades;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Illuminate\Console\Command;

class NormalizarNovedadesCommand extends Command
{
    protected $signature = 'motor:normalizar-novedades {periodo : Período YYYY-MM o YYYYMM}';

    protected $description = 'Motor: normaliza las novedades legacy (sue028s) del período a tipos de novedad del motor según motor_novedad_mapeo';

    public function handle(NormalizarNovedades $casoDeUso): int
    {
        $r = $casoDeUso->ejecutar(Periodo::de((string) $this->argument('periodo')));

        $this->table(['creadas', 'actualizadas', 'sin cambio', 'anuladas', 'ignoradas'], [[$r->creadas, $r->actualizadas, $r->sinCambio, $r->anuladas, $r->ignoradas]]);
        if ($r->sinMapeo !== []) {
            arsort($r->sinMapeo);
            $this->warn('Códigos sin mapeo (cargarlos en Motor › Mapeo de novedades):');
            foreach ($r->sinMapeo as $cod => $n) {
                $this->line(sprintf('  %-8s %5d filas', $cod, $n));
            }
        }
        if ($r->sinRelacion !== []) {
            $this->warn(count($r->sinRelacion).' legajos con novedades sin relación laboral sincronizada: '.implode(', ', array_keys($r->sinRelacion)));
        }

        return self::SUCCESS;
    }
}
