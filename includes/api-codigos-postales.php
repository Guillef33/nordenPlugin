<?php

function obtener_codigos_postales($token, $provincias) {
    $url_base = 'https://quickbi4.norden.com.ar/api/general/ubicacion/listacodigopostal';

    $url = $url_base . '?' . http_build_query([
        'Pais' => '054',
        'Provincias' => $provincias,
    ]);

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 15,
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        error_log('Error al obtener códigos postales: ' . $response->get_error_message());
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return !empty($body['Data']) ? $body['Data'] : [];
}
