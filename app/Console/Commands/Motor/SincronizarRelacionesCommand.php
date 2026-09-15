<?php

namespace App\Console\Commands\Motor;

use App\Motor\Aplicacion\CasosDeUso\SincronizarRelacionesLaborales;
use App\Motor\Dominio\ValorObjetos\Periodo;
use Illuminate\Console\Command;

class SincronizarRelacionesCommand extends Command
{
    protected $signature = 'motor:sincronizar-relaciones {empresa? : Código de empresa (sue086s.codigo, p. ej. 05). Vacío = todas} {--periodo= : Período YYYY-MM para la situación de revista (default: mes actual)}';

    protected $description = 'Motor: crea/actualiza las relaciones laborales canónicas a partir de los legajos activos (sue001s)';

    public function handle(SincronizarRelacionesLaborales $casoDeUso): int
    {
        $periodo = $this->option('periodo') ? Periodo::de((string) $this->option('periodo')) : null;
        $r = $casoDeUso->ejecutar($this->argument('empresa'), $periodo);

        $this->table(['creadas', 'actualizadas', 'sin cambio', 'bajas'], [[$r->creadas, $r->actualizadas, $r->sinCambio, $r->bajas]]);
        foreach ($r->avisos as $a) {
            $this->line("  aviso: {$a}");
        }
        foreach ($r->conflictos as $c) {
            $this->warn("  conflicto: {$c}");
        }

        return self::SUCCESS;
    }
}
