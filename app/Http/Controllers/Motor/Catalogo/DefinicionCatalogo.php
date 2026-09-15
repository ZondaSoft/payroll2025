<?php

namespace App\Http\Controllers\Motor\Catalogo;

final readonly class DefinicionCatalogo
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelo
     * @param  string  $ruta  prefijo de nombre de ruta (motor.normativa.subsistemas)
     * @param  list<CampoCatalogo>  $campos
     * @param  list<string>  $editablesCerrado  campos editables cuando el catálogo es cerrado
     * @param  list<array{0: string, 1: string}>  $orden
     * @param  list<string>  $filtros
     */
    public function __construct(
        public string $modelo,
        public string $ruta,
        public string $titulo,
        public string $tituloPlural,
        public string $claveNatural,
        public array $campos,
        public bool $cerrado = false,
        public array $editablesCerrado = ['activo', 'estado_validacion'],
        public array $orden = [],
        public array $filtros = [],
        public bool $bajaLogica = true,
        public ?string $descripcion = null,
        public ?string $vista = null,
    ) {}

    public function campo(string $nombre): ?CampoCatalogo
    {
        foreach ($this->campos as $c) {
            if ($c->nombre === $nombre) {
                return $c;
            }
        }

        return null;
    }

    /** @return array<string, mixed> serializable a Inertia */
    public function aArray(): array
    {
        return [
            'ruta' => $this->ruta, 'titulo' => $this->titulo, 'titulo_plural' => $this->tituloPlural, 'clave_natural' => $this->claveNatural,
            'cerrado' => $this->cerrado, 'editables_cerrado' => $this->editablesCerrado, 'filtros' => $this->filtros, 'baja_logica' => $this->bajaLogica,
            'descripcion' => $this->descripcion, 'campos' => array_map(fn (CampoCatalogo $c) => $c->aArray(), $this->campos),
        ];
    }
}
