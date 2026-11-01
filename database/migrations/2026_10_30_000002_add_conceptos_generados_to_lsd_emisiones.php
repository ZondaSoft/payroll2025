<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deja asentado en la emisión qué conceptos se dieron de alta automáticamente al
 * generarla.
 *
 * Cuando el parámetro general `lsd_no_conceptos` está desactivado, los conceptos de
 * la liquidación que no existen en el catálogo (`sue102s`) se crean solos en vez de
 * frenar la generación con un modal. Eso es cómodo pero deja de ser visible, así que
 * el detalle se guarda acá y se muestra en el resumen final: qué código se creó, con
 * qué tipo y con qué código ARCA quedó — o sin ninguno, que es lo que hay que
 * revisar después.
 *
 * Mismo criterio que `legajos_ignorados`: un JSON por emisión, no una tabla aparte,
 * porque es un dato de auditoría de esa generación y no se consulta por sí mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->json('conceptos_generados')->nullable()->after('legajos_ignorados')
                ->comment('Conceptos creados automáticamente en sue102s al generar (lsd_no_conceptos desactivado)');
        });
    }

    public function down(): void
    {
        Schema::table('lsd_emisiones', function (Blueprint $table) {
            $table->dropColumn('conceptos_generados');
        });
    }
};
