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
            ->assertJsonPath('props.config.lsd_no_conceptos', false);

        self::assertSame(1, SueConfig::count(), 'sue_config debe tener un único registro');
    }

    public function test_editar_guarda_el_flag_y_registra_el_usuario(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/config', ['lsd_no_conceptos' => true]);

        $response->assertSessionHasNoErrors()->assertRedirect('/config');

        $config = SueConfig::actual();
        self::assertTrue($config->lsd_no_conceptos);
        self::assertSame($user->id, $config->usuario_id);
        self::assertSame(1, SueConfig::count(), 'editar no debe crear un segundo registro');
    }

    public function test_rechaza_un_valor_no_booleano(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/config/editar')
            ->patch('/config', ['lsd_no_conceptos' => 'tal vez'])
            ->assertRedirect('/config/editar')
            ->assertSessionHasErrors('lsd_no_conceptos');

        self::assertFalse(SueConfig::actual()->lsd_no_conceptos);
    }

    /** La versión de assets que el middleware exige en X-Inertia-Version. */
    private function versionInertia(): string
    {
        return (string) app(HandleInertiaRequests::class)->version(request());
    }
}
