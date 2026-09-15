<?php

namespace Tests\Support\Motor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea, en la conexión de tests (sqlite :memory:), las tablas legacy de Zonda
 * Gestión con SOLO las columnas que leen los adaptadores del motor. Permite
 * probar adaptadores y sincronización sin tocar jamás la base compartida `swnov`.
 *
 * Las 54 migraciones legacy del sistema no corren en sqlite (ya fallan hoy en
 * create_permission_tables), por eso este esquema mínimo existe.
 */
final class EsquemaLegacyMinimo
{
    /** Compartido por TODAS las clases de test del proceso: el esquema se levanta una sola vez. */
    public static bool $preparado = false;

    public static function crear(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->timestamps();
        });

        Schema::create('sue086s', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 2)->unique();
            $t->string('detalle', 40);
            $t->string('cuit', 100)->nullable();
            $t->timestamps();
        });

        Schema::create('sue007s', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 5)->unique();
            $t->string('detalle', 30);
            $t->integer('hs_normales_semanales')->nullable();
            $t->timestamps();
        });

        Schema::create('sue006s', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 4)->unique();
            $t->string('detalle', 100);
            $t->string('cod_conve', 5)->nullable();
            $t->timestamps();
        });

        Schema::create('sue010s', function (Blueprint $t) {
            $t->id();
            $t->string('detalle', 150);
            $t->integer('horas_semana')->nullable();
            $t->boolean('parcial')->default(false);
            $t->timestamps();
        });

        Schema::create('sue001s', function (Blueprint $t) {
            $t->id();
            $t->integer('codigo')->unique();
            $t->string('cuil', 13)->nullable();
            $t->string('detalle', 30)->nullable();
            $t->string('nombres', 30)->nullable();
            $t->date('alta')->nullable();
            $t->date('baja')->nullable();
            $t->date('fecha_antiguedad_reconocida')->nullable();
            $t->string('cod_categ', 4)->nullable();
            $t->string('convenio', 5)->nullable();
            $t->unsignedBigInteger('jornada_id')->nullable();
            $t->string('grupo_emp', 2)->nullable();
            $t->string('cod_sindic', 2)->nullable();
            $t->integer('sicoss_activ')->nullable();
            $t->integer('sicoss_condi')->nullable();
            $t->integer('sicoss_modal')->nullable();
            $t->integer('sicoss_situa')->nullable();
            $t->integer('sicoss_zona')->nullable();
            $t->decimal('bruto', 10, 2)->nullable();
            $t->timestamps();
        });

        Schema::create('sue031s', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 6)->unique();
            $t->string('detalle', 40);
            $t->string('tipo', 50)->nullable();
            $t->string('cod_sue', 5)->nullable();
            $t->boolean('azul')->nullable();
            $t->boolean('activo')->default(false);
            $t->timestamps();
        });

        Schema::create('sue028s', function (Blueprint $t) {
            $t->id();
            $t->date('fecha')->nullable();
            $t->integer('legajo')->nullable()->index();
            $t->string('cod_nov', 6)->nullable()->index();
            $t->decimal('cantidad', 11, 2)->default(0);
            $t->string('periodo', 6)->nullable();
            $t->integer('int_enf')->default(0);
            $t->integer('int_acc')->default(0);
            $t->integer('int_susp')->default(0);
            $t->integer('int_aperc')->default(0);
            $t->unsignedInteger('int_licencias')->nullable();
            $t->integer('int_vac')->default(0);
            $t->timestamps();
        });

        foreach (['sicoss01s', 'sicoss05s', 'sicoss08s', 'sicoss12s'] as $tabla) {
            Schema::create($tabla, function (Blueprint $t) {
                $t->id();
                $t->integer('codigo')->unique();
                $t->string('detalle', 255);
                $t->timestamps();
            });
        }

        Schema::create('sicoss_zonas', function (Blueprint $t) {
            $t->id();
            $t->integer('codigo')->unique();
            $t->string('numero', 2);
            $t->string('detalle', 255);
            $t->timestamps();
        });

        Schema::create('conceptosarcas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_empresa')->nullable();
            $t->integer('codigo_afip')->nullable();
            $t->string('descripcion', 80)->nullable();
            $t->integer('codigo_contribuyente')->nullable();
            $t->string('descripcion_contribuyente', 80)->nullable();
            foreach (['aportes_sipa', 'contribuciones_sipa', 'aportes_inssjyp', 'contribuciones_inssjyp', 'aportes_obra_social',
                'contribuciones_obra_social', 'aportes_fsr', 'contribuciones_fsr', 'aportes_renatea', 'contribuciones_renatea',
                'contribuciones_aaff', 'contribuciones_fne', 'contribuciones_lrt', 'aportes_diferenciales', 'aportes_especiales'] as $marca) {
                $t->decimal($marca, 9, 3)->nullable();
            }
            $t->timestamps();
        });
        // Tablas de spatie/laravel-permission: el layout `legajos` (que renderizan las
        // pantallas del motor) usa @can en el menú, y sin ellas la vista revienta con 500.
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
            $t->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
            $t->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->unsignedBigInteger('role_id');
            $t->primary(['permission_id', 'role_id']);
        });
    }
}
