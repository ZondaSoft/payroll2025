<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parámetros generales del sistema: un único registro que edita el ABM /config.
        // Nace con un solo flag; los próximos parámetros se agregan como columnas en migraciones
        // sucesivas (no como filas clave/valor) para conservar el tipo y la validación por campo.
        Schema::create('sue_config', function (Blueprint $table) {
            $table->id();
            $table->boolean('lsd_no_conceptos')->default(false)
                ->comment('LSD sin conceptos: parámetro para la generación del Libro Sueldo Digital');
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Último usuario que modificó los parámetros');
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
        });

        // Registro único, para que el formulario siempre tenga datos que mostrar y editar.
        if (DB::table('sue_config')->count() === 0) {
            DB::table('sue_config')->insert([
                'lsd_no_conceptos' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sue_config');
    }
};
