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
}
