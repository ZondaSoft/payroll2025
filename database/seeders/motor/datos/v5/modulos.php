<?php

// Generado por generar_datos_v5.py desde el Excel funcional v5 (DBML v2). No editar a mano.
// Hoja MODULOS — 3 filas.

return [
    ['codigo' => 'ANTIGUEDAD.PORCENTAJE', 'descripcion' => 'Antigüedad → porcentaje por años, según el modelo de antigüedad vigente del convenio', 'unidad_resultado' => 'PORCENTAJE', 'version' => 'v1', 'requiere_historico' => 0, 'meses_historico' => null, 'activo' => 1, 'observaciones' => 'DSL v1.1 §16. Tramos por convenio (CONVENIO_MODELO_ANTIGUEDAD + ANTIGUEDAD_TRAMOS).'],
    ['codigo' => 'PROMEDIOS.VARIABLE_6M', 'descripcion' => 'Promedio de los conceptos variables de los últimos seis períodos', 'unidad_resultado' => 'ARS', 'version' => 'v1', 'requiere_historico' => 1, 'meses_historico' => '6', 'activo' => 0, 'observaciones' => 'Mes incompleto de Larga Distancia: regla base decidida el 2026-09-16 (promedio / 30 × días liquidados). Pendiente: fuente histórica, clasificación fijo/variable y casos borde. Si falta historia bloquea (MOD_HISTORICO_INSUFICIENTE), nunca cero.'],
    ['codigo' => 'SEG_SOCIAL.APORTE_JUBILATORIO', 'descripcion' => 'Seguridad Social → aporte jubilatorio SIPA', 'unidad_resultado' => 'ARS', 'version' => 'v0-prueba', 'requiere_historico' => 0, 'meses_historico' => null, 'activo' => 0, 'observaciones' => 'Etapa 3A EN PRUEBA (sim/motor_contributivo.js). Sin dependencias DSL declaradas todavía.'],
];
