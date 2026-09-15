<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo — conceptos (nivel 5): maestro de conceptos con su
 * configuración de cálculo, aplicabilidad por ámbito, bases de Zonda y su
 * composición, incidencias ARCA y versionado por vigencia (snapshot).
 *
 * TAB_CONCEPTOS de la spec (27 campos) + extensiones del mockup v7.9.3
 * (estrategia y fuentes desdobladas, redondeo, periodicidad, participación,
 * estado de configuración) documentadas en el plan §11.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_tipos_novedad', function (Blueprint $t) {
            $t->id('id_tipo_novedad');
            $t->string('codigo', 50)->unique()->comment('Código canónico del motor: HE_50, KM_REM, …');
            $t->string('descripcion', 100);
            $t->string('unidad', 20)->comment('HORAS | KM | CANTIDAD | DIAS | IMPORTE');
            $t->boolean('activo')->default(true);
            $this->auditoria($t);
            $t->timestamps();
        }, 'Catálogo canónico de tipos de novedad. Los códigos legacy (sue031s) se traducen vía motor_novedad_mapeo');

        $this->crear('motor_bases_zonda', function (Blueprint $t) {
            $t->id('id_base_zonda');
            $t->string('codigo_base', 50)->unique()->comment('Código funcional estable: BASE_HORA_NORMAL, BASE_SINDICAL, …');
            $t->string('nombre', 100);
            $t->text('descripcion')->nullable();
            $t->boolean('activo')->default(true);
            $this->estadoValidacion($t);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
        }, 'Bases de cálculo propias de Zonda (agrupaciones de conceptos). No confundir con BASES_ARCA. Sin vigencia: la vigencia vive en la composición');

        $this->crear('motor_conceptos', function (Blueprint $t) {
            $t->id('id_concepto');
            // --- 27 campos de TAB_CONCEPTOS ---
            $t->string('codigo_concepto', 50)->unique()->comment('Código estable: no se renombra una vez referenciado (RT-009)');
            $t->string('nombre_concepto', 255);
            $t->string('alias_recibo', 100)->nullable();
            $t->string('convenio_documental', 100)->nullable()->comment('Convenio de referencia documental (371/03, 40/89, AMBOS)');
            $t->string('tipo_concepto', 20)->comment('HABER | DESCUENTO | CONTRIBUCION | INFORMATIVO');
            $t->string('naturaleza', 20)->comment('REMUNERATIVO | NO_REMUNERATIVO | DESCUENTO | CONTRIBUCION');
            $t->string('signo', 10)->comment('CREDITO | DEBITO | NEUTRO');
            $t->string('unidad_principal', 20)->nullable();
            $t->string('modo_calculo', 20)->comment('FORMULA | NOVEDAD | IMPORTE_FIJO | PARAMETRO | TABLA | MODULO_SISTEMA | MANUAL');
            $t->string('modulo_responsable', 30)->nullable()->comment('MOTOR_GENERAL | SEGURIDAD_SOCIAL | OBRA_SOCIAL | ART | SCVO | SINDICATO | CONVENIO');
            $t->string('base_modulo', 50)->nullable()->comment('REM_n sobre la que opera un concepto MODULO_SISTEMA');
            $t->string('codigo_parametro', 50)->nullable();
            $t->string('codigo_novedad_cantidad', 50)->nullable();
            $t->unsignedBigInteger('id_formula_base')->nullable()->comment('Reservado: el módulo de fórmulas (DSL) es una fase posterior');
            $t->boolean('requiere_novedad')->default(false);
            $t->boolean('editable_manual')->default(false);
            $t->boolean('repetible')->default(false);
            $t->boolean('permite_importe_cero')->default(false);
            $t->boolean('imprime_recibo')->default(true);
            $t->boolean('es_concepto_sistema')->default(false)->comment('Concepto protegido: solo lectura salvo activo/estado');
            $t->string('tipo_repeticion', 30)->nullable()->comment('TODOS_LOS_MESES | CADA_N_MESES | DURANTE_N_MESES | MESES_ESPECIFICOS | SIN_RESTRICCION_PERIODICA');
            $t->unsignedSmallInteger('orden_visual_sugerido')->nullable();
            $this->vigencia($t, 'motor_conceptos_vigencia_idx');
            $t->boolean('activo')->default(true);
            $t->text('observaciones_funcionales')->nullable();
            // --- extensiones (mockup v7.9.3) ---
            $t->string('estrategia_calculo', 30)->nullable()->comment('IMPORTE_DIRECTO | CANTIDAD_X_VALOR | BASICO | ANTIGUEDAD | PORCENTAJE_SOBRE_BASE | MODULO_SISTEMA');
            $t->string('fuente_cantidad', 20)->nullable()->comment('FORMULA | NOVEDAD | PARAMETRO | MANUAL | NO_APLICA');
            $t->string('fuente_valor', 20)->nullable()->comment('FORMULA | PARAMETRO | BASE_ZONDA | BASICO | MANUAL | NO_APLICA');
            $t->string('fuente_importe', 20)->nullable()->comment('FORMULA | NOVEDAD | PARAMETRO | MANUAL | MODULO_SISTEMA | NO_APLICA');
            $t->string('codigo_base_zonda', 50)->nullable()->comment('Base de Zonda que CONSUME el concepto (hora normal, base para %). No puede integrarla (R13)');
            $t->decimal('factor_valor', 18, 6)->nullable()->comment('Multiplicador adicional del valor (p. ej. 1.5 para HE 50 % si no viene por parámetro)');
            $t->decimal('importe_fijo', 18, 6)->nullable();
            $t->string('codigo_novedad_importe', 50)->nullable()->comment('Novedad cuyo valor es directamente el importe');
            $t->string('regla_redondeo', 20)->nullable()->comment('SIN_REDONDEO | DECIMAL | ENTERO | PARAMETRIZADA');
            $t->string('politica_edicion', 30)->nullable()->comment('NO_EDITABLE | EDITABLE | EDITABLE_CON_JUSTIFICACION');
            $t->string('estado_configuracion', 20)->default('BORRADOR')->comment('BORRADOR | PUBLICADO. Solo PUBLICADO con versión vigente liquida');
            $t->unsignedSmallInteger('intervalo_meses')->nullable();
            $t->unsignedSmallInteger('cantidad_meses')->nullable();
            $t->json('meses_aplicacion')->nullable()->comment('MESES_ESPECIFICOS: ["ENERO","JULIO"]');
            $t->char('periodo_inicio', 7)->nullable();
            $t->json('tipos_liquidacion')->nullable()->comment('NULL = todas; ["NORMAL","SAC","FINAL","VACACIONES"]');
            $t->unsignedInteger('codigo_afip')->nullable()->comment('Mapeo ARCA principal (rango por naturaleza: 110000-499999 / 510000-799999 / 810000-829999)');
            $t->string('clasificacion_funcional', 50)->nullable();
            $t->string('ausentismo_evitable', 20)->nullable()->comment('NA | EVITABLE | NO_EVITABLE (solo clasificación AUSENTISMO)');
            $t->unsignedSmallInteger('orden_calculo')->nullable()->comment('Orden derivado por el motor (bases y dependencias); informativo');
            $this->auditoria($t);
            $t->timestamps();
            $this->fk($t, 'base_modulo', 'motor_bases_arca', 'codigo_base');
            $this->fk($t, 'codigo_parametro', 'motor_parametros', 'codigo');
            $this->fk($t, 'codigo_novedad_cantidad', 'motor_tipos_novedad', 'codigo', 'motor_conceptos_nov_cant_fk');
            $this->fk($t, 'codigo_novedad_importe', 'motor_tipos_novedad', 'codigo', 'motor_conceptos_nov_imp_fk');
            $this->fk($t, 'codigo_base_zonda', 'motor_bases_zonda', 'codigo_base');
        }, 'Maestro de conceptos del motor. Cada fila es un concepto único y estable del sistema');

        $this->crear('motor_categoria_concepto', function (Blueprint $t) {
            $t->id('id_categoria_concepto');
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s');
            $t->string('codigo_concepto', 50);
            $t->string('accion', 10)->default('INCLUIR')->comment('INCLUIR | EXCLUIR');
            $this->vigencia($t, 'motor_cat_concepto_vigencia_idx');
            $this->estadoValidacion($t);
            $t->string('fuente', 255)->nullable();
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['id_categoria', 'codigo_concepto', 'vigencia_desde'], 'motor_cat_concepto_unique');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto');
        }, 'Conceptos que aplican a una categoría cuando la aplicabilidad no es común a todo el convenio (nivel CATEGORIA)');

        $this->crear('motor_concepto_aplicabilidad', function (Blueprint $t) {
            $t->id('id_aplicabilidad');
            $t->string('codigo_concepto', 50);
            $t->string('tipo_ambito', 20)->comment('GENERAL | CONVENIO | EMPRESA | TRABAJADOR (CATEGORIA vive en motor_categoria_concepto)');
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s', nullable: true);
            $this->referenciaLegacy($t, 'id_empresa', 'sue086s', nullable: true);
            $t->unsignedBigInteger('relacion_laboral_id')->nullable();
            $t->string('accion', 10)->default('INCLUIR');
            $this->vigencia($t, 'motor_aplicab_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['codigo_concepto', 'vigencia_desde'], 'motor_aplicab_concepto_idx');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id');
        }, 'Reglas de aplicabilidad por ámbito con precedencia TRABAJADOR > EMPRESA > CATEGORIA > CONVENIO > GENERAL');
        $this->check('motor_concepto_aplicabilidad', 'motor_aplicab_ambito_chk',
            "(tipo_ambito = 'GENERAL' AND id_convenio IS NULL AND id_empresa IS NULL AND relacion_laboral_id IS NULL)
             OR (tipo_ambito = 'CONVENIO' AND id_convenio IS NOT NULL) OR (tipo_ambito = 'EMPRESA' AND id_empresa IS NOT NULL)
             OR (tipo_ambito = 'TRABAJADOR' AND relacion_laboral_id IS NOT NULL)");

        $this->crear('motor_base_zonda_incidencia', function (Blueprint $t) {
            $t->id('id_incidencia');
            $t->unsignedBigInteger('id_base_zonda');
            $t->string('codigo_concepto', 50);
            $t->string('incidencia', 10)->comment('S = el concepto integra la base | N = decisión explícita de exclusión');
            $this->vigencia($t, 'motor_bz_incid_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['id_base_zonda', 'codigo_concepto', 'vigencia_desde'], 'motor_bz_incid_unique');
            $this->fk($t, 'id_base_zonda', 'motor_bases_zonda', 'id_base_zonda');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto');
        }, 'Composición de cada base de Zonda: qué conceptos la integran, con vigencia (N:M)');

        $this->crear('motor_concepto_incidencia_arca', function (Blueprint $t) {
            $t->id('id_incidencia_arca');
            $t->string('codigo_concepto', 50);
            $t->unsignedBigInteger('id_subsistema_aplicacion');
            $t->string('incidencia', 10)->comment('S | N | PENDIENTE. La ausencia de fila no equivale a N');
            $this->vigencia($t, 'motor_incid_arca_vigencia_idx');
            $this->estadoValidacion($t);
            $t->text('fuente_normativa')->nullable();
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['codigo_concepto', 'id_subsistema_aplicacion', 'vigencia_desde'], 'motor_incid_arca_unique');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto');
            $this->fk($t, 'id_subsistema_aplicacion', 'motor_subsistema_aplicacion', 'id_subsistema_aplicacion');
        }, 'Fuente primaria de parametrización normativa: incidencia de cada concepto en cada aplicación ARCA válida');

        $this->crear('motor_concepto_versiones', function (Blueprint $t) {
            $t->id('id_version');
            $t->unsignedBigInteger('id_concepto');
            $t->unsignedSmallInteger('numero_version');
            $this->vigencia($t, 'motor_versiones_vigencia_idx');
            $t->unsignedTinyInteger('esquema_snapshot')->default(1);
            $t->json('snapshot')->comment('Definición congelada del concepto (campos de cálculo, incidencias, bases, aplicabilidad). El liquidador usa esto, no la fila viva');
            $t->char('hash', 64)->comment('sha256 del JSON canónico del snapshot');
            $t->string('motivo', 255)->nullable();
            $t->string('tipo_cambio', 20)->nullable()->comment('ALTA | PARITARIA | NORMATIVO | CORRECCION | CAMBIO_FUNCIONAL | OTRO');
            $t->dateTime('publicada_at')->nullable();
            $t->boolean('usada_en_liquidacion')->default(false)->comment('Una versión usada jamás se modifica (RT-006)');
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['id_concepto', 'vigencia_desde'], 'motor_versiones_concepto_vig_unique');
            $t->unique(['id_concepto', 'numero_version'], 'motor_versiones_concepto_num_unique');
            $this->fk($t, 'id_concepto', 'motor_conceptos', 'id_concepto');
        }, 'Versiones publicadas de cada concepto, por vigencia. Publicar una nueva cierra la anterior al día previo');
    }

    public function down(): void
    {
        foreach (['motor_concepto_versiones', 'motor_concepto_incidencia_arca', 'motor_base_zonda_incidencia', 'motor_concepto_aplicabilidad',
            'motor_categoria_concepto', 'motor_conceptos', 'motor_bases_zonda', 'motor_tipos_novedad'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
