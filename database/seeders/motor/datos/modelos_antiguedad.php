<?php

// Generado por database/seeders/motor/herramientas/generar_datos.py desde la hoja MODELOS_ANTIGUEDAD
// de Zonda_Motor_Calculo_2026-08-30.xlsx. No editar a mano: regenerar.

return [
    ['id_modelo_antiguedad' => 1, 'codigo' => 'ANTIG_2_3_TOPE70_V1', 'descripcion' => '2 % anual hasta 20 años, luego 3 % anual, con tope de 70 %', 'metodo_calculo' => 'ESCALA', 'porcentaje_por_anio' => null, 'porcentaje_maximo' => '0.7', 'version' => 1, 'estado' => 'VIGENTE', 'estado_validacion' => 'SIN_CONFIRMAR', 'activo' => 1, 'observaciones' => null],
    ['id_modelo_antiguedad' => 2, 'codigo' => 'ANTIG_1_PCT_ANUAL_V1', 'descripcion' => '1 % por cada año de antigüedad', 'metodo_calculo' => 'LINEAL', 'porcentaje_por_anio' => '0.01', 'porcentaje_maximo' => null, 'version' => 1, 'estado' => null, 'estado_validacion' => 'SIN_CONFIRMAR', 'activo' => 1, 'observaciones' => 'Modelo LINEAL sin porcentaje máximo configurado: el adicional crece sin tope a medida que aumenta la antigüedad. Ese comportamiento extremo es el motivo por el cual la asignación requiere confirmación normativa. Antes este motivo estaba dentro del estado, como VALIDAR_EXTREMO.'],
];
