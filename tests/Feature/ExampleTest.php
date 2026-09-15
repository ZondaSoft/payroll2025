<?php

namespace Tests\Feature;

// use Tests\Support\UsaEsquemaApp;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_la_raiz_redirige_al_login_cuando_no_hay_sesion(): void
    {
        $response = $this->get('/');

        // Toda la aplicación exige autenticación: un invitado en la raíz va al login.
        $response->assertRedirect(route('login'));
    }
}
