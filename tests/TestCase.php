<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\BaseDeTests;

abstract class TestCase extends BaseTestCase
{
    /**
     * GUARDARRAÍL (incidente 2026-09-15): ningún test corre si la conexión activa
     * no es una base de tests de la lista blanca (Tests\Support\BaseDeTests).
     *
     * Va acá y NO en setUp() a propósito. El setUp() de Laravel hace:
     *
     *     refreshApplication()  →  setUpTraits()  →  [resto]
     *
     * y es setUpTraits() el que dispara RefreshDatabase::refreshDatabase(), o sea
     * migrate:fresh. Validar en setUp() (que corre después de parent::setUp(), es
     * decir después de setUpTraits) llegaría TARDE: la base ya estaría borrada.
     * refreshApplication() es el primer punto donde config() existe y todavía no
     * se tocó la base.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        BaseDeTests::asegurar('correr el test '.static::class);
    }
}
