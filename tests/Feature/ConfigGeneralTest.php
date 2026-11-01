<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\SueConfig;
use App\Models\User;
use Tests\Support\UsaEsquemaApp;
use Tests\TestCase;

class ConfigGeneralTest extends TestCase
{
    use UsaEsquemaApp;

    public function test_la_pantalla_muestra_el_registro_unico_de_parametros(): void
    {
        $user = User::factory()->create();

        // Con la cabecera X-Inertia la respuesta es JSON y no se renderiza el layout Blade.
        // La versión debe ser la que calcula el middleware (hash del manifest de Vite), si no responde 409.
        $response = $this->actingAs($user)
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $this->versionInertia()])
            ->get('/config');

        // La respuesta es el JSON de Inertia (component + props), no la vista Blade.
        $response->assertOk()
            ->assertJsonPath('component', 'Config/Index')
            ->assertJsonPath('props.edicion', false)
            ->assertJsonPath('props.config.id', 1)
            ->assertJsonPath('props.config.lsd_no_conceptos', false)
            ->assertJsonPath('props.config.lsd_ocultar_activos', false);

        self::assertSame(1, SueConfig::count(), 'sue_config debe tener un único registro');
    }

    public function test_editar_guarda_los_flags_y_registra_el_usuario(): void
    {
        $user = User::factory()->create();

        // El formulario manda los dos switches juntos
        $response = $this->actingAs($user)->patch('/config', [
            'lsd_no_conceptos' => true,
            'lsd_ocultar_activos' => true,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/config');

        $config = SueConfig::actual();
        self::assertTrue($config->lsd_no_conceptos);
        self::assertTrue($config->lsd_ocultar_activos);
        self::assertSame($user->id, $config->usuario_id);
        self::assertSame(1, SueConfig::count(), 'editar no debe crear un segundo registro');
    }

    public function test_rechaza_un_valor_no_booleano(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/config/editar')
            ->patch('/config', ['lsd_no_conceptos' => 'tal vez', 'lsd_ocultar_activos' => false])
            ->assertRedirect('/config/editar')
            ->assertSessionHasErrors('lsd_no_conceptos');

        self::assertFalse(SueConfig::actual()->lsd_no_conceptos);
    }

    public function test_rechaza_un_valor_no_booleano_en_el_switch_de_activos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/config/editar')
            ->patch('/config', ['lsd_no_conceptos' => false, 'lsd_ocultar_activos' => 'tal vez'])
            ->assertRedirect('/config/editar')
            ->assertSessionHasErrors('lsd_ocultar_activos');

        // El valor no cambió: el diálogo se sigue mostrando.
        self::assertFalse(SueConfig::actual()->lsd_ocultar_activos);
    }

    /** La versión de assets que el middleware exige en X-Inertia-Version. */
    private function versionInertia(): string
    {
        return (string) app(HandleInertiaRequests::class)->version(request());
    }
}
