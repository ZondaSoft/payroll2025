<?php

namespace App\Http\Controllers\Motor\Catalogo;

/** Definición tipada de un campo de un catálogo genérico del motor (se serializa a la vista). */
final readonly class CampoCatalogo
{
    /**
     * @param  'texto'|'texto_largo'|'entero'|'decimal'|'booleano'|'fecha'|'periodo'|'select'|'estado_validacion'|'porcentaje'  $tipo
     * @param  list<string>  $reglas  reglas Laravel adicionales (required/nullable se derivan de $obligatorio)
     * @param  array<string, string>  $mensajes
     * @param  list<array{value: string, label: string}>|null  $opciones  para select
     */
    public function __construct(
        public string $nombre,
        public string $etiqueta,
        public string $tipo = 'texto',
        public bool $obligatorio = false,
        public array $reglas = [],
        public array $mensajes = [],
        public ?array $opciones = null,
        public bool $soloLectura = false,
        public bool $enLista = true,
        public ?string $ayuda = null,
        public int $col = 6,
        public mixed $default = null,
    ) {}

    /** @return array<string, mixed> */
    public function aArray(): array
    {
        return get_object_vars($this);
    }
}
