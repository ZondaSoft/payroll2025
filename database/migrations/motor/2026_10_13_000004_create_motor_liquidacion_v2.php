<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;

/**
 * Persistencia de resultados (E-6…E-9 del modelo DBML v2). Hasta la Etapa 3A
 * estaba fuera de alcance por RT-012; la auditoría del 2026-09-25 la especifica
 * y la incluye en el gate LISTO_DESARROLLO.
 *
 * Principio de la especificación: lo liquidado se congela. La versión de
 * fórmula, la alícuota y el tope aplicados se guardan en la fila, no se
 * vuelven a resolver al reimprimir un recibo. Los importes cero también se
 * persisten (un concepto que dio cero no es lo mismo que uno que no aplicó).
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_liquidacion', function (Blueprint $t) {
            $t->id('id_liquidacion');
            $this->referenciaLegacy($t, 'empresa_id', 'sue086s');
            $t->char('periodo', 7)->comment('YYYY-MM');
            $t->string('tipo_liquidacion', 20)->comment('NORMAL | SAC | FINAL | VACACIONES');
            $t->string('detalle', 150)->nullable();
            $t->unsignedBigInteger('id_liquidacion_origen')->nullable()
                ->comment('Liquidación de la que deriva (reliquidación o ajuste)');
            $t->string('estado', 20)->default('CALCULADA')->comment('CALCULADA | CERRADA | ANULADA');
            $t->string('version_motor', 30)->nullable()->comment('Versión del motor que la calculó');
            $t->dateTime('fecha_calculo', 6);
            $t->unsignedBigInteger('usuario_calculo')->nullable();
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->index(['empresa_id', 'periodo', 'tipo_liquidacion'], 'motor_liq_empresa_periodo_idx');
            $t->foreign('id_liquidacion_origen')->references('id_liquidacion')->on('motor_liquidacion')
                ->onDelete('restrict')->onUpdate('restrict');
            $t->foreign('usuario_calculo')->references('id')->on('users')->onDelete('set null');
        }, 'Cabecera de una corrida de liquidación');

        $this->crear('motor_liquidacion_relacion', function (Blueprint $t) {
            $t->id('id_liquidacion_relacion');
            $t->unsignedBigInteger('id_liquidacion');
            $t->unsignedBigInteger('relacion_laboral_id');
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s', true);
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s', true);
            $t->string('codigo_perfil', 50)->nullable()->comment('Perfil que ganó la precedencia');
            $t->string('encuadramiento', 2)->nullable()->comment('A | B vigente del empleador');
            $t->string('codigo_condicion', 10)->nullable()->comment('Condición ARCA del trabajador');
            $t->unsignedSmallInteger('dias_vinculo')->nullable();
            $t->unsignedSmallInteger('dias_liquidados')->nullable();
            foreach (['total_remunerativo', 'total_no_remunerativo', 'total_aportes', 'total_deducciones', 'total_contribuciones', 'neto'] as $total) {
                $t->decimal($total, 18, 6)->default(0);
            }
            $t->string('estado', 20)->comment('CALCULADA | BLOQUEADA');
            $t->unsignedSmallInteger('cantidad_bloqueos')->default(0);
            $t->timestamps();
            $t->unique(['id_liquidacion', 'relacion_laboral_id'], 'motor_lr_liq_rl_uq');
            $this->fk($t, 'id_liquidacion', 'motor_liquidacion', 'id_liquidacion', 'motor_lr_liq_fk');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id', 'motor_lr_rl_fk');
        }, 'Renglón por relación liquidada, con sus subtotales (E-8)');

        $this->crear('motor_liquidacion_concepto', function (Blueprint $t) {
            $t->id('id_liquidacion_concepto');
            $t->unsignedBigInteger('id_liquidacion_relacion');
            $t->string('codigo_concepto', 50);
            $t->unsignedBigInteger('id_version')->nullable()->comment('Versión de fórmula con la que se calculó (congelada)');
            $t->unsignedSmallInteger('ocurrencia')->default(1)->comment('Conceptos repetibles: 1..n en el mismo recibo');
            $t->unsignedInteger('orden_calculo')->nullable()->comment('Orden que resolvió el grafo, no un número manual');
            $t->decimal('cantidad', 18, 6)->nullable();
            $t->string('unidad_cantidad', 20)->nullable();
            $t->decimal('valor_unitario', 18, 6)->nullable();
            $t->decimal('importe', 18, 6)->default(0)->comment('Se persiste aunque sea cero');
            $t->string('naturaleza', 20)->nullable();
            $t->string('tipo_variacion', 20)->nullable();
            $t->string('tipo_remuneracion', 10)->nullable()->comment('sue | adi | hse | nre | pre | red (LSD). NULL hasta clasificar');
            $t->string('signo', 10)->nullable()->comment('CREDITO | DEBITO | NEUTRO');
            $t->boolean('impacta_neto')->default(true);
            $t->string('estado', 20)->comment('CALCULADO | NO_APLICA | BLOQUEADO | NO_MODELADO');
            $t->string('codigo_error', 40)->nullable()->comment('VAR_SIN_VALOR, PARAM_SIN_VALOR, DIVISION_CERO, ERROR_CONFIGURACION…');
            $t->text('detalle_error')->nullable();
            $t->string('motivo_no_aplica', 255)->nullable()->comment('Copiado de la regla de perfil que lo excluyó');
            $t->boolean('imprime_recibo')->default(true);
            $t->unsignedInteger('orden_visual')->nullable();
            $t->timestamps();
            $t->unique(['id_liquidacion_relacion', 'codigo_concepto', 'ocurrencia'], 'motor_lc_rel_concepto_uq');
            $t->index('estado', 'motor_lc_estado_idx');
            $this->fk($t, 'id_liquidacion_relacion', 'motor_liquidacion_relacion', 'id_liquidacion_relacion', 'motor_lc_rel_fk');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto', 'motor_lc_concepto_fk');
            $this->fk($t, 'id_version', 'motor_concepto_versiones', 'id_version', 'motor_lc_version_fk');
        }, 'Resultado por concepto, con versión y estado; persiste importes cero (E-6, E-7)');

        $this->crear('motor_liquidacion_base_arca', function (Blueprint $t) {
            $t->id('id_liquidacion_base');
            $t->unsignedBigInteger('id_liquidacion_relacion');
            $t->string('codigo_base', 10);
            $t->string('origen_calculo', 30)->nullable()->comment('MATRIZ | DERIVADA | DIFERENCIAL: cómo se obtuvo');
            $t->decimal('importe_bruto', 18, 6)->default(0);
            $t->decimal('importe_diferencial', 18, 6)->default(0);
            $t->decimal('importe_ajustes', 18, 6)->default(0);
            $t->decimal('importe_final', 18, 6)->default(0);
            $t->unsignedBigInteger('id_tope_aplicado')->nullable();
            $t->string('tipo_tope_aplicado', 10)->nullable()->comment('MAXIMO | MINIMO');
            $t->decimal('importe_tope', 18, 6)->nullable()->comment('Tope efectivo, ya prorrateado por días si correspondía');
            $t->text('detalle_derivacion')->nullable()->comment('Para REM_10 y las bases derivadas: de dónde sale el número');
            $t->string('estado', 20)->comment('CALCULADA | BLOQUEADA');
            $t->string('codigo_error', 40)->nullable();
            $t->timestamps();
            $t->unique(['id_liquidacion_relacion', 'codigo_base'], 'motor_lba_rel_base_uq');
            $this->fk($t, 'id_liquidacion_relacion', 'motor_liquidacion_relacion', 'id_liquidacion_relacion', 'motor_lba_rel_fk');
            $this->fk($t, 'codigo_base', 'motor_bases_arca', 'codigo_base', 'motor_lba_base_fk');
            $this->fk($t, 'id_tope_aplicado', 'motor_topes_minimos', 'id_tope', 'motor_lba_tope_fk');
        }, 'Bases REM_n brutas, diferenciales, topes y finales (E-9)');

        $this->crear('motor_liquidacion_base_arca_aplicacion', function (Blueprint $t) {
            $t->id('id_liquidacion_base_aplicacion');
            $t->unsignedBigInteger('id_liquidacion_base');
            $t->unsignedBigInteger('id_subsistema_aplicacion');
            $t->timestamps();
            $t->unique(['id_liquidacion_base', 'id_subsistema_aplicacion'], 'motor_lbaa_base_aplic_uq');
            $this->fk($t, 'id_liquidacion_base', 'motor_liquidacion_base_arca', 'id_liquidacion_base', 'motor_lbaa_base_fk');
            $this->fk($t, 'id_subsistema_aplicacion', 'motor_subsistema_aplicacion', 'id_subsistema_aplicacion', 'motor_lbaa_aplic_fk');
        }, 'Puente REM_n → aplicaciones de subsistema que la usan');

        $this->crear('motor_liquidacion_base_arca_detalle', function (Blueprint $t) {
            $t->id('id_liquidacion_base_detalle');
            $t->unsignedBigInteger('id_liquidacion_base');
            $t->unsignedBigInteger('id_liquidacion_concepto')->nullable();
            $t->unsignedInteger('orden_detalle')->nullable();
            $t->string('codigo_concepto', 50);
            $t->string('incidencia', 12)->comment('S | N | PENDIENTE con la que se resolvió');
            $t->decimal('importe_concepto', 18, 6)->default(0);
            $t->decimal('importe_incluido', 18, 6)->default(0)->comment('Lo que efectivamente sumó a la base');
            $t->timestamps();
            $t->index('id_liquidacion_base', 'motor_lbad_base_idx');
            $this->fk($t, 'id_liquidacion_base', 'motor_liquidacion_base_arca', 'id_liquidacion_base', 'motor_lbad_base_fk');
            $this->fk($t, 'id_liquidacion_concepto', 'motor_liquidacion_concepto', 'id_liquidacion_concepto', 'motor_lbad_concepto_fk');
        }, 'Detalle congelado de la composición de cada REM_n: qué concepto entró y por cuánto');

        $this->crear('motor_liquidacion_base_arca_diferencial', function (Blueprint $t) {
            $t->id('id_liquidacion_base_diferencial');
            $t->unsignedBigInteger('id_liquidacion_relacion');
            $t->string('codigo_base', 10);
            $t->string('tipo_diferencial', 20)->comment('APORTE_OS | CONTRIB_OS');
            $t->decimal('importe', 18, 6)->default(0);
            $t->string('origen_regla', 60)->nullable()->comment('Regla de base diferencial que lo generó (BD-*)');
            $t->text('fuente_normativa')->nullable();
            $t->string('estado', 20)->comment('CALCULADA | BLOQUEADA');
            $t->string('codigo_error', 40)->nullable();
            $t->timestamps();
            $t->index(['id_liquidacion_relacion', 'codigo_base'], 'motor_lbadif_rel_base_idx');
            $this->fk($t, 'id_liquidacion_relacion', 'motor_liquidacion_relacion', 'id_liquidacion_relacion', 'motor_lbadif_rel_fk');
            $this->fk($t, 'codigo_base', 'motor_bases_arca', 'codigo_base', 'motor_lbadif_base_fk');
        }, 'Diferenciales de base ajenos a la matriz de conceptos (jornada parcial de OS)');

        $this->crear('motor_liquidacion_seg_social', function (Blueprint $t) {
            $t->id('id_liquidacion_seg_social');
            $t->unsignedBigInteger('id_liquidacion_relacion');
            $t->string('sujeto_obligado', 20)->comment('APORTE | CONTRIBUCION, tal como lo nombra la fuente');
            $t->unsignedBigInteger('id_subsistema');
            $t->unsignedBigInteger('id_subsistema_aplicacion')->nullable();
            $t->unsignedBigInteger('id_liquidacion_base')->nullable();
            $t->decimal('importe_base', 18, 6)->default(0);
            $t->string('origen_regla', 60)->nullable()->comment('ALICUOTA:<codigo> o REGIMEN:<codigo>: de dónde salió el porcentaje');
            $t->unsignedBigInteger('id_alicuota_aporte')->nullable();
            $t->unsignedBigInteger('id_alicuota_contribucion')->nullable();
            $t->decimal('porcentaje', 12, 6)->nullable()->comment('Alícuota congelada al momento del cálculo');
            $t->decimal('importe_calculado', 18, 6)->default(0);
            $t->unsignedBigInteger('id_regimen')->nullable();
            $t->decimal('importe_reduccion', 18, 6)->default(0);
            $t->decimal('importe', 18, 6)->default(0)->comment('Importe final tras régimen y reducciones');
            $t->unsignedBigInteger('id_liquidacion_concepto')->nullable()->comment('Línea del recibo que lo representa, si hay');
            $t->string('estado', 20)->comment('CALCULADA | BLOQUEADA');
            $t->string('codigo_error', 40)->nullable();
            $t->timestamps();
            $t->index(['id_liquidacion_relacion', 'sujeto_obligado'], 'motor_lss_rel_sujeto_idx');
            $this->fk($t, 'id_liquidacion_relacion', 'motor_liquidacion_relacion', 'id_liquidacion_relacion', 'motor_lss_rel_fk');
            $this->fk($t, 'id_subsistema', 'motor_subsistemas', 'id_subsistema', 'motor_lss_subsistema_fk');
            $this->fk($t, 'id_subsistema_aplicacion', 'motor_subsistema_aplicacion', 'id_subsistema_aplicacion', 'motor_lss_aplic_fk');
            $this->fk($t, 'id_liquidacion_base', 'motor_liquidacion_base_arca', 'id_liquidacion_base', 'motor_lss_base_fk');
            $this->fk($t, 'id_alicuota_aporte', 'motor_alicuotas_aportes', 'id_alicuota_aporte', 'motor_lss_alic_ap_fk');
            $this->fk($t, 'id_alicuota_contribucion', 'motor_alicuotas_contribuciones', 'id_alicuota', 'motor_lss_alic_co_fk');
            $this->fk($t, 'id_regimen', 'motor_regimenes_contributivos', 'id_regimen', 'motor_lss_regimen_fk');
            $this->fk($t, 'id_liquidacion_concepto', 'motor_liquidacion_concepto', 'id_liquidacion_concepto', 'motor_lss_concepto_fk');
        }, 'Aportes y contribuciones con alícuota y régimen congelados (E-9)');
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
