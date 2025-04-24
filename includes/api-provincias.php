<?php

if (!defined('ABSPATH')) exit;

function obtener_provincias_norden($token) {
    $url = 'https://quickbi4.norden.com.ar/api/general/ubicacion/listaprovincia?pais=054';

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token
        ],
        'timeout' => 15
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        echo '<pre>Error al obtener provincias: ';
        print_r($response->get_error_message());
        echo '</pre>';
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return is_array($body) ? $body : [];
}

