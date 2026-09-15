<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo — modelo canónico (nivel 3): RELACION_LABORAL y sus hijas,
 * más EMPRESA_ENCUADRAMIENTO. La relación laboral es la contratación concreta
 * del trabajador; los datos personales no se duplican: se obtienen del legajo
 * legacy (sue001s) mediante adaptador. Va antes de configuración y conceptos
 * porque ambas la referencian (ámbito TRABAJADOR).
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_relacion_laboral', function (Blueprint $t) {
            $t->id('relacion_laboral_id');
            $this->referenciaLegacy($t, 'id_legajo', 'sue001s');
            $this->referenciaLegacy($t, 'empresa_id', 'sue086s');
            $t->string('legajo', 50)->comment('Número de legajo (sue001s.codigo) — informativo, la clave es id_legajo');
            $t->string('cuil', 11)->nullable()->comment('Informativo (copia del legajo) para listados sin JOIN a legacy');
            $t->string('apellido_nombre', 100)->nullable()->comment('Informativo (copia del legajo)');
            $t->date('fecha_ingreso');
            $t->date('fecha_egreso')->nullable();
            $t->date('fecha_antiguedad')->nullable()->comment('Antigüedad reconocida si difiere del ingreso (sue001s.fecha_antiguedad_reconocida)');
            $t->string('estado', 30)->default('VIGENTE')->comment('VIGENTE | BAJA | INCONSISTENTE_ORIGEN');
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['empresa_id', 'estado'], 'motor_rl_empresa_estado_idx');
        }, 'Contratación concreta del trabajador. No es la tabla persona/legajo');

        $this->crear('motor_relacion_laboral_categoria', function (Blueprint $t) {
            $t->id('relacion_categoria_id');
            $t->unsignedBigInteger('relacion_laboral_id');
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s', nullable: true);
            $t->string('codigo_categoria', 10)->nullable()->comment('Informativo (sue006s.codigo)');
            $this->vigencia($t, 'motor_rl_categoria_vigencia_idx');
            $this->estadoValidacion($t);
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['relacion_laboral_id', 'vigencia_desde'], 'motor_rl_categoria_rl_idx');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
        }, 'Historial de categoría de la relación laboral (puede cambiar durante la contratación)');

        $this->crear('motor_relacion_laboral_arca', function (Blueprint $t) {
            $t->id('encuadre_sicoss_id');
            $t->unsignedBigInteger('relacion_laboral_id')->unique();
            $t->string('codigo_condicion', 50);
            $t->string('actividad_interna', 100)->nullable();
            $t->string('codigo_actividad_arca', 50)->nullable();
            $t->string('codigo_modalidad', 50);
            $t->string('codigo_localidad_arca', 50)->nullable();
            $this->vigencia($t, 'motor_rl_arca_vigencia_idx');
            $this->estadoValidacion($t);
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
            $this->fk($t, 'codigo_condicion', 'motor_condiciones_arca', 'codigo_condicion');
            $this->fk($t, 'codigo_actividad_arca', 'motor_actividades_arca', 'codigo_actividad_arca');
            $this->fk($t, 'codigo_modalidad', 'motor_modalidades_arca', 'codigo_modalidad');
            $this->fk($t, 'codigo_localidad_arca', 'motor_localidades_arca', 'codigo_localidad_arca');
        }, 'Encuadre SICOSS de la relación laboral (1:1): condición, actividad, modalidad y localidad');

        $this->crear('motor_relacion_laboral_regimen', function (Blueprint $t) {
            $t->id('relacion_regimen_id');
            $t->unsignedBigInteger('relacion_laboral_id');
            $t->unsignedBigInteger('id_regimen');
            $this->vigencia($t, 'motor_rl_regimen_vigencia_idx');
            $this->estadoValidacion($t);
            $t->string('documento_respaldo', 100)->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['relacion_laboral_id', 'vigencia_desde'], 'motor_rl_regimen_rl_idx');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
            $this->fk($t, 'id_regimen', 'motor_regimenes_contributivos', 'id_regimen');
        }, 'Regímenes contributivos asignados a la relación laboral, cada uno con su vigencia');

        $this->crear('motor_situacion_revista_periodo', function (Blueprint $t) {
            $t->id('situacion_periodo_id');
            $t->char('periodo', 7);
            $t->unsignedBigInteger('relacion_laboral_id');
            $t->unsignedSmallInteger('orden')->default(1);
            $t->string('codigo_situacion', 50);
            $t->unsignedTinyInteger('dia_desde')->default(1);
            $t->unsignedTinyInteger('dia_hasta')->default(31);
            $t->string('origen_novedad', 100)->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['periodo', 'relacion_laboral_id', 'orden'], 'motor_situacion_periodo_unique');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
            $this->fk($t, 'codigo_situacion', 'motor_situaciones_revista_arca', 'codigo_situacion');
        }, 'Qué le pasó a cada trabajador dentro del período: la única tabla mensual del bloque contributivo');

        $this->crear('motor_empresa_encuadramiento', function (Blueprint $t) {
            $t->id('encuadramiento_id');
            $this->referenciaLegacy($t, 'empresa_id', 'sue086s');
            $t->string('encuadramiento', 50);
            $t->string('detalle', 100)->nullable();
            $this->vigencia($t, 'motor_emp_encuadr_vigencia_idx');
            $t->string('certificado_mipyme', 100)->nullable();
            $t->date('vencimiento_certificado')->nullable();
            $t->string('origen', 100)->nullable();
            $t->string('documento_respaldo', 100)->nullable();
            $this->estadoValidacion($t, 'SIN_DATO');
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['empresa_id', 'vigencia_desde'], 'motor_emp_encuadr_empresa_idx');
            $this->fk($t, 'encuadramiento', 'motor_encuadramientos_patronales', 'encuadramiento');
        }, 'Línea de tiempo del encuadramiento patronal A/B de cada empresa (pendiente de relevamiento en la spec; nace SIN_DATO)');
    }

    public function down(): void
    {
        foreach (['motor_empresa_encuadramiento', 'motor_situacion_revista_periodo', 'motor_relacion_laboral_regimen',
            'motor_relacion_laboral_arca', 'motor_relacion_laboral_categoria', 'motor_relacion_laboral'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
