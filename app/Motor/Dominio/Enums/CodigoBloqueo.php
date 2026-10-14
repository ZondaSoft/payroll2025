<?php

namespace App\Motor\Dominio\Enums;

enum CodigoBloqueo: string
{
    case ParametroSinValor = 'PARAMETRO_SIN_VALOR';
    case ParametroEmpate = 'PARAMETRO_EMPATE';
    case ParametroTecnicoAusente = 'PARAMETRO_TECNICO_AUSENTE';
    /** VAR() del DSL sin valor vigente, o con dos valores del mismo nivel. */
    case VariableSinValor = 'VARIABLE_SIN_VALOR';
    /** VALCAT() sin valor para la categoría del trabajador. */
    case ValorCategoriaAusente = 'VALCAT_SIN_VALOR';
    /** El perfil no eligió variante, o dos perfiles del mismo nivel se contradicen. */
    case PerfilSinRegla = 'PERFIL_SIN_REGLA';
    case PerfilAmbiguo = 'PERFIL_AMBIGUO';
    /** MOD() invocado sobre un módulo que no está registrado o está inactivo. */
    case ModuloSinResultado = 'MOD_SIN_RESULTADO';
    case BasicoSinImporte = 'BASICO_SIN_IMPORTE';
    case BasicoAmbiguo = 'BASICO_AMBIGUO';
    case TopeSinParametrizacion = 'TOPE_SIN_PARAMETRIZACION';
    case TopeImporteVacio = 'TOPE_IMPORTE_VACIO';
    case TopeDuplicado = 'TOPE_DUPLICADO';
    case EstadoNoHabilitado = 'ESTADO_NO_HABILITADO';
    case IncidenciaPendiente = 'INCIDENCIA_PENDIENTE';
    case ConflictoPerfiles = 'CONFLICTO_PERFILES';
    case NovedadObligatoriaAusente = 'NOVEDAD_OBLIGATORIA_AUSENTE';
    case NovedadSinMapeo = 'NOVEDAD_SIN_MAPEO';
    case EstrategiaSinEjecutor = 'ESTRATEGIA_SIN_EJECUTOR';
    case ConceptoSinVersion = 'CONCEPTO_SIN_VERSION';
    case CircularidadBase = 'CIRCULARIDAD_BASE';
    case RelacionInconsistente = 'RL_INCONSISTENTE';
    case RelacionInexistente = 'RL_INEXISTENTE';
    case EncuadramientoAusente = 'ENCUADRAMIENTO_AUSENTE';
    case AlicuotaAusente = 'ALICUOTA_AUSENTE';
    case RegimenSinBase = 'REGIMEN_SIN_BASE';
    case BaseZondaInexistente = 'BASE_ZONDA_INEXISTENTE';
    /** Una base ARCA que otra etapa necesita no quedó construida en la corrida. */
    case BaseNoConstruida = 'BASE_NO_CALCULADA';
    case JornadaSinDato = 'JORNADA_SIN_DATO';
    case ConfiguracionInvalida = 'CONFIGURACION_INVALIDA';
    case PeriodicidadDesconocida = 'PERIODICIDAD_DESCONOCIDA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ParametroSinValor => 'Parámetro sin valor vigente',
            self::ParametroEmpate => 'Empate de parámetro en el mismo nivel',
            self::ParametroTecnicoAusente => 'Parámetro técnico del motor ausente',
            self::VariableSinValor => 'Variable del DSL sin valor vigente',
            self::ValorCategoriaAusente => 'Valor por categoría ausente',
            self::PerfilSinRegla => 'Ningún perfil se pronuncia sobre el concepto',
            self::PerfilAmbiguo => 'Dos perfiles del mismo nivel se contradicen',
            self::ModuloSinResultado => 'Módulo sin resultado',
            self::BasicoSinImporte => 'Categoría sin básico vigente',
            self::BasicoAmbiguo => 'Más de un básico vigente',
            self::TopeSinParametrizacion => 'Falta parametrización de tope',
            self::TopeImporteVacio => 'Tope con importe vacío',
            self::TopeDuplicado => 'Tope duplicado (configuración ambigua)',
            self::EstadoNoHabilitado => 'Estado de validación no habilitado para el entorno',
            self::IncidenciaPendiente => 'Incidencia ARCA pendiente',
            self::ConflictoPerfiles => 'Conflicto de aplicabilidad de igual nivel',
            self::NovedadObligatoriaAusente => 'Novedad obligatoria ausente',
            self::NovedadSinMapeo => 'Novedad de origen sin mapeo',
            self::EstrategiaSinEjecutor => 'Estrategia de cálculo sin ejecutor',
            self::ConceptoSinVersion => 'Concepto sin versión publicada',
            self::CircularidadBase => 'Circularidad entre bases de Zonda',
            self::RelacionInconsistente => 'Relación laboral inconsistente con el origen',
            self::RelacionInexistente => 'Relación laboral inexistente',
            self::EncuadramientoAusente => 'Empresa sin encuadramiento patronal vigente',
            self::AlicuotaAusente => 'Alícuota no parametrizada',
            self::RegimenSinBase => 'Sin régimen BASE para el trabajador',
            self::BaseZondaInexistente => 'Base de Zonda inexistente o inactiva',
            self::JornadaSinDato => 'Jornada sin dato y sin referencia',
            self::ConfiguracionInvalida => 'Configuración inválida del concepto',
            self::PeriodicidadDesconocida => 'Periodicidad desconocida',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function opciones(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->etiqueta()], self::cases());
    }
}
