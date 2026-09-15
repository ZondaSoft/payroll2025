<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo — capa de interfaz (nivel 6): desacople y trazabilidad entre
 * Zonda Gestión (sue001s, sue028s) y el modelo canónico. No contiene reglas de
 * liquidación. El motor nunca consulta tablas legacy desde fórmulas (RT-003).
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_if_relacion_laboral_origen', function (Blueprint $t) {
            $t->id('id_interfaz_relacion');
            $t->unsignedBigInteger('relacion_laboral_id');
            $t->string('sistema_origen', 50)->comment('ZONDA_GESTION');
            $t->string('tabla_origen', 100)->comment('sue001s');
            $t->string('clave_origen', 100)->comment('sue001s.id (no el número de legajo: sobrevive a renumeraciones)');
            $t->string('codigo_legajo_origen', 100)->nullable()->comment('sue001s.codigo al momento de la sincronización');
            $t->char('hash_origen', 64)->nullable()->comment('sha256 de la tupla relevante del legajo para detectar cambios');
            $t->boolean('activo')->default(true);
            $t->dateTime('created_at', 6)->nullable();
            $t->dateTime('updated_at', 6)->nullable();
            $t->unique(['sistema_origen', 'tabla_origen', 'clave_origen'], 'motor_if_rl_origen_unique');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
        }, 'Mapeo identidad legacy ↔ relación laboral canónica (RT-004)');

        $this->crear('motor_novedades', function (Blueprint $t) {
            $t->id('id_novedad');
            $t->char('periodo', 7);
            $t->unsignedBigInteger('relacion_laboral_id');
            $t->string('codigo_novedad', 50);
            $t->decimal('cantidad', 12, 4)->nullable()->comment('Unidades (horas, km, días…) cuando la novedad es de cantidad');
            $t->decimal('valor', 18, 6)->nullable()->comment('Importe cuando la novedad es monetaria');
            $t->string('origen', 50)->nullable()->comment('ZONDA_GESTION | MANUAL');
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['periodo', 'relacion_laboral_id'], 'motor_novedades_periodo_rl_idx');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
            $this->fk($t, 'codigo_novedad', 'motor_tipos_novedad', 'codigo');
        }, 'Novedades normalizadas del período (cantidad o importe, nunca ambos ambiguos). Dos del mismo tipo se suman');

        $this->crear('motor_if_novedad_origen', function (Blueprint $t) {
            $t->id('id_interfaz_novedad');
            $t->unsignedBigInteger('id_novedad')->nullable()->comment('NULL cuando la fila de origen no pudo normalizarse (ver estado)');
            $t->string('sistema_origen', 50);
            $t->string('tabla_origen', 100)->comment('sue028s');
            $t->string('clave_origen', 100)->comment('sue028s.id + columna (p. ej. 792242:cantidad)');
            $t->dateTime('fecha_importacion', 6);
            $t->char('hash_origen', 64)->nullable();
            $t->string('estado', 30)->default('NORMALIZADA')->comment('NORMALIZADA | SIN_MAPEO | IGNORADA | SIN_RELACION_LABORAL | ANULADA');
            $t->string('motivo', 255)->nullable();
            $t->dateTime('created_at', 6)->nullable();
            $t->dateTime('updated_at', 6)->nullable();
            $t->unique(['sistema_origen', 'tabla_origen', 'clave_origen'], 'motor_if_nov_origen_unique');
            $t->index(['estado'], 'motor_if_nov_estado_idx');
            $this->fk($t, 'id_novedad', 'motor_novedades', 'id_novedad');
        }, 'Procedencia de cada novedad normalizada y registro de las rechazadas (RT-005)');

        $this->crear('motor_novedad_mapeo', function (Blueprint $t) {
            $t->id('id_mapeo');
            $t->string('cod_nov', 10)->comment('sue031s.codigo (case-sensitive en origen)');
            $t->string('columna_origen', 30)->default('cantidad')->comment('sue028s.cantidad | int_vac | int_enf | int_acc | int_susp | int_aperc | int_licencias');
            $t->string('codigo_tipo_novedad', 50);
            $t->string('unidad', 20)->nullable();
            $t->boolean('es_importe')->default(false)->comment('true: la cantidad de origen son pesos → motor_novedades.valor');
            $t->decimal('factor', 18, 6)->default(1);
            $t->string('accion', 20)->default('NORMALIZAR')->comment('NORMALIZAR | IGNORAR');
            $t->boolean('activo')->default(true);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['cod_nov', 'columna_origen'], 'motor_nov_mapeo_unique');
            $this->fk($t, 'codigo_tipo_novedad', 'motor_tipos_novedad', 'codigo');
        }, 'Traducción de códigos de novedad legacy (sue031s) a tipos de novedad del motor. Resuelve la polimorfía de sue028s.cantidad');
    }

    public function down(): void
    {
        foreach (['motor_novedad_mapeo', 'motor_if_novedad_origen', 'motor_novedades', 'motor_if_relacion_laboral_origen'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
