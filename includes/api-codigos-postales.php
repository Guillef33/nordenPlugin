<?php

add_action('rest_api_init', function () {
    register_rest_route('mi-plugin/v1', '/codigos-postales', array(
        'methods' => 'GET',
        'callback' => 'mi_plugin_codigos_postales_handler',
        'permission_callback' => '__return_true'
    ));
});

function mi_plugin_codigos_postales_handler(WP_REST_Request $request)
{
    $provincia = $request->get_param('provincia');

    if (!$provincia) {
        return new WP_REST_Response(['error' => 'Provincia requerida'], 400);
    }

    $token = obtener_token_norden(); // Tu función existente
    $codigos = obtener_codigos_postales($token, $provincia); // Tu función existente

    return new WP_REST_Response($codigos, 200);
}

function obtener_codigos_postales($token, $provincia = '02')
{
    $url_base = 'https://quickbi4.norden.com.ar/api/general/ubicacion/listacodigopostal';

    $url = $url_base . '?' . http_build_query([
        'Pais' => '054',
        'Provincia' => $provincia,
    ]);

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
        ],
        'timeout' => 15,
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        echo '<pre>Error al obtener codigos postales: ';
        print_r($response->get_error_message());
        echo '</pre>';
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!function_exists('compareByName')) {
        function compareByName($a, $b)
        {
            return strcmp($a["Text"], $b["Text"]);
        }
    }

    usort($body["Data"], 'compareByName');

    return is_array($body) ? $body : [];
}
