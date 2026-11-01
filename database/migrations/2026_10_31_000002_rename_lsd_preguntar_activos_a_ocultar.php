<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El parámetro de los empleados activos sin liquidación se da vuelta: pasa de
 * "preguntar" a "ocultar y no incluirlos".
 *
 * Es la misma función leída al revés — hoy `preguntar = false` ya hace exactamente
 * lo que el parámetro nuevo llama `ocultar = true`: no muestra el diálogo y deja a
 * esos empleados afuera del TXT. Lo que cambia es cuál es la respuesta afirmativa,
 * y con ella el default: antes había que decir "sí" para que el diálogo apareciera,
 * ahora hay que decir "sí" para que no aparezca.
 *
 * Por eso el valor guardado se **invierte** en lugar de copiarse: quien tenía el
 * diálogo activado lo sigue teniendo activado después de la migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sue_config', function (Blueprint $table) {
            $table->renameColumn('lsd_preguntar_activos', 'lsd_ocultar_activos');
        });

        // ocultar = NO preguntar. Sin esto, quien tenía el diálogo activado (el valor
        // por defecto) se quedaría sin él de un día para el otro.
        DB::table('sue_config')->update(['lsd_ocultar_activos' => DB::raw('NOT lsd_ocultar_activos')]);

        // El rename conserva el default anterior (true), que ahora significaría lo
        // contrario de lo que significaba.
        Schema::table('sue_config', function (Blueprint $table) {
            $table->boolean('lsd_ocultar_activos')->default(false)
                ->comment('Ocultar el diálogo de empleados activos sin liquidación y dejarlos afuera del TXT')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('sue_config', function (Blueprint $table) {
            $table->renameColumn('lsd_ocultar_activos', 'lsd_preguntar_activos');
        });

        DB::table('sue_config')->update(['lsd_preguntar_activos' => DB::raw('NOT lsd_preguntar_activos')]);

        Schema::table('sue_config', function (Blueprint $table) {
            $table->boolean('lsd_preguntar_activos')->default(true)
                ->comment('Preguntar por los empleados activos sin liquidación al generar el LSD')
                ->change();
        });
    }
};
