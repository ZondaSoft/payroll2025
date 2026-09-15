<?php

namespace Database\Seeders;

use App\Motor\Aplicacion\CasosDeUso\PublicarConcepto;
use App\Motor\Aplicacion\Puertos\RepositorioConceptos;
use App\Motor\Aplicacion\Puertos\RepositorioVersiones;
use App\Motor\Dominio\Enums\TipoCambio;
use App\Motor\Dominio\Excepciones\MotorBloqueado;
use Illuminate\Database\Seeder;

/**
 * Motor: publica la versión 1 de cada concepto PUBLICADO que todavía no tiene
 * versiones (snapshot + hash construidos por el dominio, no por una migración).
 * Idempotente: los conceptos con versión se saltan. Se fuerza la publicación
 * (sin validaciones) porque los datos del Excel nacen con avisos por diseño.
 */
class MotorVersionesInicialesSeeder extends Seeder
{
    public function run(PublicarConcepto $publicar, RepositorioVersiones $versiones, RepositorioConceptos $conceptos): void
    {
        $n = 0;
        foreach ($versiones->conceptosSinVersion() as $id) {
            $c = $conceptos->porId($id);
            if ($c === null) {
                continue;
            }
            try {
                $publicar->ejecutar($id, $c->vigencia->desde, 'Versión inicial (carga desde la especificación)', TipoCambio::Alta, null, $c->vigencia->hasta, forzar: true);
                $n++;
            } catch (MotorBloqueado $e) {
                $this->command?->warn("  {$c->codigo}: {$e->getMessage()}");
            }
        }
        $this->command?->info("Versiones iniciales publicadas: {$n}");
    }
}
