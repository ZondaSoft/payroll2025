<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;

/**
 * Normativa incorporada por la Etapa 3A (auditoría ARCA 2026-09-25): la
 * distribución de los regímenes sustitutivos entre subsistemas, las reglas de
 * base diferencial que viven fuera de la matriz S/N, y el mapeo de los códigos
 * de concepto del sistema legacy.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        // RIFL sustituye un 5 % que NO es un único destino: se reparte 1,31 % SIPA +
        // 3 % INSSJP + 0,57 % AAFF + 0,12 % FNE. Sin esta tabla el régimen no se
        // puede imputar por subsistema.
        $this->crear('motor_regimen_distribucion', function (Blueprint $t) {
            $t->id('id_regimen_distribucion');
            $t->unsignedBigInteger('id_regimen');
            $t->unsignedBigInteger('id_subsistema');
            $t->string('destino', 30)->comment('Subsistema destino de esta porción');
            $t->decimal('porcentaje', 12, 6);
            $this->vigencia($t, 'motor_regdist_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['id_regimen', 'id_subsistema', 'vigencia_desde'], 'motor_regdist_reg_sub_desde_uq');
            $this->fk($t, 'id_regimen', 'motor_regimenes_contributivos', 'id_regimen', 'motor_regdist_regimen_fk');
            $this->fk($t, 'id_subsistema', 'motor_subsistemas', 'id_subsistema', 'motor_regdist_subsistema_fk');
        }, 'Distribución de un régimen sustitutivo entre subsistemas (RIFL 5 % = 1,31+3+0,57+0,12)');

        // Diferencial de obra social por jornada parcial: no sale de la matriz de
        // conceptos sino de comparar la base contra la de jornada completa
        // (art. 92 ter LCT + ARCA G18).
        $this->crear('motor_reglas_base_diferencial_arca', function (Blueprint $t) {
            $t->id('id_regla_base_diferencial');
            $t->string('codigo', 50)->comment('No es único por sí solo: la misma regla aplica a varias bases');
            $t->string('codigo_base', 10);
            $t->string('tipo_diferencial', 20)->comment('APORTE_OS | CONTRIB_OS');
            $t->string('disparador', 255)->comment('Condición que la activa (p. ej. JORNADA_PARCIAL)');
            $t->text('metodo')->comment('Cómo se calcula el diferencial');
            $t->string('modulo_responsable', 30);
            $this->vigencia($t, 'motor_rbd_vigencia_idx', false);
            $t->string('estado_validacion', 30)->default('PENDIENTE_VIGENCIA');
            $t->text('fuente_normativa')->nullable();
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['codigo', 'codigo_base'], 'motor_rbd_codigo_base_uq');
            $this->fk($t, 'codigo_base', 'motor_bases_arca', 'codigo_base', 'motor_rbd_base_fk');
        }, 'Reglas de base diferencial ARCA ajenas a la matriz S/N de conceptos');

        // Un mismo código legacy significa cosas distintas según la empresa y el
        // convenio: el mapeo es contextual, no un atributo del concepto.
        $this->crear('motor_concepto_legacy_mapeo', function (Blueprint $t) {
            $t->id('id_mapeo');
            $t->string('sistema_origen', 30);
            $this->referenciaLegacy($t, 'empresa_id', 'sue086s', true);
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s', true);
            $t->string('contexto_origen', 30)->nullable()->comment('Contexto del libro de origen (LESTAC, LTRANS, FC…)');
            $t->string('codigo_legacy', 30);
            $t->string('familia_rango_legacy', 60)->nullable();
            $t->string('codigo_concepto', 50)->nullable()->comment('NULL con estado_mapeo NO_MODELADO');
            $t->boolean('tiene_canti')->default(false)->comment('El código legacy trae cantidad asociada');
            $t->string('codigo_novedad_canti', 50)->nullable();
            $t->string('tipo_remuneracion_legacy', 10)->nullable()->comment('sue | adi | hse | nre | pre | red');
            $t->string('naturaleza_legacy', 30)->nullable();
            $t->string('periodo_evidencia', 7)->nullable()->comment('Período del que se tomó la evidencia (YYYY-MM)');
            $this->vigencia($t, 'motor_clm_vigencia_idx', false);
            $t->string('estado_mapeo', 30)->comment('CONFIRMADO_PERIODO | NO_MODELADO | PROPUESTO');
            $t->string('fuente', 255)->nullable();
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['codigo_legacy', 'empresa_id', 'id_convenio'], 'motor_clm_legacy_contexto_idx');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto', 'motor_clm_concepto_fk');
        }, 'Mapeo contextual entre códigos del sistema legacy y conceptos del motor');
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
