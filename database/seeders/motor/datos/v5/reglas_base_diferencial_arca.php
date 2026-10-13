<?php

// Generado por generar_datos_v5.py desde el Excel funcional v5 (DBML v2). No editar a mano.
// Hoja REGLAS_BASE_DIFERENCIAL_ARCA — 2 filas.

return [
    ['id_regla_base_diferencial' => 'BD-001', 'codigo' => 'OS_TIEMPO_PARCIAL', 'codigo_base' => 'REM_4', 'tipo_diferencial' => 'APORTE_OS', 'disparador' => 'JORNADA_PARCIAL / encuadre ARCA vigente', 'metodo' => 'MAX(0, BASE_OS_JORNADA_COMPLETA - BASE_CONCEPTOS_REM4)', 'modulo_responsable' => 'SEGURIDAD_SOCIAL', 'vigencia_desde' => null, 'vigencia_hasta' => null, 'estado_validacion' => 'PENDIENTE_VIGENCIA', 'fuente_normativa' => 'Art. 92 ter LCT + ARCA G18', 'observaciones' => 'BASE_OS_JORNADA_COMPLETA se resuelve por categoría/puesto y vigencia. | Estado original v3: CONFIRMADO.'],
    ['id_regla_base_diferencial' => 'BD-002', 'codigo' => 'OS_TIEMPO_PARCIAL', 'codigo_base' => 'REM_8', 'tipo_diferencial' => 'CONTRIB_OS', 'disparador' => 'JORNADA_PARCIAL / encuadre ARCA vigente', 'metodo' => 'MAX(0, BASE_OS_JORNADA_COMPLETA - BASE_CONCEPTOS_REM8)', 'modulo_responsable' => 'SEGURIDAD_SOCIAL', 'vigencia_desde' => null, 'vigencia_hasta' => null, 'estado_validacion' => 'PENDIENTE_VIGENCIA', 'fuente_normativa' => 'Art. 92 ter LCT + ARCA G18', 'observaciones' => 'Mismo diferencial validado en BLEXA 08/2026. | Estado original v3: CONFIRMADO.'],
];
