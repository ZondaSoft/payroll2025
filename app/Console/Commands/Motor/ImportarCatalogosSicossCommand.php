<?php

namespace App\Console\Commands\Motor;

use App\Motor\Aplicacion\CasosDeUso\ImportarCatalogosSicoss;
use Illuminate\Console\Command;

class ImportarCatalogosSicossCommand extends Command
{
    protected $signature = 'motor:importar-catalogos-sicoss';

    protected $description = 'Motor: carga en los catálogos ARCA del motor los catálogos SICOSS existentes (sicoss01s/05s/08s/12s/sicoss_zonas), sin pisar filas';

    public function handle(ImportarCatalogosSicoss $casoDeUso): int
    {
        foreach ($casoDeUso->ejecutar() as $catalogo => $n) {
            $this->line(sprintf('  %-12s %4d insertadas', $catalogo, $n));
        }

        return self::SUCCESS;
    }
}
