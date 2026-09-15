<?php

namespace App\Motor\Infraestructura\Persistencia\Esquema;

use Closure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Base de las migraciones del Motor. Centraliza las convenciones que exige la
 * spec (RT-007 vigencias, RT-010 sin borrado físico, FK RESTRICT en todas las
 * relaciones motor_*) y las del entorno (utf8mb4_unicode_ci como todo `swnov`).
 *
 * Las migraciones deben poder correr en sqlite :memory: (tests) y en MySQL 8.
 */
abstract class MigracionMotor extends Migration
{
    public const CHARSET = 'utf8mb4';

    public const COLLATION = 'utf8mb4_unicode_ci';

    protected function crear(string $tabla, Closure $definicion, ?string $comentario = null): void
    {
        Schema::create($tabla, function (Blueprint $t) use ($definicion, $comentario) {
            $t->charset = self::CHARSET;
            $t->collation = self::COLLATION;
            if ($comentario !== null && $this->esMysql()) {
                $t->comment($comentario);
            }
            $definicion($t);
        });
    }

    /** Columnas vigencia_desde / vigencia_hasta (NULL = abierta) + índice compuesto. */
    protected function vigencia(Blueprint $t, string $nombreIndice, bool $desdeObligatoria = true): void
    {
        $desde = $t->date('vigencia_desde')->comment('Inicio de vigencia (inclusive)');
        if (! $desdeObligatoria) {
            $desde->nullable();
        }
        $t->date('vigencia_hasta')->nullable()->comment('Fin de vigencia (inclusive). NULL = vigencia abierta');
        $t->index(['vigencia_desde', 'vigencia_hasta'], $nombreIndice);
    }

    /** Estado de validación normativa: SIN_DATO | SIN_CONFIRMAR | CONFIRMADO. */
    protected function estadoValidacion(Blueprint $t, string $default = 'SIN_CONFIRMAR'): void
    {
        $t->string('estado_validacion', 20)->default($default)
            ->comment('SIN_DATO | SIN_CONFIRMAR | CONFIRMADO. En PRODUCCION solo CONFIRMADO habilita el cálculo');
    }

    /** Auditoría de quién cargó/modificó (nullable, FK a users con set null). */
    protected function auditoria(Blueprint $t): void
    {
        $t->unsignedBigInteger('usuario_id')->nullable();
        $t->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
    }

    /** FK física entre tablas del motor: siempre RESTRICT/RESTRICT (56/56 en la spec). */
    protected function fk(Blueprint $t, string $columna, string $tabla, string $referencia, ?string $nombre = null): void
    {
        $fk = $t->foreign($columna, $nombre)->references($referencia)->on($tabla)->onDelete('restrict')->onUpdate('restrict');
    }

    /** Referencia lógica a una tabla legacy (sue*): índice, sin FK física. */
    protected function referenciaLegacy(Blueprint $t, string $columna, string $tablaLegacy, bool $nullable = false): void
    {
        $col = $t->unsignedBigInteger($columna)->comment("Referencia lógica a {$tablaLegacy}.id (sin FK física: tabla compartida con el portal)");
        if ($nullable) {
            $col->nullable();
        }
        $t->index($columna);
    }

    /** CHECK constraint solo en MySQL (sqlite no admite ALTER ... ADD CONSTRAINT CHECK). */
    protected function check(string $tabla, string $nombre, string $expresion): void
    {
        if ($this->esMysql()) {
            DB::statement("ALTER TABLE `{$tabla}` ADD CONSTRAINT `{$nombre}` CHECK ({$expresion})");
        }
    }

    protected function esMysql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
