<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reapunta la obra social del legajo (sue001s.cod_obsoc) al catálogo SICOSS
 * (sicoss_obras) en lugar del catálogo legacy sue009s.
 *
 * sicoss_obras usa códigos de 6 dígitos con ceros a la izquierda (varchar(6)),
 * mientras que sue009s usaba códigos sin padear (ej. "2501"). Se normalizan los
 * datos existentes antes de crear la nueva FK.
 *
 * Idempotente / tolerante a drift dev↔prod: la FK vieja se dropea solo si existe,
 * y la nueva se crea solo si falta (en prod la FK a sue009s puede no haberse creado nunca).
 */
return new class extends Migration
{
    private function fkExiste(string $tabla, string $fk): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tabla)
            ->where('CONSTRAINT_NAME', $fk)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    public function up(): void
    {
        // 1) Quitar la FK antigua (apuntaba a sue009s) — solo si existe.
        if ($this->fkExiste('sue001s', 'sue001s_cod_obsoc_foreign')) {
            Schema::table('sue001s', function (Blueprint $table) {
                $table->dropForeign(['cod_obsoc']);
            });
        }

        // 2) Normalizar cod_obsoc al catálogo sicoss_obras
        //    Remapeo puntual: 100304 (OS Técnicos de Vuelo, solo en sue009s) -> 003900 (mismo concepto en sicoss_obras)
        DB::table('sue001s')->where('cod_obsoc', '100304')->update(['cod_obsoc' => '003900']);
        //    Pad de códigos cortos a 6 dígitos con ceros a la izquierda
        DB::statement("UPDATE sue001s SET cod_obsoc = LPAD(cod_obsoc, 6, '0') WHERE cod_obsoc IS NOT NULL AND cod_obsoc <> '' AND CHAR_LENGTH(cod_obsoc) < 6");

        // 3) Misma normalización en sue070s (bajas) por consistencia (no tiene FK)
        DB::statement("UPDATE sue070s SET cod_obsoc = LPAD(cod_obsoc, 6, '0') WHERE cod_obsoc IS NOT NULL AND cod_obsoc <> '' AND CHAR_LENGTH(cod_obsoc) < 6");

        // 4) Anular referencias que no existan en sicoss_obras, para que la FK se pueda crear sin error 1452
        //    (cod_obsoc es nullable; un valor que no está en el catálogo era una referencia inválida).
        DB::statement("UPDATE sue001s SET cod_obsoc = NULL WHERE cod_obsoc IS NOT NULL AND cod_obsoc <> '' AND cod_obsoc NOT IN (SELECT codigo FROM sicoss_obras)");

        // 5) Crear la nueva FK hacia sicoss_obras — solo si no existe ya.
        if (!$this->fkExiste('sue001s', 'sue001s_cod_obsoc_foreign')) {
            Schema::table('sue001s', function (Blueprint $table) {
                $table->foreign('cod_obsoc')
                    ->references('codigo')->on('sicoss_obras')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        // Quitar la FK a sicoss_obras (si existe)
        if ($this->fkExiste('sue001s', 'sue001s_cod_obsoc_foreign')) {
            Schema::table('sue001s', function (Blueprint $table) {
                $table->dropForeign(['cod_obsoc']);
            });
        }

        // Reverso best-effort hacia la codificación de sue009s.
        // (La normalización a 6 dígitos no es 100% reversible.)
        DB::table('sue001s')->where('cod_obsoc', '003900')->update(['cod_obsoc' => '100304']);
        DB::statement("UPDATE sue001s SET cod_obsoc = TRIM(LEADING '0' FROM cod_obsoc) WHERE cod_obsoc IS NOT NULL AND cod_obsoc <> '' AND cod_obsoc NOT IN (SELECT codigo FROM sue009s)");
        // Anular lo que aún no exista en sue009s para poder recrear la FK sin error
        DB::statement("UPDATE sue001s SET cod_obsoc = NULL WHERE cod_obsoc IS NOT NULL AND (cod_obsoc = '' OR cod_obsoc NOT IN (SELECT codigo FROM sue009s))");

        // Restaurar la FK original hacia sue009s (si no existe)
        if (!$this->fkExiste('sue001s', 'sue001s_cod_obsoc_foreign')) {
            Schema::table('sue001s', function (Blueprint $table) {
                $table->foreign('cod_obsoc')
                    ->references('codigo')->on('sue009s')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }
};
