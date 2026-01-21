<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reglas básicas
    |--------------------------------------------------------------------------
    */
    'required' => 'El campo :attribute es obligatorio.',
    'integer'  => 'El campo :attribute debe ser un número entero.',
    'numeric'  => 'El campo :attribute debe ser numérico.',
    'unique'   => 'El valor ingresado para :attribute ya existe.',

    /*
    |--------------------------------------------------------------------------
    | Reglas que DEBEN ser arrays
    |--------------------------------------------------------------------------
    */
    'max' => [
        'numeric' => 'El campo :attribute no puede ser mayor a :max.',
        'string'  => 'El campo :attribute no puede superar :max caracteres.',
        'array'   => 'El campo :attribute no puede tener más de :max elementos.',
        'file'    => 'El archivo :attribute no puede superar :max kilobytes.',
    ],

    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string'  => 'El campo :attribute debe tener al menos :min caracteres.',
        'array'   => 'El campo :attribute debe tener al menos :min elementos.',
        'file'    => 'El archivo :attribute debe tener al menos :min kilobytes.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Atributos personalizados
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'codigo'  => 'código',
        'detalle' => 'detalle',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensajes personalizados por campo
    |--------------------------------------------------------------------------
    */
    'custom' => [
        'codigo' => [
            'unique' => 'Ya existe una actividad SICOSS con ese código.',
        ],
    ],
];
