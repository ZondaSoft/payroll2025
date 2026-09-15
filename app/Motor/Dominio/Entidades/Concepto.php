<?php

namespace App\Motor\Dominio\Entidades;

use App\Motor\Dominio\Enums\EstadoConfiguracion;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Enums\ModoCalculo;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\PoliticaEdicion;
use App\Motor\Dominio\Enums\ReglaRedondeo;
use App\Motor\Dominio\Enums\Signo;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Enums\TipoRepeticion;
use App\Motor\Dominio\ValorObjetos\CodigoConcepto;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Brick\Math\BigDecimal;

/**
 * Definición de un concepto de liquidación (27 campos de TAB_CONCEPTOS +
 * extensiones del mockup). Es la misma clase para la fila viva y para la
 * definición congelada dentro de una versión.
 */
final readonly class Concepto
{
    /**
     * @param  list<string>  $mesesAplicacion
     * @param  list<TipoLiquidacion>|null  $tiposLiquidacion  null = todas
     */
    public function __construct(
        public ?int $id,
        public CodigoConcepto $codigo,
        public string $nombre,
        public TipoConcepto $tipo,
        public Naturaleza $naturaleza,
        public Signo $signo,
        public ModoCalculo $modoCalculo,
        public Vigencia $vigencia,
        public bool $activo = true,
        public EstadoConfiguracion $estadoConfiguracion = EstadoConfiguracion::Borrador,
        public ?string $aliasRecibo = null,
        public ?string $convenioDocumental = null,
        public ?string $unidadPrincipal = null,
        public ?string $moduloResponsable = null,
        public ?string $baseModulo = null,
        public ?string $codigoParametro = null,
        public ?string $codigoNovedadCantidad = null,
        public ?string $codigoNovedadImporte = null,
        public bool $requiereNovedad = false,
        public bool $editableManual = false,
        public bool $repetible = false,
        public bool $permiteImporteCero = false,
        public bool $imprimeRecibo = true,
        public bool $esConceptoSistema = false,
        public ?TipoRepeticion $tipoRepeticion = null,
        public ?int $intervaloMeses = null,
        public ?int $cantidadMeses = null,
        public array $mesesAplicacion = [],
        public ?Periodo $periodoInicio = null,
        public ?array $tiposLiquidacion = null,
        public ?EstrategiaCalculo $estrategia = null,
        public ?FuenteComponente $fuenteCantidad = null,
        public ?FuenteComponente $fuenteValor = null,
        public ?FuenteComponente $fuenteImporte = null,
        public ?string $codigoBaseZonda = null,
        public ?BigDecimal $factorValor = null,
        public ?Importe $importeFijo = null,
        public ?ReglaRedondeo $reglaRedondeo = null,
        public ?PoliticaEdicion $politicaEdicion = null,
        public ?int $codigoAfip = null,
        public ?string $clasificacionFuncional = null,
        public ?string $ausentismoEvitable = null,
        public ?int $ordenCalculo = null,
        public ?int $ordenVisual = null,
        public ?string $observaciones = null,
    ) {}

    public function esHaber(): bool
    {
        return $this->tipo === TipoConcepto::Haber || $this->tipo === TipoConcepto::Informativo;
    }

    public function esModuloSistema(): bool
    {
        return $this->modoCalculo === ModoCalculo::ModuloSistema;
    }

    public function esRemunerativo(): bool
    {
        return $this->naturaleza === Naturaleza::Remunerativo;
    }

    /** Coeficiente de signo para sumar líneas: CREDITO +1, DEBITO −1, NEUTRO 0 (no afecta el neto). */
    public function coeficienteSigno(): int
    {
        return match ($this->signo) {
            Signo::Credito => 1,
            Signo::Debito => -1,
            Signo::Neutro => 0,
        };
    }

    public function admiteTipoLiquidacion(TipoLiquidacion $tipo): bool
    {
        return $this->tiposLiquidacion === null || in_array($tipo, $this->tiposLiquidacion, true);
    }

    public function estaPublicado(): bool
    {
        return $this->activo && $this->estadoConfiguracion === EstadoConfiguracion::Publicado;
    }

    /** Definición congelable (sin id/estado/auditoría). Claves ordenadas: es lo que se hashea. */
    public function aDefinicion(): array
    {
        $d = [
            'codigo_concepto' => (string) $this->codigo,
            'nombre_concepto' => $this->nombre,
            'alias_recibo' => $this->aliasRecibo,
            'convenio_documental' => $this->convenioDocumental,
            'tipo_concepto' => $this->tipo->value,
            'naturaleza' => $this->naturaleza->value,
            'signo' => $this->signo->value,
            'unidad_principal' => $this->unidadPrincipal,
            'modo_calculo' => $this->modoCalculo->value,
            'modulo_responsable' => $this->moduloResponsable,
            'base_modulo' => $this->baseModulo,
            'codigo_parametro' => $this->codigoParametro,
            'codigo_novedad_cantidad' => $this->codigoNovedadCantidad,
            'codigo_novedad_importe' => $this->codigoNovedadImporte,
            'requiere_novedad' => $this->requiereNovedad,
            'editable_manual' => $this->editableManual,
            'repetible' => $this->repetible,
            'permite_importe_cero' => $this->permiteImporteCero,
            'imprime_recibo' => $this->imprimeRecibo,
            'es_concepto_sistema' => $this->esConceptoSistema,
            'tipo_repeticion' => $this->tipoRepeticion?->value,
            'intervalo_meses' => $this->intervaloMeses,
            'cantidad_meses' => $this->cantidadMeses,
            'meses_aplicacion' => array_values($this->mesesAplicacion),
            'periodo_inicio' => $this->periodoInicio === null ? null : (string) $this->periodoInicio,
            'tipos_liquidacion' => $this->tiposLiquidacion === null ? null : array_map(fn (TipoLiquidacion $t) => $t->value, $this->tiposLiquidacion),
            'estrategia_calculo' => $this->estrategia?->value,
            'fuente_cantidad' => $this->fuenteCantidad?->value,
            'fuente_valor' => $this->fuenteValor?->value,
            'fuente_importe' => $this->fuenteImporte?->value,
            'codigo_base_zonda' => $this->codigoBaseZonda,
            'factor_valor' => $this->factorValor === null ? null : (string) $this->factorValor->toScale(6),
            'importe_fijo' => $this->importeFijo === null ? null : (string) $this->importeFijo,
            'regla_redondeo' => $this->reglaRedondeo?->value,
            'politica_edicion' => $this->politicaEdicion?->value,
            'codigo_afip' => $this->codigoAfip,
            'clasificacion_funcional' => $this->clasificacionFuncional,
            'ausentismo_evitable' => $this->ausentismoEvitable,
            'orden_calculo' => $this->ordenCalculo,
            'vigencia' => $this->vigencia->jsonSerialize(),
        ];
        ksort($d);

        return $d;
    }

    /** Reconstruye desde una definición (snapshot). Inversa de aDefinicion(). */
    public static function desdeDefinicion(array $d, ?int $id = null, bool $activo = true, EstadoConfiguracion $estado = EstadoConfiguracion::Publicado): self
    {
        $enum = static fn (string $clase, mixed $v) => $v === null ? null : $clase::from($v);

        return new self(
            id: $id,
            codigo: CodigoConcepto::de($d['codigo_concepto']),
            nombre: $d['nombre_concepto'],
            tipo: TipoConcepto::from($d['tipo_concepto']),
            naturaleza: Naturaleza::from($d['naturaleza']),
            signo: Signo::from($d['signo']),
            modoCalculo: ModoCalculo::from($d['modo_calculo']),
            vigencia: Vigencia::de($d['vigencia']['desde'], $d['vigencia']['hasta'] ?? null),
            activo: $activo,
            estadoConfiguracion: $estado,
            aliasRecibo: $d['alias_recibo'] ?? null,
            convenioDocumental: $d['convenio_documental'] ?? null,
            unidadPrincipal: $d['unidad_principal'] ?? null,
            moduloResponsable: $d['modulo_responsable'] ?? null,
            baseModulo: $d['base_modulo'] ?? null,
            codigoParametro: $d['codigo_parametro'] ?? null,
            codigoNovedadCantidad: $d['codigo_novedad_cantidad'] ?? null,
            codigoNovedadImporte: $d['codigo_novedad_importe'] ?? null,
            requiereNovedad: (bool) ($d['requiere_novedad'] ?? false),
            editableManual: (bool) ($d['editable_manual'] ?? false),
            repetible: (bool) ($d['repetible'] ?? false),
            permiteImporteCero: (bool) ($d['permite_importe_cero'] ?? false),
            imprimeRecibo: (bool) ($d['imprime_recibo'] ?? true),
            esConceptoSistema: (bool) ($d['es_concepto_sistema'] ?? false),
            tipoRepeticion: $enum(TipoRepeticion::class, $d['tipo_repeticion'] ?? null),
            intervaloMeses: $d['intervalo_meses'] ?? null,
            cantidadMeses: $d['cantidad_meses'] ?? null,
            mesesAplicacion: $d['meses_aplicacion'] ?? [],
            periodoInicio: isset($d['periodo_inicio']) ? Periodo::de($d['periodo_inicio']) : null,
            tiposLiquidacion: isset($d['tipos_liquidacion']) ? array_map(fn ($v) => TipoLiquidacion::from($v), $d['tipos_liquidacion']) : null,
            estrategia: $enum(EstrategiaCalculo::class, $d['estrategia_calculo'] ?? null),
            fuenteCantidad: $enum(FuenteComponente::class, $d['fuente_cantidad'] ?? null),
            fuenteValor: $enum(FuenteComponente::class, $d['fuente_valor'] ?? null),
            fuenteImporte: $enum(FuenteComponente::class, $d['fuente_importe'] ?? null),
            codigoBaseZonda: $d['codigo_base_zonda'] ?? null,
            factorValor: isset($d['factor_valor']) ? BigDecimal::of($d['factor_valor']) : null,
            importeFijo: isset($d['importe_fijo']) ? Importe::de($d['importe_fijo']) : null,
            reglaRedondeo: $enum(ReglaRedondeo::class, $d['regla_redondeo'] ?? null),
            politicaEdicion: $enum(PoliticaEdicion::class, $d['politica_edicion'] ?? null),
            codigoAfip: $d['codigo_afip'] ?? null,
            clasificacionFuncional: $d['clasificacion_funcional'] ?? null,
            ausentismoEvitable: $d['ausentismo_evitable'] ?? null,
            ordenCalculo: $d['orden_calculo'] ?? null,
        );
    }
}
