<?php

function formulario_cotizacion_auto() {
    ob_start();

    $token = obtener_token_norden();
    // // $codigos_postales = obtener_codigos_postales($token, '02'); 

    // if (!is_array($codigos_postales)) {
    //     $codigos_postales = [];
    // }

    $marcas = obtener_marcas_norden($token);


    $modelos = [
        ['Codigo' => '1', 'Nombre' => 'Corolla'],
        ['Codigo' => '2', 'Nombre' => 'Focus'],
        ['Codigo' => '3', 'Nombre' => 'Golf'],
    ];

    $provincias = obtener_provincias_norden($token);

    include plugin_dir_path(__FILE__) . 'formulario-html.php';

    return ob_get_clean();
}

