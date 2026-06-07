<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la tabla legacy sue009s (catálogo de obra social antiguo).
 *
 * La obra social del legajo (sue001s.cod_obsoc) ya no la referencia: pasó a
 * apuntar a sicoss_obras en la migración 2026_06_05_230000. Ya no quedan FKs,
 * modelos (Sue009 borrado), controladores ni vistas que la usen.
 *
 * down() recrea la estructura (sin datos) para mantener reversible la cadena de
 * migraciones; los 40 registros originales no se restauran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sue009s');
    }

    public function down(): void
    {
        if (Schema::hasTable('sue009s')) {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE TABLE `sue009s` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT,
              `codigo` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
              `detalle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
              `localidad` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `cp` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `tel1` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `tel2` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `tel3` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `email` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `web` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `contacto` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
              `con_os` decimal(5,2) DEFAULT NULL,
              `apo_os` decimal(5,2) DEFAULT NULL,
              `fijo_apo` decimal(11,2) DEFAULT NULL,
              `fijo_con` decimal(11,2) DEFAULT NULL,
              `Desde_sue1` decimal(11,2) DEFAULT NULL,
              `Hasta_sue1` decimal(11,2) DEFAULT NULL,
              `por_os1` decimal(11,2) DEFAULT NULL,
              `por_ans1` decimal(11,2) DEFAULT NULL,
              `Desde_sue2` decimal(11,2) DEFAULT NULL,
              `Hasta_sue2` decimal(11,2) DEFAULT NULL,
              `por_os2` decimal(11,2) DEFAULT NULL,
              `por_ans2` decimal(11,2) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT NULL,
              `updated_at` timestamp NULL DEFAULT NULL,
              `por_has_1` decimal(11,2) DEFAULT NULL,
              `por_may_1` decimal(11,2) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `sue009s_codigo_unique` (`codigo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);
    }
};
