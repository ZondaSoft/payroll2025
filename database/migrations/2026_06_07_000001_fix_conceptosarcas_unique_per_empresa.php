<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * conceptosarcas es POR EMPRESA (id_empresa + codigo_contribuyente). El unique original estaba sobre
     * codigo_contribuyente SOLO (global), lo que impedía cargar la parametrización de una segunda empresa
     * (ej. BLEXA) si el código ya existía en otra (ej. PETROANDINA). Se reemplaza por un unique COMPUESTO:
     * mismo código permitido entre empresas distintas, pero único dentro de cada empresa.
     */
    public function up(): void
    {
        Schema::table('conceptosarcas', function (Blueprint $table) {
            $table->dropUnique('conceptosarcas_codigo_contribuyente_unique');
            $table->unique(['id_empresa', 'codigo_contribuyente'], 'conceptosarcas_empresa_concepto_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conceptosarcas', function (Blueprint $table) {
            $table->dropUnique('conceptosarcas_empresa_concepto_unique');
            $table->unique('codigo_contribuyente', 'conceptosarcas_codigo_contribuyente_unique');
        });
    }
};
