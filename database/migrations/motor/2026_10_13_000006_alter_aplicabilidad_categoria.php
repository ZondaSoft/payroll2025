<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El nivel CATEGORIA de la aplicabilidad se apoyaba en motor_categoria_concepto,
 * que en el modelo v2 pasó a ser sólo la definición del valor categórico (VALCAT):
 * sus columnas accion y vigencia se retiraron. La aplicabilidad queda entonces
 * completa en una sola tabla, con los cinco ámbitos.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        Schema::table('motor_concepto_aplicabilidad', function (Blueprint $t) {
            $t->unsignedBigInteger('id_categoria')->nullable()->after('id_convenio')
                ->comment('Referencia lógica a sue006s.id (sin FK física: tabla compartida con el portal)');
            $t->index('id_categoria');
        });
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
