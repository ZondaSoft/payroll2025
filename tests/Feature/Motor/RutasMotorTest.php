<?php

namespace Tests\Feature\Motor;

use App\Models\User;
use Database\Seeders\MotorVersionesInicialesSeeder;
use Tests\Support\Motor\UsaEsquemaMotor;
use Tests\TestCase;

/** Los índices del módulo responden 200 para un usuario autenticado (sobre sqlite con el esquema del motor). */
final class RutasMotorTest extends TestCase
{
    use UsaEsquemaMotor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEsquemaMotor();
        $this->seed(MotorVersionesInicialesSeeder::class);
    }

    public function test_indices_del_motor_responden(): void
    {
        $user = User::query()->create(['name' => 'Test', 'email' => 'test@example.com', 'password' => bcrypt('secret')]);
        $rutas = ['motor.simulador.index', 'motor.integridad.index', 'motor.conceptos.index', 'motor.bases-zonda.index', 'motor.incidencias-arca.index',
            'motor.parametros.index', 'motor.escalas.basicos.index', 'motor.escalas.antiguedad.index', 'motor.escalas.vacaciones.index', 'motor.escalas.asignaciones.index',
            'motor.normativa.index', 'motor.normativa.bases-arca.index', 'motor.normativa.topes.index', 'motor.normativa.alicuotas-contribuciones.index',
            'motor.relaciones.index', 'motor.encuadramientos.index', 'motor.novedades-mapeo.index'];
        foreach ($rutas as $r) {
            $this->actingAs($user)->get(route($r))->assertOk();
        }
    }

    public function test_catalogo_cerrado_no_admite_alta(): void
    {
        $user = User::query()->create(['name' => 'Test', 'email' => 'test2@example.com', 'password' => bcrypt('secret')]);
        self::assertFalse(\Illuminate\Support\Facades\Route::has('motor.normativa.subsistemas.create'));
        $this->actingAs($user)->get(route('motor.normativa.subsistemas.index'))->assertOk();
        $this->actingAs($user)->get(route('motor.normativa.condiciones.create'))->assertOk();
    }
}
