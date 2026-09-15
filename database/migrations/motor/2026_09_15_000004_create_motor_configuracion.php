<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo — configuración declarativa (nivel 4): parámetros y sus
 * valores por alcance, modelos de antigüedad y vacaciones con sus tramos,
 * asignación por convenio y sueldos básicos por categoría.
 * Todo con vigencia: cada modificación de una escala usada genera una nueva
 * versión, nunca se modifica la anterior (RT-006).
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_parametros', function (Blueprint $t) {
            $t->id('id_parametro');
            $t->string('codigo', 50)->unique()->comment('Código funcional que usan las estrategias: TARIFA_KM, PORC_TITULO, …');
            $t->string('descripcion', 255);
            $t->string('tipo_parametro', 30)->comment('PORCENTAJE | TARIFA | HORAS | CANTIDAD | ENTERO');
            $t->string('unidad', 30)->nullable();
            $t->boolean('es_tecnico')->default(false)->comment('Parámetro técnico del motor (REDONDEO_DECIMALES, DIVISOR_DIAS, HORAS_MES_REFERENCIA)');
            $t->boolean('activo')->default(true);
            $this->auditoria($t);
            $t->timestamps();
        }, 'Un parámetro es un dato de configuración que una fórmula/estrategia utiliza como entrada');

        $this->crear('motor_valores_parametros', function (Blueprint $t) {
            $t->id('id_valor_parametro');
            $t->unsignedBigInteger('id_parametro');
            $t->string('alcance', 20)->comment('GENERAL | CONVENIO | CATEGORIA | EMPRESA | TRABAJADOR (precedencia creciente)');
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s', nullable: true);
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s', nullable: true);
            $this->referenciaLegacy($t, 'id_empresa', 'sue086s', nullable: true);
            $t->unsignedBigInteger('relacion_laboral_id')->nullable();
            $t->decimal('valor', 18, 6);
            $this->vigencia($t, 'motor_valores_param_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['id_parametro', 'alcance', 'vigencia_desde'], 'motor_valores_param_alcance_idx');
            $this->fk($t, 'id_parametro', 'motor_parametros', 'id_parametro');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
        }, 'Valores de cada parámetro según alcance y vigencia. Dos vigentes de igual nivel = empate = bloqueo');
        $this->check('motor_valores_parametros', 'motor_valores_param_ambito_chk',
            "(alcance = 'GENERAL' AND id_convenio IS NULL AND id_categoria IS NULL AND id_empresa IS NULL AND relacion_laboral_id IS NULL)
             OR (alcance = 'CONVENIO' AND id_convenio IS NOT NULL) OR (alcance = 'CATEGORIA' AND id_categoria IS NOT NULL)
             OR (alcance = 'EMPRESA' AND id_empresa IS NOT NULL) OR (alcance = 'TRABAJADOR' AND relacion_laboral_id IS NOT NULL)");

        $this->crear('motor_modelos_antiguedad', function (Blueprint $t) {
            $t->id('id_modelo_antiguedad');
            $t->string('codigo', 50)->unique();
            $t->string('descripcion', 255);
            $t->string('metodo_calculo', 10)->comment('ESCALA (tramos) | LINEAL (% por año)');
            $t->decimal('porcentaje_por_anio', 18, 6)->nullable()->comment('Solo LINEAL');
            $t->decimal('porcentaje_maximo', 18, 6)->nullable()->comment('NULL = sin tope configurado');
            $t->unsignedSmallInteger('version')->default(1);
            $t->string('estado', 50)->nullable();
            $this->estadoValidacion($t);
            $t->boolean('activo')->default(true);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
        }, 'Modelos de escalas de antigüedad reutilizables por distintos convenios. El resultado siempre es un porcentaje');

        $this->crear('motor_antiguedad_tramos', function (Blueprint $t) {
            $t->id('id_tramo_antiguedad');
            $t->unsignedBigInteger('id_modelo_antiguedad');
            $t->decimal('anios_desde', 12, 4);
            $t->decimal('anios_hasta', 12, 4)->nullable()->comment('NULL = último tramo abierto');
            $t->decimal('porcentaje', 18, 6);
            $t->unsignedSmallInteger('orden');
            $t->timestamps();
            $t->unique(['id_modelo_antiguedad', 'anios_desde'], 'motor_antig_tramos_unique');
            $this->fk($t, 'id_modelo_antiguedad', 'motor_modelos_antiguedad', 'id_modelo_antiguedad');
        });

        $this->crear('motor_convenio_modelo_antiguedad', function (Blueprint $t) {
            $t->id('id_asignacion_antiguedad');
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s');
            $t->unsignedBigInteger('id_modelo_antiguedad');
            $this->vigencia($t, 'motor_conv_antig_vigencia_idx');
            $this->estadoValidacion($t);
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'id_modelo_antiguedad', 'motor_modelos_antiguedad', 'id_modelo_antiguedad');
        }, 'Asigna a cada convenio el modelo de antigüedad que debe utilizar');

        $this->crear('motor_modelos_vacaciones', function (Blueprint $t) {
            $t->id('id_modelo_vacaciones');
            $t->string('codigo', 50)->unique();
            $t->string('descripcion', 255);
            $t->unsignedSmallInteger('version')->default(1);
            $t->string('estado', 50)->nullable();
            $t->string('fuente', 100)->nullable();
            $t->boolean('activo')->default(true);
            $this->auditoria($t);
            $t->timestamps();
        });

        $this->crear('motor_vacaciones_tramos', function (Blueprint $t) {
            $t->id('id_tramo_vacaciones');
            $t->unsignedBigInteger('id_modelo_vacaciones');
            $t->decimal('anios_desde', 12, 4);
            $t->boolean('incluye_desde')->default(true);
            $t->decimal('anios_hasta', 12, 4)->nullable();
            $t->boolean('incluye_hasta')->nullable();
            $t->unsignedSmallInteger('dias');
            $t->unsignedSmallInteger('orden');
            $t->timestamps();
            $t->unique(['id_modelo_vacaciones', 'anios_desde'], 'motor_vac_tramos_unique');
            $this->fk($t, 'id_modelo_vacaciones', 'motor_modelos_vacaciones', 'id_modelo_vacaciones');
        });

        $this->crear('motor_convenio_modelo_vacaciones', function (Blueprint $t) {
            $t->id('id_asignacion_vacaciones');
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s');
            $t->unsignedBigInteger('id_modelo_vacaciones');
            $this->vigencia($t, 'motor_conv_vac_vigencia_idx');
            $t->string('fuente', 255)->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'id_modelo_vacaciones', 'motor_modelos_vacaciones', 'id_modelo_vacaciones');
        });

        $this->crear('motor_basicos', function (Blueprint $t) {
            $t->id('id_basico');
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s');
            $t->string('codigo_categoria', 10)->nullable()->comment('Informativo (sue006s.codigo)');
            $this->vigencia($t, 'motor_basicos_vigencia_idx');
            $t->decimal('importe_basico', 18, 6);
            $t->string('moneda', 10)->default('ARS');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['id_categoria', 'vigencia_desde'], 'motor_basicos_categoria_vigencia_unique');
        }, 'Sueldo básico de cada categoría con su vigencia (escala salarial)');
    }

    public function down(): void
    {
        foreach (['motor_basicos', 'motor_convenio_modelo_vacaciones', 'motor_vacaciones_tramos', 'motor_modelos_vacaciones',
            'motor_convenio_modelo_antiguedad', 'motor_antiguedad_tramos', 'motor_modelos_antiguedad', 'motor_valores_parametros', 'motor_parametros'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
