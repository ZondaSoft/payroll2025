<?php

// Generado por generar_datos_v5.py desde el Excel funcional v5 (DBML v2). No editar a mano.
// Hoja VARIABLES — 8 filas.

return [
    ['codigo' => 'BASCAT', 'descripcion' => 'Básico de escala de la categoría del trabajador', 'unidad' => 'ARS', 'origen' => 'CATEGORIA', 'activo' => 1, 'observaciones' => 'Se resuelve desde BASICOS por categoría y vigencia. No se copia a VALORES_VARIABLE.'],
    ['codigo' => 'HSPACT', 'descripcion' => 'Horas semanales pactadas del trabajador', 'unidad' => 'HORAS', 'origen' => 'RELACION_LABORAL', 'activo' => 1, 'observaciones' => 'Sin fallback desde la categoría ni desde la jornada. En el perfil LARGA_DISTANCIA no existe: no es un dato faltante.'],
    ['codigo' => 'HSJORN', 'descripcion' => 'Horas semanales de la jornada completa aplicable', 'unidad' => 'HORAS', 'origen' => 'CONVENIO', 'activo' => 1, 'observaciones' => 'JORNADAS.horas_semanales de la jornada del convenio.'],
    ['codigo' => 'HDJORN', 'descripcion' => 'Horas diarias de la jornada', 'unidad' => 'HORAS', 'origen' => 'CONVENIO', 'activo' => 1, 'observaciones' => 'JORNADAS.horas_dia de la jornada del convenio.'],
    ['codigo' => 'HMJORN', 'descripcion' => 'Horas mensuales de la jornada', 'unidad' => 'HORAS', 'origen' => 'CONVENIO', 'activo' => 1, 'observaciones' => 'JORNADAS.horas_mensuales de la jornada del convenio (192 / 200).'],
    ['codigo' => 'DIVJOR', 'descripcion' => 'Divisor de jornal diario — jornales del mes', 'unidad' => 'DIAS', 'origen' => 'CONVENIO', 'activo' => 1, 'observaciones' => 'P-10-b cerrado el 2026-09-13. No se deriva de HMJORN/HDJORN, no reutiliza Dias_Garantia y no se duplica en PARAMETROS.'],
    ['codigo' => 'DIASLIQ', 'descripcion' => 'Días liquidados del período', 'unidad' => 'DIAS', 'origen' => 'PERIODO', 'activo' => 1, 'observaciones' => 'Se resuelve en la liquidación (LIQUIDACION_RELACION.dias_liquidados).'],
    ['codigo' => 'ANTANIO', 'descripcion' => 'Años de antigüedad', 'unidad' => 'NUMERIC', 'origen' => 'RELACION_LABORAL', 'activo' => 1, 'observaciones' => 'Se calcula desde la relación laboral.'],
];
