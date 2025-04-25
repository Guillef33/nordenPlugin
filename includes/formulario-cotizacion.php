<?php

function formulario_cotizacion_auto() {
    ob_start();

    $token = obtener_token_norden();

    $marcas = obtener_marcas_norden($token);


    $modelos = [
        ['Codigo' => '1', 'Nombre' => 'Corolla'],
        ['Codigo' => '2', 'Nombre' => 'Focus'],
        ['Codigo' => '3', 'Nombre' => 'Golf'],
    ];

    $provincias = obtener_provincias_norden($token);

    $provincia = '02';

    $codigos_postales = obtener_codigos_postales($token, $provincia);

    include plugin_dir_path(__FILE__) . 'formulario-html.php';

    return ob_get_clean();
}

