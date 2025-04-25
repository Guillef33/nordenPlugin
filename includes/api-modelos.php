<?php

add_action('rest_api_init', function () {
    register_rest_route('mi-plugin/v1', '/modelos', array(
        'methods' => 'GET',
        'callback' => 'mi_plugin_modelos_handler',
        'permission_callback' => '__return_true'
    ));
});

function mi_plugin_modelos_handler(WP_REST_Request $request) {
    $marca = $request->get_param('marca');
    $anio = $request->get_param('anio');

    if (!$marca) {
        return new WP_REST_Response(['error' => 'Marca requerida'], 400);
    }

    if (!$marca) {
        return new WP_REST_Response(['error' => 'Modelo requerido'], 400);
    }

    $token = obtener_token_norden(); // Tu función existente
    $codigos = obtener_codigos_postales($token, $provincia); // Tu función existente

    return new WP_REST_Response($codigos, 200);
}

function obtener_modelos_norden($token, $marca, $anio) {

    if(!$marca || !$anio){
        return;
    }

    $url_base = 'https://quickbi4.norden.com.ar/api_externa/autos/vehiculo/listamodeloanio';

    $url = $url_base . '?' . http_build_query([
        'Marca' => $marca,
        'Anio' => $anio,
    ]);

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token
        ],
        'timeout' => 15
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        echo '<pre>Error al obtener modelos: ';
        print_r($response->get_error_message());
        echo '</pre>';
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return is_array($body) ? $body : [];
}

