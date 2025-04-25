<?php

function formulario_cotizacion_auto() {
    ob_start();

    $token = obtener_token_norden();

    $marcas = obtener_marcas_norden($token);

    $provincias = obtener_provincias_norden($token);

    $provincia = '02';

    $codigos_postales = obtener_codigos_postales($token, $provincia);

    include plugin_dir_path(__FILE__) . 'formulario-html.php';

    return ob_get_clean();
}

