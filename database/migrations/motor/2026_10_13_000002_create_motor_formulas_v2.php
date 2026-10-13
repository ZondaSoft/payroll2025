<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;

/**
 * Bloque Fórmulas del modelo DBML v2: variables del sistema, novedades y
 * literales declarados por versión, perfiles de aplicabilidad, módulos y los
 * valores categóricos (VALCAT) con su vigencia.
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        // VAR(COD) — códigos de hasta 7 caracteres (DSL v1.1 §4).
        $this->crear('motor_variables', function (Blueprint $t) {
            $t->string('codigo', 7)->primary()->comment('Código que las fórmulas leen con VAR(COD)');
            $t->string('descripcion', 150);
            $t->string('unidad', 20)->comment('ARS | HORAS | DIAS | KM | UNIDADES | NUMERIC');
            $t->string('origen', 30)->comment('CATEGORIA | RELACION_LABORAL | CONVENIO | JORNADA | PERIODO: quién la resuelve');
            $t->boolean('activo')->default(true);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
        }, 'Variables del sistema que las fórmulas leen con VAR(). Sólo DIVJOR guarda valores; el resto las resuelve el adaptador');

        // Sólo las variables de alcance CONVENIO/CATEGORIA/TRABAJADOR guardan valor
        // (hoy únicamente DIVJOR: 25 en CCT 371/03 y 24 en CCT 40/89).
        $this->crear('motor_valores_variable', function (Blueprint $t) {
            $t->id('id_valor_variable');
            $t->string('codigo', 7);
            $t->string('alcance', 20)->comment('GLOBAL | CONVENIO | CATEGORIA | TRABAJADOR');
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s', true);
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s', true);
            $t->unsignedBigInteger('relacion_laboral_id')->nullable();
            $t->decimal('valor', 18, 6);
            $this->vigencia($t, 'motor_valvar_vigencia_idx');
            $this->estadoValidacion($t);
            $t->string('fuente', 255)->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['codigo', 'alcance', 'vigencia_desde'], 'motor_valvar_codigo_alcance_idx');
            $this->fk($t, 'codigo', 'motor_variables', 'codigo', 'motor_valvar_codigo_fk');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id', 'motor_valvar_rl_fk');
        }, 'Valores versionados de VAR() por alcance y vigencia (DIVJOR, P-10-b)');

        // Las novedades que consume cada versión. La obligatoriedad es de la
        // versión, no del concepto: REQUERIDA ausente => NO_APLICA; OPCIONAL => cero tipado.
        $this->crear('motor_concepto_version_novedad', function (Blueprint $t) {
            $t->unsignedBigInteger('id_version');
            $t->string('codigo_novedad', 50);
            $t->string('obligatoriedad', 12)->comment('REQUERIDA | OPCIONAL');
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->primary(['id_version', 'codigo_novedad'], 'motor_cvn_pk');
            $this->fk($t, 'id_version', 'motor_concepto_versiones', 'id_version', 'motor_cvn_version_fk');
            $this->fk($t, 'codigo_novedad', 'motor_tipos_novedad', 'codigo', 'motor_cvn_novedad_fk');
        }, 'Novedades declaradas por versión de fórmula: una fórmula que usa NOV() sin declararlo no publica');

        // Literales distintos de 0/1 requieren justificación para publicar (DSL §15).
        $this->crear('motor_concepto_version_literal', function (Blueprint $t) {
            $t->id('id_literal');
            $t->unsignedBigInteger('id_version');
            $t->string('literal', 30);
            $t->string('justificacion', 255);
            $t->timestamps();
            $t->unique(['id_version', 'literal'], 'motor_cvl_version_literal_uq');
            $this->fk($t, 'id_version', 'motor_concepto_versiones', 'id_version', 'motor_cvl_version_fk');
        }, 'Justificación de los literales de cada versión (DSL §15)');

        // PERFIL: precedencia TRABAJADOR(1) → EMPRESA(2) → CATEGORIA(3) → CONVENIO(4) → GENERAL(5).
        // Se completa sólo la columna del nivel propio.
        $this->crear('motor_perfil', function (Blueprint $t) {
            $t->id('id_perfil');
            $t->string('codigo_perfil', 50)->unique();
            $t->string('nombre', 150);
            $t->string('nivel', 20)->comment('TRABAJADOR | EMPRESA | CATEGORIA | CONVENIO | GENERAL');
            $t->unsignedTinyInteger('orden_precedencia')->comment('1 TRABAJADOR … 5 GENERAL. Gana la primera regla encontrada');
            $t->unsignedBigInteger('relacion_laboral_id')->nullable();
            $this->referenciaLegacy($t, 'empresa_id', 'sue086s', true);
            $this->referenciaLegacy($t, 'id_categoria', 'sue006s', true);
            $this->referenciaLegacy($t, 'id_convenio', 'sue007s', true);
            $this->vigencia($t, 'motor_perfil_vigencia_idx');
            $t->boolean('activo')->default(true);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->index(['nivel', 'orden_precedencia'], 'motor_perfil_nivel_idx');
            $this->fk($t, 'relacion_laboral_id', 'motor_relacion_laboral', 'relacion_laboral_id', 'motor_perfil_rl_fk');
        }, 'Perfiles de liquidación. Dos reglas del mismo nivel para un concepto bloquean con PERFIL_AMBIGUO');

        // Qué conceptos aplican en cada perfil y con qué variante. aplica y variante
        // se toman juntas de la regla ganadora; sin variante => ERROR_CONFIGURACION.
        $this->crear('motor_perfil_concepto', function (Blueprint $t) {
            $t->id('id_perfil_concepto');
            $t->unsignedBigInteger('id_perfil');
            $t->string('codigo_concepto', 50);
            $t->boolean('aplica')->comment('FALSE explícito produce NO_APLICA con su motivo');
            $t->string('variante', 30)->nullable()->comment('Obligatoria cuando aplica = TRUE (UNICA si el concepto no tiene variantes)');
            $t->string('motivo', 255)->nullable()->comment('Explica la exclusión; se copia al resultado');
            $this->vigencia($t, 'motor_pc_vigencia_idx');
            $this->estadoValidacion($t);
            $t->timestamps();
            $t->unique(['id_perfil', 'codigo_concepto', 'vigencia_desde'], 'motor_pc_perfil_concepto_uq');
            $this->fk($t, 'id_perfil', 'motor_perfil', 'id_perfil', 'motor_pc_perfil_fk');
            $this->fk($t, 'codigo_concepto', 'motor_conceptos', 'codigo_concepto', 'motor_pc_concepto_fk');
        }, 'Aplicabilidad y variante de fórmula por perfil (F-3)');

        // Punto de extensión del motor: la mecánica nueva entra como módulo y no
        // como cambio del DSL (§16).
        $this->crear('motor_modulos', function (Blueprint $t) {
            $t->string('codigo', 60)->primary()->comment('Código que las fórmulas leen con MOD(COD.RESULTADO)');
            $t->string('descripcion', 255);
            $t->string('unidad_resultado', 20)->comment('PORCENTAJE | ARS | DIAS | HORAS | NUMERIC');
            $t->string('version', 10)->default('v1');
            $t->boolean('requiere_historico')->default(false);
            $t->unsignedTinyInteger('meses_historico')->nullable()
                ->comment('Períodos que necesita. Sin historia suficiente bloquea con MOD_HISTORICO_INSUFICIENTE');
            $t->boolean('activo')->default(true);
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
        }, 'Módulos del motor: resolvers tipados invocados con MOD()');

        $this->crear('motor_modulo_dependencia', function (Blueprint $t) {
            $t->id('id_modulo_dependencia');
            $t->string('codigo_modulo', 60);
            $t->string('tipo', 20)->comment('VAR | CONC | BASE | HISTORICO: participan del grafo');
            $t->string('codigo_referencia', 60);
            $t->timestamps();
            $t->unique(['codigo_modulo', 'tipo', 'codigo_referencia'], 'motor_md_modulo_ref_uq');
            $this->fk($t, 'codigo_modulo', 'motor_modulos', 'codigo', 'motor_md_modulo_fk');
        }, 'Dependencias declaradas por módulo (entran al grafo de orden de cálculo)');

        // VALCAT con vigencia: el valor categórico cambia en el tiempo, la
        // definición (tipo y unidad) no.
        $this->crear('motor_categoria_concepto_vigencia', function (Blueprint $t) {
            $t->id('id_categoria_concepto_vigencia');
            $t->unsignedBigInteger('id_categoria_concepto');
            $t->decimal('valor', 18, 6);
            $this->vigencia($t, 'motor_ccv_vigencia_idx');
            $this->estadoValidacion($t);
            $t->string('fuente', 255)->nullable();
            $t->text('observaciones')->nullable();
            $this->auditoria($t);
            $t->timestamps();
            $t->unique(['id_categoria_concepto', 'vigencia_desde'], 'motor_ccv_cc_desde_uq');
            $this->fk($t, 'id_categoria_concepto', 'motor_categoria_concepto', 'id_categoria_concepto', 'motor_ccv_cc_fk');
        }, 'Valores de VALCAT() por categoría y vigencia (F-1)');
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten.
    }
};
