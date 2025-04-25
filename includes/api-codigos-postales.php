<?php

function obtener_codigos_postales($token, $provincia = '02') {
    $url_base = 'https://quickbi4.norden.com.ar/api/general/ubicacion/listacodigopostal';

    $url = $url_base . '?' . http_build_query([
        'Pais' => '054',
        'Provincia' => '02',
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

    return is_array($body) ? $body : [];
}
