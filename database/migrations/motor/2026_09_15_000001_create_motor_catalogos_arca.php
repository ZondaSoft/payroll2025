<?php

use App\Motor\Infraestructura\Persistencia\Esquema\MigracionMotor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de Cálculo — catálogos normativos ARCA sin dependencias (nivel 1).
 * Espejan las hojas BASES_ARCA, SUBSISTEMAS, ENCUADRAMIENTOS_PATRONALES,
 * CONDICIONES_ARCA, MODALIDADES_ARCA, SITUACIONES_REVISTA_ARCA, ACTIVIDADES_ARCA,
 * LOCALIDADES_ARCA y COMPONENTES_TOPE de la especificación del especialista.
 * Los códigos son de ARCA y no deben renombrarse (RT-009).
 */
return new class extends MigracionMotor
{
    public function up(): void
    {
        $this->crear('motor_bases_arca', function (Blueprint $t) {
            $t->id('id_base');
            $t->string('codigo_base', 50)->unique()->comment('REM_1 … REM_10, código oficial de la base imponible');
            $t->string('descripcion', 255);
            $t->string('sujeto_obligado', 20)->comment('TRABAJADOR | EMPLEADOR');
            $t->boolean('admite_tope')->default(false)->comment('Solo REM_1, REM_4 y REM_5 (bases de aportes) admiten tope');
            $t->boolean('activo')->default(true);
            $t->timestamps();
        }, 'Las 10 bases imponibles que exigen el LSD y el F.931');

        $this->crear('motor_subsistemas', function (Blueprint $t) {
            $t->id('id_subsistema');
            $t->string('codigo', 50)->unique()->comment('SIPA, INSSJP, AAFF, FNE, RENATRE, OS, FSR, LRT');
            $t->string('descripcion', 255);
            $t->string('modulo_responsable', 30)->comment('SEGURIDAD_SOCIAL | OBRA_SOCIAL | ART');
            $t->string('base_aportes', 50)->nullable()->comment('REM_n de aportes; NULL si el subsistema no genera aporte personal');
            $t->string('base_contribuciones', 50)->comment('REM_n de contribuciones');
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $this->fk($t, 'base_aportes', 'motor_bases_arca', 'codigo_base');
            $this->fk($t, 'base_contribuciones', 'motor_bases_arca', 'codigo_base');
        }, 'Destinos de la recaudación de la seguridad social. Ninguna empresa crea, edita ni elimina filas');

        $this->crear('motor_encuadramientos_patronales', function (Blueprint $t) {
            $t->string('encuadramiento', 50)->primary()->comment('A | B (Ley 27.541, art. 19)');
            $t->string('descripcion', 255);
            $t->text('fuente_normativa')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        }, 'Dominio cerrado de encuadramientos patronales. No guarda porcentajes: viven en alícuotas');

        $this->crear('motor_condiciones_arca', function (Blueprint $t) {
            $t->string('codigo_condicion', 50)->primary()->comment('Código de condición previsional ARCA (se informa tal cual en F.931/LSD)');
            $t->string('descripcion', 255);
            $t->string('consecuencia_general', 255)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        }, 'Condición previsional del trabajador según ARCA: selector de reglas de cálculo');

        $this->crear('motor_modalidades_arca', function (Blueprint $t) {
            $t->string('codigo_modalidad', 50)->primary()->comment('Nomenclador de modalidades de contratación ARCA (710 = RIFL)');
            $t->string('descripcion', 100);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        $this->crear('motor_situaciones_revista_arca', function (Blueprint $t) {
            $t->string('codigo_situacion', 50)->primary();
            $t->string('descripcion', 100);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        }, 'Situaciones de revista que ARCA admite informar dentro de un período');

        $this->crear('motor_actividades_arca', function (Blueprint $t) {
            $t->string('codigo_actividad_arca', 50)->primary();
            $t->string('descripcion', 100);
            $this->vigencia($t, 'motor_actividades_arca_vigencia_idx', desdeObligatoria: false);
            $t->string('estado', 50)->default('VIGENTE');
            $t->timestamps();
        }, 'Códigos de actividad ARCA. Se importan desde sicoss01s (por eso la vigencia puede ser NULL)');

        $this->crear('motor_localidades_arca', function (Blueprint $t) {
            $t->string('codigo_localidad_arca', 50)->primary()->comment('Código de localidad ARCA de 2 caracteres (sicoss_zonas.numero)');
            $t->string('descripcion', 100);
            $t->string('provincia', 100)->nullable();
            $this->vigencia($t, 'motor_localidades_arca_vigencia_idx', desdeObligatoria: false);
            $t->string('estado', 50)->default('VIGENTE');
            $t->timestamps();
        }, 'Códigos de localidad ARCA. Se importan desde sicoss_zonas');

        $this->crear('motor_componentes_tope', function (Blueprint $t) {
            $t->string('codigo_componente', 50)->primary()->comment('MENSUAL | SAC_COMPLETO | SAC_PROPORCIONAL | VACACIONES');
            $t->string('descripcion', 100);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        }, 'Componentes sobre los que se consume el cupo de un tope (cada uno con cupo propio)');
    }

    public function down(): void
    {
        foreach (['motor_componentes_tope', 'motor_localidades_arca', 'motor_actividades_arca', 'motor_situaciones_revista_arca',
            'motor_modalidades_arca', 'motor_condiciones_arca', 'motor_encuadramientos_patronales', 'motor_subsistemas', 'motor_bases_arca'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
