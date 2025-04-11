<?php

if (!defined('ABSPATH')) exit;

function obtener_token_norden() {
    $url = 'https://quickbi4.norden.com.ar/api_externa/seguridad/autenticacion/login';

    $datos_login = [
        "Username" => "ApiQuick",
        "Password" => "a9_e3G_5x4A7"
    ];

    $args = [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($datos_login),
        'timeout' => 15,
    ];

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('Error WP: ' . $response->get_error_message());
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($body['Data']['Token'])) {
        return $body['Data']['Token'];
    }

    return null;
}
