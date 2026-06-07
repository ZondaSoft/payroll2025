<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lsd_importes_detraer', function (Blueprint $table) {
            $table->id();
            $table->char('periodo_desde', 6)->unique()->comment('Período YYYYMM desde el que aplica el importe');
            $table->decimal('importe', 13, 2)->comment('Monto en pesos. Ej: 7003.68 = $7.003,68');
            $table->string('normativa', 255)->nullable()->comment('Referencia legal: ej. RG ARCA 5418/2023');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
        });

        DB::table('lsd_importes_detraer')->insert([
            'periodo_desde' => '202001',
            'importe' => 7003.68,
            'normativa' => 'Ley 27.430 art 4 (valor histórico inicial)',
            'observaciones' => 'Registro semilla creado al migrar — verificar y actualizar con los valores reales por período.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('lsd_importes_detraer');
    }
};
