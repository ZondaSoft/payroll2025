<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alineación con el modelo DBML v2 del Excel funcional v5: columnas que la
 * especificación agregó o movió de lugar sobre tablas que ya existen.
 *
 * Aditiva por diseño. Lo único que se retira son columnas de
 * motor_categoria_concepto que el v2 movió a motor_categoria_concepto_vigencia,
 * y sólo si la tabla está vacía (se verifica antes y se aborta si no).
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        // TAB_CONCEPTOS v2: clasificación funcional en dos niveles y tipo de variación,
        // que el módulo PROMEDIOS necesita para decidir qué conceptos promedia.
        Schema::table('motor_conceptos', function (Blueprint $t) {
            $t->string('subtipo_funcional', 60)->nullable()->after('clasificacion_funcional')
                ->comment('Subtipo dentro de la clasificación funcional (DBML v2)');
            $t->string('tipo_variacion', 20)->nullable()->after('subtipo_funcional')
                ->comment('FIJO | VARIABLE | NO_APLICA. NULL = sin clasificar: PROMEDIOS.VARIABLE_6M bloquea');
        });

        // TOPES_MINIMOS v2: el tope máximo se prorratea por días cuando la relación
        // no cubre el mes completo (auditoría ARCA 2026-09-25).
        Schema::table('motor_topes_minimos', function (Blueprint $t) {
            $t->boolean('proporcional_dias')->default(false)->after('importe')
                ->comment('S en la fuente: el tope se prorratea por días liquidados');
        });

        // REGIMENES_CONTRIBUTIVOS v2: tipo_regla admite SUSTITUCION_SUBSISTEMA
        // (RENATRE reemplaza FNE) y se declara cuál subsistema reemplaza.
        Schema::table('motor_regimenes_contributivos', function (Blueprint $t) {
            $t->string('reemplaza_subsistema', 30)->nullable()->after('destino')
                ->comment('Subsistema que el régimen reemplaza (SUSTITUCION_SUBSISTEMA)');
        });

        // CONCEPTO_VERSION v2: la versión deja de ser sólo un snapshot de auditoría y
        // pasa a ser la definición ejecutable — variante + método + expresiones DSL.
        // Clave natural (codigo_concepto, variante, vigencia_desde).
        Schema::table('motor_concepto_versiones', function (Blueprint $t) {
            $t->string('variante', 30)->default('UNICA')->after('id_concepto')
                ->comment('Variante de fórmula que elige el perfil. UNICA si el concepto no tiene variantes');
            $t->string('metodo', 10)->default('FORMULA')->after('variante')
                ->comment('FORMULA | MODULO (DSL v1.1 §2)');
            $t->text('expr_condicion')->nullable()->after('metodo')->comment('Expresión DSL: condición de aplicación');
            $t->text('expr_cantidad')->nullable()->after('expr_condicion')->comment('Expresión DSL: cantidad');
            $t->text('expr_valor_unitario')->nullable()->after('expr_cantidad')->comment('Expresión DSL: valor unitario');
            $t->text('expr_importe')->nullable()->after('expr_valor_unitario')->comment('Expresión DSL: importe (debe resultar ARS)');
            $t->string('unidad_cantidad', 20)->nullable()->after('expr_importe')->comment('ARS | HORAS | DIAS | KM | UNIDADES');
            $t->string('regla_redondeo_version', 20)->nullable()->after('unidad_cantidad')
                ->comment('Redondeo propio de la versión: SIN_REDONDEO | DECIMAL | ENTERO');
            $t->unsignedTinyInteger('decimales')->nullable()->after('regla_redondeo_version');
            $t->string('codigo_modulo', 60)->nullable()->after('decimales')->comment('Módulo responsable cuando metodo = MODULO');
            $t->string('estado', 12)->default('PUBLICADA')->after('codigo_modulo')
                ->comment('BORRADOR | PUBLICADA | ANULADA. Sólo PUBLICADA se ejecuta y es inmutable');
            $t->string('estado_validacion', 40)->nullable()->after('estado');
            $t->text('fuente_normativa')->nullable()->after('estado_validacion');
            $t->index(['estado', 'vigencia_desde'], 'motor_cv_estado_vigencia_idx');
        });

        // La unicidad pasa a incluir la variante: BASICO tiene JORNADA_PACTADA y
        // LARGA_DISTANCIA vigentes a la vez, con la misma fecha de inicio.
        $this->reemplazarUnico(
            'motor_concepto_versiones',
            'motor_concepto_versiones_id_concepto_vigencia_desde_unique',
            ['id_concepto', 'variante', 'vigencia_desde'],
            'motor_cv_concepto_variante_desde_uq'
        );

        // CATEGORIA_CONCEPTO v2: queda sólo la definición del valor categórico
        // (VALCAT). La aplicabilidad se fue a PERFIL_CONCEPTO y las vigencias a
        // CATEGORIA_CONCEPTO_VIGENCIA.
        Schema::table('motor_categoria_concepto', function (Blueprint $t) {
            $t->string('tipo_valor', 20)->nullable()->after('codigo_concepto')->comment('CANTIDAD | PORCENTAJE | IMPORTE');
            $t->string('unidad_valor', 20)->nullable()->after('tipo_valor')->comment('HORAS | PORCENTAJE | ARS | UNIDADES');
            $t->boolean('activo')->default(true)->after('unidad_valor');
        });

        $filas = DB::table('motor_categoria_concepto')->count();
        if ($filas === 0) {
            Schema::table('motor_categoria_concepto', function (Blueprint $t) {
                $t->dropColumn(['accion', 'vigencia_desde', 'vigencia_hasta', 'fuente']);
            });
        } else {
            // No se descarta información cargada: las columnas quedan y el seed las ignora.
            DB::statement('SELECT 1');
        }
    }

    /** Cambia un índice único por otro, tolerando que el original ya no exista. */
    private function reemplazarUnico(string $tabla, string $viejo, array $columnas, string $nuevo): void
    {
        if (! $this->esMysql()) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unique($columnas, $nuevo));

            return;
        }
        $existe = collect(DB::select("SHOW INDEX FROM `{$tabla}`"))->pluck('Key_name')->contains($viejo);
        if ($existe) {
            DB::statement("ALTER TABLE `{$tabla}` DROP INDEX `{$viejo}`");
        }
        $cols = implode('`, `', $columnas);
        DB::statement("ALTER TABLE `{$tabla}` ADD UNIQUE `{$nuevo}` (`{$cols}`)");
    }

    public function down(): void
    {
        // Sin reversa: las migraciones del motor no se revierten (ver docs/motor/operacion.md).
    }
};
