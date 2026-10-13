<?php

namespace Tests\Fixtures\Motor;

use App\Motor\Dominio\Contratos\JornadaCanonica;
use App\Motor\Dominio\Entidades\AlicuotaAporte;
use App\Motor\Dominio\Entidades\AlicuotaContribucion;
use App\Motor\Dominio\Entidades\BaseArca;
use App\Motor\Dominio\Entidades\Basico;
use App\Motor\Dominio\Entidades\CategoriaVigente;
use App\Motor\Dominio\Entidades\Concepto;
use App\Motor\Dominio\Entidades\ConceptoVersion;
use App\Motor\Dominio\Entidades\EncuadramientoEmpresa;
use App\Motor\Dominio\Entidades\EncuadreArca;
use App\Motor\Dominio\Entidades\IncidenciaArca;
use App\Motor\Dominio\Entidades\IncidenciaBaseZonda;
use App\Motor\Dominio\Entidades\ModeloAntiguedad;
use App\Motor\Dominio\Entidades\Novedad;
use App\Motor\Dominio\Entidades\RegimenAsignado;
use App\Motor\Dominio\Entidades\RegimenContributivo;
use App\Motor\Dominio\Entidades\ReglaAplicabilidad;
use App\Motor\Dominio\Entidades\RelacionLaboral;
use App\Motor\Dominio\Entidades\Subsistema;
use App\Motor\Dominio\Entidades\SubsistemaAplicacion;
use App\Motor\Dominio\Entidades\Tope;
use App\Motor\Dominio\Entidades\TramoAntiguedad;
use App\Motor\Dominio\Entidades\ValorParametro;
use App\Motor\Dominio\Enums\AccionAplicabilidad;
use App\Motor\Dominio\Enums\AlcanceParametro;
use App\Motor\Dominio\Enums\ComponenteTope;
use App\Motor\Dominio\Enums\Entorno;
use App\Motor\Dominio\Enums\EstadoConfiguracion;
use App\Motor\Dominio\Enums\EstadoRelacion;
use App\Motor\Dominio\Enums\EstadoValidacion;
use App\Motor\Dominio\Enums\EstrategiaCalculo;
use App\Motor\Dominio\Enums\FuenteComponente;
use App\Motor\Dominio\Enums\Incidencia;
use App\Motor\Dominio\Enums\MetodoAntiguedad;
use App\Motor\Dominio\Enums\ModoCalculo;
use App\Motor\Dominio\Enums\Naturaleza;
use App\Motor\Dominio\Enums\ReglaRedondeo;
use App\Motor\Dominio\Enums\Signo;
use App\Motor\Dominio\Enums\SujetoObligado;
use App\Motor\Dominio\Enums\TipoAmbito;
use App\Motor\Dominio\Enums\TipoAplicacion;
use App\Motor\Dominio\Enums\TipoConcepto;
use App\Motor\Dominio\Enums\TipoLiquidacion;
use App\Motor\Dominio\Enums\TipoRegla;
use App\Motor\Dominio\Enums\TipoTope;
use App\Motor\Dominio\Liquidador\ContextoPeriodo;
use App\Motor\Dominio\Liquidador\ContextoRelacionLaboral;
use App\Motor\Dominio\Servicios\SnapshotConcepto;
use App\Motor\Dominio\ValorObjetos\CodigoConcepto;
use App\Motor\Dominio\ValorObjetos\Importe;
use App\Motor\Dominio\ValorObjetos\Periodo;
use App\Motor\Dominio\ValorObjetos\Porcentaje;
use App\Motor\Dominio\ValorObjetos\Vigencia;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

/**
 * Caso de prueba del Excel funcional (julio 2026, CCT 371/03 y 40/89):
 * ids de convenio 1 (371/03) y 2 (40/89); categorías 101..107 y 201..206;
 * RL-0003 AGUIRRE (7019, categoría 104, 17 hs pactadas) y RL-0004 ALFARO
 * (221, camionero categoría 201, ingreso 2009-07-01).
 * Todo lo normativo nace SIN_CONFIRMAR como en la spec.
 */
final class CasoJulio2026
{
    public const PERIODO = '2026-07';

    public const VIGENCIA = '2026-07-01';

    public static function periodo(): Periodo
    {
        return Periodo::de(self::PERIODO);
    }

    public static function vig(string $desde = '2026-01-01', ?string $hasta = null): Vigencia
    {
        return Vigencia::de($desde, $hasta);
    }

    /** @return list<BaseArca> */
    public static function basesArca(): array
    {
        $out = [];
        foreach ([1 => ['Aportes SIPA', 'TRABAJADOR', true], 2 => ['Contribuciones SIPA/INSSJP', 'EMPLEADOR', false], 3 => ['Contribuciones FNE/AAFF/RENATRE', 'EMPLEADOR', false],
            4 => ['Aportes OS y FSR', 'TRABAJADOR', true], 5 => ['Aporte INSSJP', 'TRABAJADOR', true], 6 => ['Aportes diferenciales', 'TRABAJADOR', false],
            7 => ['Aportes regímenes especiales', 'TRABAJADOR', false], 8 => ['Contribuciones OS y FSR', 'EMPLEADOR', false], 9 => ['LRT', 'EMPLEADOR', false], 10 => ['Contribuciones tras detracción', 'EMPLEADOR', false]] as $n => [$d, $s, $t]) {
            $out[] = new BaseArca("REM_{$n}", $d, SujetoObligado::from($s), $t);
        }

        return $out;
    }

    /** @return list<Subsistema> */
    public static function subsistemas(): array
    {
        return [
            new Subsistema(1, 'SIPA', 'SIPA', 'SEGURIDAD_SOCIAL', 'REM_1', 'REM_2'), new Subsistema(2, 'INSSJP', 'INSSJP', 'SEGURIDAD_SOCIAL', 'REM_5', 'REM_2'),
            new Subsistema(3, 'AAFF', 'Asignaciones familiares', 'SEGURIDAD_SOCIAL', null, 'REM_3'), new Subsistema(4, 'FNE', 'Fondo Nacional de Empleo', 'SEGURIDAD_SOCIAL', null, 'REM_3'),
            new Subsistema(5, 'RENATRE', 'RENATRE', 'SEGURIDAD_SOCIAL', null, 'REM_3'), new Subsistema(6, 'OS', 'Obra social', 'OBRA_SOCIAL', 'REM_4', 'REM_8'),
            new Subsistema(7, 'FSR', 'FSR', 'OBRA_SOCIAL', 'REM_4', 'REM_8'), new Subsistema(8, 'LRT', 'LRT', 'ART', null, 'REM_9'),
        ];
    }

    /** @return list<SubsistemaAplicacion> */
    public static function aplicaciones(): array
    {
        $sc = EstadoValidacion::SinConfirmar;

        return [
            new SubsistemaAplicacion(1, 1, 'SIPA', TipoAplicacion::Aporte, 'REM_1', 1, true, $sc), new SubsistemaAplicacion(2, 1, 'SIPA', TipoAplicacion::Contribucion, 'REM_2', 2, true, $sc),
            new SubsistemaAplicacion(3, 2, 'INSSJP', TipoAplicacion::Aporte, 'REM_5', 3, true, $sc), new SubsistemaAplicacion(4, 2, 'INSSJP', TipoAplicacion::Contribucion, 'REM_2', 4, true, $sc),
            new SubsistemaAplicacion(5, 6, 'OS', TipoAplicacion::Aporte, 'REM_4', 5, true, $sc), new SubsistemaAplicacion(6, 6, 'OS', TipoAplicacion::Contribucion, 'REM_8', 6, true, $sc),
            new SubsistemaAplicacion(7, 7, 'FSR', TipoAplicacion::Aporte, 'REM_4', 7, true, $sc), new SubsistemaAplicacion(8, 7, 'FSR', TipoAplicacion::Contribucion, 'REM_8', 8, true, $sc),
            new SubsistemaAplicacion(9, 5, 'RENATRE', TipoAplicacion::Aporte, null, 9, true, EstadoValidacion::SinDato), new SubsistemaAplicacion(10, 5, 'RENATRE', TipoAplicacion::Contribucion, 'REM_3', 10, true, $sc),
            new SubsistemaAplicacion(11, 3, 'AAFF', TipoAplicacion::Contribucion, 'REM_3', 12, true, $sc), new SubsistemaAplicacion(12, 4, 'FNE', TipoAplicacion::Contribucion, 'REM_3', 14, true, $sc),
            new SubsistemaAplicacion(13, 8, 'LRT', TipoAplicacion::Contribucion, 'REM_9', 16, true, $sc),
        ];
    }

    /** @return list<AlicuotaContribucion> ALIC-001..008 */
    public static function alicuotasContribuciones(EstadoValidacion $estado = EstadoValidacion::SinConfirmar): array
    {
        $f = [['ALIC-001', 'A', 1, 'SIPA', '0.1235', 'REM_2'], ['ALIC-002', 'A', 2, 'INSSJP', '0.0157', 'REM_2'], ['ALIC-003', 'A', 3, 'AAFF', '0.054', 'REM_3'], ['ALIC-004', 'A', 4, 'FNE', '0.0108', 'REM_3'],
            ['ALIC-005', 'B', 1, 'SIPA', '0.1077', 'REM_2'], ['ALIC-006', 'B', 2, 'INSSJP', '0.0159', 'REM_2'], ['ALIC-007', 'B', 3, 'AAFF', '0.047', 'REM_3'], ['ALIC-008', 'B', 4, 'FNE', '0.0094', 'REM_3']];
        $out = [];
        foreach ($f as $i => [$c, $e, $ids, $s, $p, $b]) {
            $out[] = new AlicuotaContribucion($i + 1, $c, $e, $ids, $s, Porcentaje::deFraccion($p), $b, self::vig('2020-01-01'), $estado);
        }

        return $out;
    }

    /** @return list<AlicuotaAporte> APOR-001..004 */
    public static function alicuotasAportes(EstadoValidacion $estado = EstadoValidacion::SinConfirmar): array
    {
        return [
            new AlicuotaAporte(1, 'APOR-001', 1, 'SIPA', null, Porcentaje::deFraccion('0.11'), 'REM_1', true, self::vig('2020-01-01'), $estado),
            new AlicuotaAporte(2, 'APOR-002', 2, 'INSSJP', null, Porcentaje::deFraccion('0.03'), 'REM_5', true, self::vig('2020-01-01'), $estado),
            new AlicuotaAporte(3, 'APOR-003', 1, 'SIPA', '2', Porcentaje::deFraccion('0'), 'REM_1', true, self::vig('2020-01-01'), EstadoValidacion::SinDato),
            new AlicuotaAporte(4, 'APOR-004', 2, 'INSSJP', '2', Porcentaje::deFraccion('0.03'), 'REM_5', true, self::vig('2020-01-01'), EstadoValidacion::SinDato),
        ];
    }

    /** @return list<Tope> TOP-003..007 (julio 2026) */
    public static function topes(EstadoValidacion $estado = EstadoValidacion::SinConfirmar): array
    {
        $p = self::periodo();

        return [
            new Tope(3, 'TOP-003', $p, 'REM_1', TipoTope::Maximo, ComponenteTope::Mensual, Importe::de('4509567.41'), $estado),
            new Tope(4, 'TOP-004', $p, 'REM_1', TipoTope::Minimo, ComponenteTope::Mensual, Importe::de('138757.90'), $estado),
            new Tope(5, 'TOP-005', $p, 'REM_5', TipoTope::Maximo, ComponenteTope::Mensual, Importe::de('4509567.41'), $estado),
            new Tope(6, 'TOP-006', $p, 'REM_4', TipoTope::Minimo, ComponenteTope::Mensual, null, EstadoValidacion::SinDato),
            new Tope(7, 'TOP-007', $p, 'REM_5', TipoTope::Minimo, ComponenteTope::Mensual, null, EstadoValidacion::SinDato),
            // REM_4 máximo no viene en la spec: lo agregamos igual al de REM_1 para que la base de OS pueda topearse
            new Tope(8, 'TOP-REM4', $p, 'REM_4', TipoTope::Maximo, ComponenteTope::Mensual, Importe::de('4509567.41'), $estado),
        ];
    }

    /** @return list<RegimenContributivo> RC-001..009 */
    public static function regimenes(EstadoValidacion $estado = EstadoValidacion::SinConfirmar): array
    {
        $e = SujetoObligado::Empleador;
        $t = SujetoObligado::Trabajador;
        $v = self::vig('2020-01-01');

        return [
            new RegimenContributivo(1, 'GENERAL', 'Régimen general', TipoRegla::Base, $e, null, 'SEGURIDAD_SOCIAL', null, 'REM_2', null, 10, $v, $estado),
            new RegimenContributivo(2, 'TRANSP_CARGAS', 'Conductor de transporte de cargas', TipoRegla::Adicional, $e, Porcentaje::deFraccion('0.02'), 'SIPA', null, 'REM_2', null, 30, $v, $estado),
            new RegimenContributivo(3, 'TRANSP_PASAJEROS', 'Transporte de pasajeros', TipoRegla::Adicional, $e, Porcentaje::deFraccion('0.02'), 'SIPA', null, 'REM_2', null, 30, $v, $estado),
            new RegimenContributivo(4, 'CONSTRUCCION', 'Construcción', TipoRegla::Adicional, $e, Porcentaje::deFraccion('0.05'), 'SIPA', null, 'REM_2', null, 30, $v, $estado),
            new RegimenContributivo(5, 'AGRARIO', 'Agrario', TipoRegla::Adicional, $e, Porcentaje::deFraccion('0.02'), 'SIPA', null, 'REM_2', null, 30, $v, $estado),
            new RegimenContributivo(6, 'RURAL_RENATRE', 'RENATRE', TipoRegla::SustitucionSubsistema, $e, Porcentaje::deFraccion('0.015'), 'RENATRE', 'FNE', 'REM_3', null, 40, $v, EstadoValidacion::SinDato),
            new RegimenContributivo(7, 'RIFL', 'RIFL Ley 27.802', TipoRegla::Sustitutivo, $e, Porcentaje::deFraccion('0.05'), 'SIPA+INSSJP+AAFF+FNE', null, 'REM_2', 48, 20, self::vig('2026-05-01'), $estado),
            new RegimenContributivo(8, 'DOCENTE', 'Docente', TipoRegla::Adicional, $t, Porcentaje::deFraccion('0.02'), 'SIPA', null, 'REM_6', null, 30, $v, $estado),
            new RegimenContributivo(9, 'INVESTIGADOR', 'Investigador', TipoRegla::Adicional, $t, Porcentaje::deFraccion('0.02'), 'SIPA', null, 'REM_6', null, 30, $v, $estado),
        ];
    }

    public static function modeloAntiguedadEscala(): ModeloAntiguedad
    {
        $tramos = [new TramoAntiguedad(BigDecimal::of(0), BigDecimal::of(0), Porcentaje::deFraccion('0'), 1)];
        for ($a = 1; $a <= 20; $a++) {
            $tramos[] = new TramoAntiguedad(BigDecimal::of($a), BigDecimal::of($a), Porcentaje::deFraccion((string) ($a * 0.02)), $a + 1);
        }
        for ($a = 21; $a <= 29; $a++) {
            $tramos[] = new TramoAntiguedad(BigDecimal::of($a), BigDecimal::of($a), Porcentaje::deFraccion((string) round(0.40 + ($a - 20) * 0.03, 2)), $a + 1);
        }
        $tramos[] = new TramoAntiguedad(BigDecimal::of(30), null, Porcentaje::deFraccion('0.70'), 31);

        return new ModeloAntiguedad(1, 'ANTIG_2_3_TOPE70_V1', '2 % anual hasta 20, luego 3 %, tope 70 %', MetodoAntiguedad::Escala, null, Porcentaje::deFraccion('0.70'), $tramos, EstadoValidacion::SinConfirmar);
    }

    public static function modeloAntiguedadLineal(): ModeloAntiguedad
    {
        return new ModeloAntiguedad(2, 'ANTIG_1_PCT_ANUAL_V1', '1 % por año', MetodoAntiguedad::Lineal, Porcentaje::deFraccion('0.01'), null, [], EstadoValidacion::SinConfirmar);
    }

    /** @return list<ValorParametro> técnicos CONFIRMADO + los del Excel SIN_CONFIRMAR */
    public static function valoresParametros(): array
    {
        $g = fn (int $id, string $codigo, string $valor, EstadoValidacion $e = EstadoValidacion::Confirmado) => new ValorParametro($id, $id, $codigo, AlcanceParametro::General, null, null, null, null, BigDecimal::of($valor), self::vig(), $e);
        $conv = fn (int $id, string $codigo, int $idConvenio, string $valor) => new ValorParametro($id, $id, $codigo, AlcanceParametro::Convenio, $idConvenio, null, null, null, BigDecimal::of($valor), self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
        $cat = fn (int $id, string $codigo, int $idCategoria, string $valor) => new ValorParametro($id, $id, $codigo, AlcanceParametro::Categoria, null, $idCategoria, null, null, BigDecimal::of($valor), self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);

        return [
            $g(100, 'REDONDEO_DECIMALES', '2'), $g(101, 'DIVISOR_DIAS', '30'), $g(102, 'HORAS_MES_REFERENCIA', '200'),
            $conv(1, 'PORC_TITULO', 1, '0.05'), $conv(2, 'PORC_ASISTENCIA', 1, '0.08'), $conv(3, 'PORC_MANEJO_FONDOS', 1, '0.08'),
            $cat(4, 'HS_TOLERANCIA_HORARIA', 103, '9'), $conv(5, 'TARIFA_KM', 2, '84.80265'), $conv(6, 'VALOR_CONTROL_CARGA_DESCARGA', 2, '44167.1'),
            $conv(7, 'VALOR_PERMANENCIA_FR_B', 2, '30004.53'), $conv(8, 'VALOR_PERMANENCIA_FR_A', 2, '57242.2'), $conv(9, 'VALOR_PERNOCTADA_BASE', 2, '18891.63'),
            $conv(10, 'VALOR_COMIDA_BASE', 2, '16219.85'), $conv(11, 'VALOR_VIATICO_ESPECIAL_BASE', 2, '8139.09'), $conv(12, 'PORC_ADIC_BASICO_5_6_2', 2, '0.2'),
            $conv(13, 'PORC_ADIC_NO_REMUNERATIVO', 2, '0.2'), $cat(14, 'PORC_PLUS_PLURALIDAD', 203, '0.25'), $cat(15, 'PORC_PLUS_PLURALIDAD', 204, '0.25'),
            $cat(16, 'PORC_PLUS_PLURALIDAD', 205, '0.18'), $conv(17, 'PORC_ACUERDO_EMPRESA_SINDICATO_5_6_2', 2, '0.2'),
            // recargos de horas extras (PENDIENTE ALTA en la spec; valores convencionales)
            $g(103, 'RECARGO_HE_50', '1.5', EstadoValidacion::SinConfirmar), $g(104, 'RECARGO_HE_100', '2', EstadoValidacion::SinConfirmar),
        ];
    }

    /** @return array<int, list<Basico>> categoría => básicos vigentes (escala julio 2026) */
    public static function basicos(): array
    {
        $out = [];
        foreach ([101 => '1401901.95', 102 => '1372530.89', 103 => '1350389.10', 104 => '1335827.18', 105 => '1312961.90', 106 => '1312961.90', 107 => '1368014.73',
            201 => '1060010.29', 202 => '1060010.29', 203 => '1180565.06', 204 => '1064259.72', 205 => '1005364.13', 206 => '1005364.13'] as $cat => $imp) {
            $out[$cat] = [new Basico($cat, $cat, (string) $cat, self::vig(self::VIGENCIA), Importe::de($imp), EstadoValidacion::SinConfirmar)];
        }

        return $out;
    }

    /** Concepto genérico del caso, con configuración de cálculo derivada como en la migración 07. */
    public static function concepto(string $codigo, string $nombre, TipoConcepto $tipo, Naturaleza $nat, ?EstrategiaCalculo $estrategia, array $extra = []): Concepto
    {
        return new Concepto(
            id: crc32($codigo) % 100000, codigo: CodigoConcepto::de($codigo), nombre: $nombre, tipo: $tipo, naturaleza: $nat,
            signo: $tipo === TipoConcepto::Haber ? Signo::Credito : ($tipo === TipoConcepto::Descuento ? Signo::Debito : Signo::Neutro),
            modoCalculo: $estrategia === EstrategiaCalculo::ModuloSistema ? ModoCalculo::ModuloSistema : ModoCalculo::Formula,
            vigencia: self::vig(self::VIGENCIA), activo: true, estadoConfiguracion: EstadoConfiguracion::Publicado,
            baseModulo: $extra['base_modulo'] ?? null, codigoParametro: $extra['parametro'] ?? null, codigoNovedadCantidad: $extra['novedad'] ?? null,
            codigoNovedadImporte: $extra['novedad_importe'] ?? null, permiteImporteCero: $extra['permite_cero'] ?? false,
            tiposLiquidacion: $extra['tipos'] ?? null, estrategia: $estrategia, fuenteCantidad: $extra['fc'] ?? null, fuenteValor: $extra['fv'] ?? null,
            fuenteImporte: $extra['fi'] ?? null, codigoBaseZonda: $extra['base_zonda'] ?? null,
            factorValor: isset($extra['factor']) ? BigDecimal::of($extra['factor']) : null, importeFijo: isset($extra['fijo']) ? Importe::de($extra['fijo']) : null,
            reglaRedondeo: ReglaRedondeo::Parametrizada, ordenVisual: $extra['orden'] ?? 100,
        );
    }

    /** Los 37 conceptos del Excel (subconjunto relevante configurado como la migración 07). @return array<string, Concepto> */
    public static function conceptos(): array
    {
        $H = TipoConcepto::Haber;
        $D = TipoConcepto::Descuento;
        $C = TipoConcepto::Contribucion;
        $R = Naturaleza::Remunerativo;
        $NR = Naturaleza::NoRemunerativo;
        $NOV = FuenteComponente::Novedad;
        $PAR = FuenteComponente::Parametro;
        $BZ = FuenteComponente::BaseZonda;
        $lista = [
            self::concepto('BASICO', 'Sueldo Básico', $H, $R, EstrategiaCalculo::Basico, ['fi' => FuenteComponente::Formula, 'orden' => 10]),
            self::concepto('ANTIG', 'Antigüedad', $H, $R, EstrategiaCalculo::Antiguedad, ['fi' => FuenteComponente::Formula, 'orden' => 20]),
            self::concepto('ADTIT', 'Adicional por título', $H, $R, EstrategiaCalculo::PorcentajeSobreBase, ['parametro' => 'PORC_TITULO', 'fv' => $PAR, 'orden' => 30]),
            self::concepto('ASIST', 'Premio asistencia', $H, $R, EstrategiaCalculo::PorcentajeSobreBase, ['parametro' => 'PORC_ASISTENCIA', 'fv' => $PAR, 'orden' => 40]),
            self::concepto('MANFON', 'Manejo de fondos', $H, $R, EstrategiaCalculo::PorcentajeSobreBase, ['parametro' => 'PORC_MANEJO_FONDOS', 'fv' => $PAR, 'orden' => 50]),
            self::concepto('PLUSPLUR', 'Plus pluralidad', $H, $R, EstrategiaCalculo::PorcentajeSobreBase, ['parametro' => 'PORC_PLUS_PLURALIDAD', 'fv' => $PAR, 'orden' => 60]),
            self::concepto('KMREC', 'Kilómetros recorridos', $H, $R, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'TARIFA_KM', 'novedad' => 'KM_REM', 'fc' => $NOV, 'fv' => $PAR, 'orden' => 70]),
            self::concepto('CTRLCD', 'Control carga/descarga', $H, $R, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'VALOR_CONTROL_CARGA_DESCARGA', 'novedad' => 'CTRL_CARGA_DESC', 'fc' => $NOV, 'fv' => $PAR, 'orden' => 80]),
            self::concepto('PERMFRB', 'Permanencia fuera residencia B', $H, $R, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'VALOR_PERMANENCIA_FR_B', 'novedad' => 'PERM_FR_B', 'fc' => $NOV, 'fv' => $PAR, 'orden' => 90]),
            self::concepto('HE50', 'Horas extras 50 %', $H, $R, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'RECARGO_HE_50', 'novedad' => 'HE_50', 'fc' => $NOV, 'fv' => $BZ, 'base_zonda' => 'BASE_HORA_NORMAL', 'orden' => 100]),
            self::concepto('HE100', 'Horas extras 100 %', $H, $R, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'RECARGO_HE_100', 'novedad' => 'HE_100', 'fc' => $NOV, 'fv' => $BZ, 'base_zonda' => 'BASE_HORA_NORMAL', 'orden' => 110]),
            self::concepto('FERTRAB', 'Feriado trabajado', $H, $R, EstrategiaCalculo::CantidadPorValor, ['novedad' => 'FERIADO_CANT', 'fc' => $NOV, 'fv' => FuenteComponente::Basico, 'orden' => 120]),
            self::concepto('TOLHOR', 'Tolerancia horaria', $H, $NR, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'HS_TOLERANCIA_HORARIA', 'fc' => $PAR, 'fv' => $BZ, 'base_zonda' => 'BASE_HORA_NORMAL', 'orden' => 130]),
            self::concepto('VIATKM', 'Viático por km', $H, $NR, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'TARIFA_KM', 'novedad' => 'KM_VIAT_NR', 'fc' => $NOV, 'fv' => $PAR, 'orden' => 140]),
            self::concepto('PERMFRA', 'Permanencia fuera residencia A', $H, $NR, EstrategiaCalculo::CantidadPorValor, ['parametro' => 'VALOR_PERMANENCIA_FR_A', 'novedad' => 'PERM_FR_A', 'fc' => $NOV, 'fv' => $PAR, 'orden' => 150]),
            self::concepto('ADICNR20', 'Adicional NR 20 %', $H, $NR, EstrategiaCalculo::PorcentajeSobreBase, ['parametro' => 'PORC_ADIC_NO_REMUNERATIVO', 'fv' => $PAR, 'base_zonda' => 'BASE_NO_REMUNERATIVA', 'orden' => 160]),
            self::concepto('ACTAFUT', 'A cuenta de futuros aumentos', $H, $NR, EstrategiaCalculo::ImporteDirecto, ['fi' => FuenteComponente::Manual, 'orden' => 170]),
            self::concepto('APJUB', 'Aporte jubilatorio', $D, Naturaleza::Descuento, EstrategiaCalculo::ModuloSistema, ['base_modulo' => 'REM_1', 'orden' => 200]),
            self::concepto('APINSSJYP', 'Aporte INSSJP', $D, Naturaleza::Descuento, EstrategiaCalculo::ModuloSistema, ['base_modulo' => 'REM_5', 'orden' => 210]),
            self::concepto('APOSOC', 'Aporte obra social', $D, Naturaleza::Descuento, EstrategiaCalculo::ModuloSistema, ['base_modulo' => 'REM_4', 'orden' => 220]),
            self::concepto('CUOSIN', 'Cuota sindical', $D, Naturaleza::Descuento, EstrategiaCalculo::ModuloSistema, ['orden' => 230]),
            self::concepto('CONTSS', 'Contribución seguridad social', $C, Naturaleza::Contribucion, EstrategiaCalculo::ModuloSistema, ['base_modulo' => 'REM_2', 'orden' => 300]),
            self::concepto('CONTOS', 'Contribución obra social', $C, Naturaleza::Contribucion, EstrategiaCalculo::ModuloSistema, ['base_modulo' => 'REM_8', 'orden' => 310]),
            self::concepto('CONTART', 'Contribución ART', $C, Naturaleza::Contribucion, EstrategiaCalculo::ModuloSistema, ['base_modulo' => 'REM_9', 'orden' => 320]),
            self::concepto('INDANT', 'Indemnización por antigüedad', $H, $NR, EstrategiaCalculo::ImporteDirecto, ['fi' => FuenteComponente::Manual, 'tipos' => [TipoLiquidacion::Final], 'orden' => 400]),
        ];
        $out = [];
        foreach ($lista as $c) {
            $out[(string) $c->codigo] = $c;
        }

        return $out;
    }

    /**
     * Composición de bases Zonda (BASE_ZONDA_INCIDENCIA del Excel, simplificada):
     *  BASE_HORA_NORMAL: BASICO, ANTIG, ADTIT, ASIST, MANFON, PLUSPLUR, AD562 (sin HE, sin km)
     *  BASE_SINDICAL: remunerativos habituales · BASE_NO_REMUNERATIVA: VIATKM, PERMFRA · BASE_ANTIGUEDAD: BASICO
     *
     * @return array<string, list<string>>
     */
    public static function composicionBases(): array
    {
        return [
            'BASE_HORA_NORMAL' => ['BASICO', 'ANTIG', 'ADTIT', 'ASIST', 'MANFON', 'PLUSPLUR'],
            'BASE_SINDICAL' => ['BASICO', 'ANTIG', 'ADTIT', 'ASIST', 'MANFON', 'PLUSPLUR', 'KMREC', 'HE50', 'HE100'],
            'BASE_NO_REMUNERATIVA' => ['VIATKM', 'PERMFRA'],
            'BASE_ANTIGUEDAD' => ['BASICO'],
        ];
    }

    /**
     * Incidencias ARCA: remunerativos S en todas las aplicaciones con base; no remunerativos N.
     * $pendientes: conceptos que quedan PENDIENTE (para probar el bloqueo en PRODUCCION).
     *
     * @return array<string, list<IncidenciaArca>>
     */
    public static function incidenciasArca(array $conceptos, array $pendientes = []): array
    {
        $out = [];
        foreach ($conceptos as $codigo => $c) {
            if (! $c->esHaber()) {
                continue;
            }
            foreach (self::aplicaciones() as $ap) {
                $inc = in_array($codigo, $pendientes, true) ? Incidencia::Pendiente : ($c->esRemunerativo() ? Incidencia::Si : Incidencia::No);
                $out[$codigo][] = new IncidenciaArca(null, $codigo, $ap->id, $ap->codigoSubsistema, $ap->tipoAplicacion, $inc, self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
            }
        }

        return $out;
    }

    /**
     * Versiones vigentes (snapshot) de los conceptos con aplicabilidad GENERAL INCLUIR, salvo los
     * específicos de CCT 40/89 (km, control, permanencia, viáticos, plus) que van por CONVENIO 2,
     * y TOLHOR/PLUSPLUR por CATEGORIA.
     *
     * @return array<string, ConceptoVersion>
     */
    public static function versiones(array $conceptos = [], array $pendientes = [], array $aplicabilidadExtra = []): array
    {
        $conceptos = $conceptos ?: self::conceptos();
        $snap = new SnapshotConcepto;
        $incid = self::incidenciasArca($conceptos, $pendientes);
        $comp = self::composicionBases();
        $soloConvenio2 = ['KMREC', 'CTRLCD', 'PERMFRB', 'VIATKM', 'PERMFRA', 'ADICNR20'];
        $soloConvenio1 = ['ADTIT', 'ASIST', 'MANFON'];
        $porCategoria = ['TOLHOR' => [103], 'PLUSPLUR' => [203, 204, 205]];
        $out = [];
        $id = 1;
        foreach ($conceptos as $codigo => $c) {
            $bz = [];
            foreach ($comp as $base => $integrantes) {
                if (in_array($codigo, $integrantes, true)) {
                    $bz[] = new IncidenciaBaseZonda(null, crc32($base) % 1000, $base, $codigo, true, self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
                }
            }
            $reglas = [];
            if (isset($porCategoria[$codigo])) {
                foreach ($porCategoria[$codigo] as $cat) {
                    $reglas[] = new ReglaAplicabilidad(null, $codigo, TipoAmbito::Categoria, null, $cat, null, null, AccionAplicabilidad::Incluir, self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
                }
            } elseif (in_array($codigo, $soloConvenio2, true)) {
                $reglas[] = new ReglaAplicabilidad(null, $codigo, TipoAmbito::Convenio, 2, null, null, null, AccionAplicabilidad::Incluir, self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
            } elseif (in_array($codigo, $soloConvenio1, true)) {
                $reglas[] = new ReglaAplicabilidad(null, $codigo, TipoAmbito::Convenio, 1, null, null, null, AccionAplicabilidad::Incluir, self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
            } else {
                $reglas[] = new ReglaAplicabilidad(null, $codigo, TipoAmbito::General, null, null, null, null, AccionAplicabilidad::Incluir, self::vig(self::VIGENCIA), EstadoValidacion::SinConfirmar);
            }
            foreach ($aplicabilidadExtra[$codigo] ?? [] as $r) {
                $reglas[] = $r;
            }
            $snapshot = $snap->construir($c, $incid[$codigo] ?? [], $bz, $reglas);
            $out[$codigo] = new ConceptoVersion($id, $c->id, $codigo, 1, self::vig(self::VIGENCIA), $snapshot, $snap->hash($snapshot), 1, 'v1', null, false, new DateTimeImmutable('2026-07-01'));
            $id++;
        }

        return $out;
    }

    public static function contextoPeriodo(Entorno $entorno = Entorno::Prueba, TipoLiquidacion $tipo = TipoLiquidacion::Normal, array $versiones = [], EstadoValidacion $estadoNormativa = EstadoValidacion::SinConfirmar): ContextoPeriodo
    {
        return new ContextoPeriodo(self::periodo(), $tipo, $entorno, $versiones ?: self::versiones(), self::basesArca(), self::subsistemas(), self::aplicaciones(),
            self::alicuotasContribuciones($estadoNormativa), self::alicuotasAportes($estadoNormativa), self::topes($estadoNormativa), self::regimenes($estadoNormativa));
    }

    /** RL-0003 AGUIRRE ENRIQUE, legajo 7019, CCT 371/03, categoría 104, ingreso 2018-06-11 (8 años a julio 2026). */
    public static function relacionAguirre(): RelacionLaboral
    {
        return new RelacionLaboral(3, 3, 1, '7019', new DateTimeImmutable('2018-06-11'), null, EstadoRelacion::Vigente, '20300000003', 'AGUIRRE ENRIQUE ISMAEL', null,
            [new CategoriaVigente(3, 104, 'EST_OP_INT_ANEXO', self::vig('2018-06-11'), EstadoValidacion::SinConfirmar)],
            new EncuadreArca(3, '1', 'GENERAL', null, '8', null, self::vig('2018-06-11'), EstadoValidacion::SinConfirmar),
            [new RegimenAsignado(3, 1, 'GENERAL', self::vig('2018-06-11'), EstadoValidacion::SinConfirmar)]);
    }

    /** RL-0004 ALFARO SERGIO, legajo 221, CCT 40/89, categoría 201 (conductor 1.ª), ingreso 2009-07-01 (17 años), GENERAL + TRANSP_CARGAS. */
    public static function relacionAlfaro(array $regimenesExtra = ['TRANSP_CARGAS']): RelacionLaboral
    {
        $reg = [new RegimenAsignado(4, 1, 'GENERAL', self::vig('2009-07-01'), EstadoValidacion::SinConfirmar)];
        $ids = ['TRANSP_CARGAS' => 2, 'RIFL' => 7, 'CONSTRUCCION' => 4];
        foreach ($regimenesExtra as $codigo) {
            $reg[] = new RegimenAsignado(null, $ids[$codigo], $codigo, self::vig('2026-05-01'), EstadoValidacion::SinConfirmar);
        }

        return new RelacionLaboral(4, 4, 1, '221', new DateTimeImmutable('2009-07-01'), null, EstadoRelacion::Vigente, '20300000004', 'ALFARO SERGIO RODRIGO', null,
            [new CategoriaVigente(4, 201, 'CAM_COND_1', self::vig('2009-07-01'), EstadoValidacion::SinConfirmar)],
            new EncuadreArca(4, '5', 'CONDUCTOR_CARGAS', null, '8', null, self::vig('2009-07-01'), EstadoValidacion::SinConfirmar), $reg);
    }

    /** @param list<Novedad> $novedades */
    public static function contexto(RelacionLaboral $rl, int $idConvenio, ?ModeloAntiguedad $modelo, array $novedades, ?JornadaCanonica $jornada = null, ?string $encuadramiento = 'B', int $dias = 30): ContextoRelacionLaboral
    {
        $cat = $rl->categoriaVigenteA(self::periodo()->ultimoDia());
        $enc = $encuadramiento === null ? null : new EncuadramientoEmpresa(1, $rl->empresaId, $encuadramiento, self::vig('2026-01-01'), EstadoValidacion::SinConfirmar);

        return new ContextoRelacionLaboral($rl, self::periodo()->ultimoDia(), $cat?->idCategoria, $cat?->codigoCategoria, $idConvenio, $idConvenio === 1 ? '371/03' : '40/89',
            $jornada ?? new JornadaCanonica(1, 'Jornada completa', 48, false), $enc, $modelo, $novedades, $dias);
    }

    public static function novedad(int $rlId, string $codigo, string $cantidad): Novedad
    {
        return new Novedad(null, self::periodo(), $rlId, $codigo, BigDecimal::of($cantidad), null, 'FIXTURE');
    }
}
