<?php

/*
 * Configuración del Motor de Cálculo (Zonda Sueldos).
 *
 * Deliberadamente mínima: los valores numéricos de cálculo (REDONDEO_DECIMALES,
 * DIVISOR_DIAS, HORAS_MES_REFERENCIA) NO viven acá sino en motor_parametros con
 * vigencia y estado de validación, para que exista una única fuente de verdad y
 * el motor pueda bloquear si faltan (fail-closed).
 */
return [

    // Entorno por defecto de cada corrida del simulador. En PRODUCCION solo los
    // datos CONFIRMADO habilitan el cálculo; en PRUEBA, SIN_CONFIRMAR pasa con aviso.
    'entorno' => env('MOTOR_ENTORNO', 'PRUEBA'),

    // Conceptos que el panel de integridad exige que existan y estén activos.
    // OBLIGATORIO: siempre. CONDICIONAL: solo si aplica al empleador (p. ej. RENATRE rural).
    'conceptos_minimos_sistema' => [
        ['codigo' => 'APJUB',     'exigencia' => 'OBLIGATORIO',  'descripcion' => 'Aporte jubilatorio (SIPA)'],
        ['codigo' => 'APINSSJYP', 'exigencia' => 'OBLIGATORIO',  'descripcion' => 'Aporte INSSJP (Ley 19.032)'],
        ['codigo' => 'APOSOC',    'exigencia' => 'OBLIGATORIO',  'descripcion' => 'Aporte obra social'],
        ['codigo' => 'CONTSS',    'exigencia' => 'OBLIGATORIO',  'descripcion' => 'Contribución seguridad social'],
        ['codigo' => 'CONTOS',    'exigencia' => 'OBLIGATORIO',  'descripcion' => 'Contribución obra social'],
        ['codigo' => 'CONTART',   'exigencia' => 'OBLIGATORIO',  'descripcion' => 'Contribución ART (LRT)'],
        ['codigo' => 'CONTDIF',   'exigencia' => 'CONDICIONAL',  'descripcion' => 'Contribución diferencial (regímenes especiales)'],
        ['codigo' => 'CONTSCVO',  'exigencia' => 'CONDICIONAL',  'descripcion' => 'Seguro colectivo de vida obligatorio'],
        ['codigo' => 'CUOSIN',    'exigencia' => 'CONDICIONAL',  'descripcion' => 'Cuota sindical (solo afiliados)'],
        ['codigo' => 'APSOL',     'exigencia' => 'CONDICIONAL',  'descripcion' => 'Aporte solidario sindical'],
        ['codigo' => 'MUTSIND',   'exigencia' => 'CONDICIONAL',  'descripcion' => 'Mutual sindical'],
        ['codigo' => 'CONTSOL',   'exigencia' => 'CONDICIONAL',  'descripcion' => 'Contribución solidaria'],
        ['codigo' => 'SEGSEP',    'exigencia' => 'CONDICIONAL',  'descripcion' => 'Seguro de sepelio'],
    ],
];
