<?php

function formulario_cotizacion_auto() {
    ob_start();

    $token = obtener_token_norden();
    // $codigos_postales = obtener_codigos_postales($token, '02'); 

    if (!is_array($codigos_postales)) {
        $codigos_postales = [];
    }

    $marcas = [
        ['Codigo' => '1', 'Nombre' => 'Toyota'],
        ['Codigo' => '2', 'Nombre' => 'Ford'],
        ['Codigo' => '3', 'Nombre' => 'Volkswagen'],
    ];

    $modelos = [
        ['Codigo' => '1', 'Nombre' => 'Corolla'],
        ['Codigo' => '2', 'Nombre' => 'Focus'],
        ['Codigo' => '3', 'Nombre' => 'Golf'],
    ];

    $provincias = [
        ['Codigo' => '1', 'Nombre' => 'Buenos Aires'],
        ['Codigo' => '2', 'Nombre' => 'CABA'],
        ['Codigo' => '3', 'Nombre' => 'Cordoba'],   
    ];

    include plugin_dir_path(__FILE__) . 'formulario-html.php';

    return ob_get_clean();
}

