<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo — normativa contributiva (nivel 2): aplicaciones por
 * subsistema, alícuotas de contribuciones y aportes, topes y regímenes.
 * Cuando cambia la norma no se edita la fila: se cierra la vigente y se agrega
 * una nueva (RT-006). Un tope usado en una liquidación no se modifica.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_subsistema_aplicacion', function (Blueprint $t) {
            $t->id('id_subsistema_aplicacion');
            $t->unsignedBigInteger('id_subsistema');
            $t->string('tipo_aplicacion', 20)->comment('APORTE | CONTRIBUCION');
            $t->string('codigo_base', 50)->nullable()->comment('REM_n ordinaria; NULL si la aplicación no posee base propia');
            $t->unsignedTinyInteger('columna_lsd')->comment('Posición oficial en el archivo de conceptos del LSD');
            $t->boolean('activo')->default(true);
            $this->estadoValidacion($t);
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->unique(['id_subsistema', 'tipo_aplicacion'], 'motor_subsis_aplic_unique');
            $this->fk($t, 'id_subsistema', 'motor_subsistemas', 'id_subsistema');
            $this->fk($t, 'codigo_base', 'motor_bases_arca', 'codigo_base');
        }, 'Combinaciones oficiales válidas subsistema × tipo de aplicación. La existencia de la fila expresa habilitación');

        $this->crear('motor_alicuotas_contribuciones', function (Blueprint $t) {
            $t->id('id_alicuota');
            $t->string('codigo', 50)->unique()->comment('Identificador estable (ALIC-001…) para trazabilidad e idempotencia');
            $t->string('encuadramiento', 50);
            $t->unsignedBigInteger('id_subsistema');
            $t->decimal('porcentaje', 18, 6)->comment('Fracción decimal: 10,77 % se guarda como 0.107700');
            $t->string('base_aplicable', 50);
            $this->vigencia($t, 'motor_alic_contrib_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'encuadramiento', 'motor_encuadramientos_patronales', 'encuadramiento');
            $this->fk($t, 'id_subsistema', 'motor_subsistemas', 'id_subsistema');
            $this->fk($t, 'base_aplicable', 'motor_bases_arca', 'codigo_base');
        }, 'Alícuotas de contribuciones patronales por encuadramiento y subsistema, con vigencia. La OS (6 %) no va acá');

        $this->crear('motor_alicuotas_aportes', function (Blueprint $t) {
            $t->id('id_alicuota_aporte');
            $t->string('codigo', 50)->unique()->comment('APOR-001…');
            $t->unsignedBigInteger('id_subsistema');
            $t->string('codigo_condicion', 50)->nullable()->comment('Condición ARCA específica. NULL = comodín (aplica a toda condición sin fila propia)');
            $t->decimal('porcentaje', 18, 6);
            $t->string('base_aplicable', 50);
            $t->boolean('aplica_tope')->default(true);
            $this->vigencia($t, 'motor_alic_aportes_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'id_subsistema', 'motor_subsistemas', 'id_subsistema');
            $this->fk($t, 'codigo_condicion', 'motor_condiciones_arca', 'codigo_condicion');
            $this->fk($t, 'base_aplicable', 'motor_bases_arca', 'codigo_base');
        }, 'Alícuotas de aportes personales por subsistema y condición ARCA. Una fila específica siempre gana sobre el comodín');

        $this->crear('motor_topes_minimos', function (Blueprint $t) {
            $t->id('id_tope');
            $t->string('codigo', 50)->unique()->comment('TOP-001…');
            $t->char('periodo', 7)->comment('YYYY-MM');
            $t->string('codigo_base', 50);
            $t->string('tipo_tope', 10)->comment('MAXIMO | MINIMO');
            $t->string('componente', 50)->comment('MENSUAL | SAC_COMPLETO | SAC_PROPORCIONAL | VACACIONES');
            $t->decimal('importe', 18, 6)->nullable()->comment('NULL = SIN_DATO: el motor bloquea, nunca lo interpreta como cero (regla C)');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['periodo', 'codigo_base', 'tipo_tope', 'componente'], 'motor_topes_clave_funcional');
            $this->fk($t, 'codigo_base', 'motor_bases_arca', 'codigo_base');
            $this->fk($t, 'componente', 'motor_componentes_tope', 'codigo_componente');
        }, 'Base mínima y máxima previsional por período, base y componente. Sin arrastre temporal ni entre componentes (reglas G/H)');

        $this->crear('motor_regimenes_contributivos', function (Blueprint $t) {
            $t->id('id_regimen');
            $t->string('codigo', 50)->unique()->comment('GENERAL, TRANSP_CARGAS, RIFL, …');
            $t->string('descripcion', 255);
            $t->string('tipo_regla', 20)->comment('BASE | SUSTITUTIVO | ADICIONAL | DESTINO — la columna decisiva: dice qué operación hacer');
            $t->string('sujeto_obligado', 20)->comment('EMPLEADOR | TRABAJADOR');
            $t->decimal('porcentaje', 18, 6)->nullable();
            $t->string('destino', 100)->nullable();
            $t->string('base_aplicable', 50);
            $t->unsignedSmallInteger('duracion_meses')->nullable()->comment('RIFL: 48 meses');
            $t->unsignedSmallInteger('orden_aplicacion')->comment('BASE 10 → SUSTITUTIVO 20 → ADICIONAL 30 → DESTINO 40');
            $this->vigencia($t, 'motor_regimenes_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'base_aplicable', 'motor_bases_arca', 'codigo_base');
        }, 'Desviaciones respecto del régimen general. Solo se asignan a trabajadores; ninguna empresa las edita');
    }

    public function down(): void
    {
        foreach (['motor_regimenes_contributivos', 'motor_topes_minimos', 'motor_alicuotas_aportes', 'motor_alicuotas_contribuciones', 'motor_subsistema_aplicacion'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
