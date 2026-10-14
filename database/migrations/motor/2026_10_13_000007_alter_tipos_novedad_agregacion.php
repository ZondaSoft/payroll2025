<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El DSL exige que cada novedad declare cómo se agregan dos novedades del mismo
 * tipo en un período: SUMA (dos partes de horas extras se suman) o UNICO (la
 * segunda es una corrección, no un acumulado).
 *
 * Sin ese dato el analizador no puede publicar una fórmula que use NOV(), porque
 * «cuánto vale la novedad» sería ambiguo. Es la columna regla_agregacion del
 * modelo v2, que faltaba en motor_tipos_novedad.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        Schema::table('motor_tipos_novedad', function (Blueprint $t) {
            $t->string('tipo_valor', 20)->nullable()->after('descripcion')
                ->comment('CANTIDAD | IMPORTE | PORCENTAJE: qué representa el valor de la novedad');
            $t->string('regla_agregacion', 10)->nullable()->after('unidad')
                ->comment('SUMA | UNICO. El DSL no publica una fórmula con NOV() de una novedad que no la declara');
            $t->text('observaciones')->nullable()->after('regla_agregacion');
        });

        // Lo cargado hasta ahora son cantidades que se acumulan en el período; es lo
        // que declara el libro v5 para las 15 novedades del catálogo.
        Schema::getConnection()->table('motor_tipos_novedad')
            ->whereNull('regla_agregacion')->update(['regla_agregacion' => 'SUMA', 'tipo_valor' => 'CANTIDAD']);
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
