<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas que payroll2025 USA pero NO CREA: en sus migraciones, los Schema::create de
 * `users`, `password_reset_tokens`, `jobs` y `failed_jobs` están comentados porque las
 * crea el portal Drakma sobre la base compartida `swnov` (igual que estaba comentado el
 * de `roles`). Para tener una base de tests aislada hay que crearlas acá, con las
 * columnas que usan el modelo User y su factory.
 */
final class EsquemaAppMinimo
{
    public static function crearTablasCompartidas(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('profile_photo')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->rememberToken();
            $t->timestamps();
            $t->string('rol')->nullable();
            $t->integer('legajo')->nullable();
            $t->string('cuil', 13)->nullable();
            $t->string('num_doc', 20)->nullable();
            $t->boolean('password_change_required')->default(false);
            $t->boolean('es_admin')->default(false);
            $t->timestamp('last_portal_login_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
    }

    /**
     * Las tres tablas del catálogo de liquidación que intervienen en el alta de
     * conceptos del LSD: el catálogo, los rangos que deciden el tipo y la
     * parametrización ARCA de la que sale el código AFIP.
     *
     * Las crea el portal, igual que `users`: en este repo sus Schema::create están
     * comentados. Sólo se declaran las columnas que el alta lee o escribe.
     */
    public static function crearCatalogoLiquidacion(): void
    {
        Schema::create('sue102s', function (Blueprint $t) {
            $t->id();
            $t->integer('codigo')->unique();
            $t->string('detalle', 250)->nullable();
            $t->string('tipo', 4)->nullable();
            $t->string('concepto_arca', 6)->nullable();
            $t->timestamps();
        });

        Schema::create('sue089s', function (Blueprint $t) {
            $t->id();
            $t->integer('desde');
            $t->integer('hasta');
            $t->string('tiporem', 4)->nullable();
            $t->timestamps();
        });

        Schema::create('conceptosarcas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('id_empresa')->nullable();
            $t->integer('codigo_contribuyente')->nullable();
            $t->integer('codigo_afip')->nullable();
            $t->string('descripcion', 255)->nullable();
            $t->timestamps();
        });
    }

    /**
     * Lo mínimo de una liquidación para los pre-checks del LSD que corren antes de armar el TXT:
     * empresa, período, legajo y el detalle liquidado. También las crea el portal; sólo se
     * declaran las columnas que esos controles leen.
     */
    public static function crearLiquidacion(): void
    {
        Schema::create('sue086s', function (Blueprint $t) {
            $t->id();
            $t->integer('codigo')->nullable();
            $t->string('detalle', 100)->nullable();
            $t->string('cuit', 20)->nullable();
            $t->timestamps();
        });

        Schema::create('sue100s', function (Blueprint $t) {
            $t->id();
            $t->string('periodo', 6);
            $t->integer('tipoliq')->nullable();
            $t->timestamps();
        });

        Schema::create('sue001s', function (Blueprint $t) {
            $t->id();
            $t->integer('codigo');
            $t->string('detalle', 100)->nullable();
            $t->string('nombres', 100)->nullable();
            $t->string('cuil', 20)->nullable();
            $t->integer('grupo_emp')->nullable();
            $t->date('baja')->nullable();
            $t->timestamps();
        });

        Schema::create('sue090s', function (Blueprint $t) {
            $t->id();
            $t->string('periodo', 6)->nullable();
            $t->string('tipoliq', 6)->nullable();
            $t->integer('legajo')->nullable();
            $t->string('concepto', 6)->nullable();
            $t->string('descripcion', 50)->nullable();
            $t->decimal('cantidad', 10, 2)->nullable();
            $t->decimal('importe', 10, 2)->nullable();
            $t->timestamps();
        });
    }
}
