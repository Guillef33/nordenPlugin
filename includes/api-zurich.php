<?php

if (!defined('ABSPATH')) exit;

function obtener_provincia_zurich($provincia, $token)
{
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/zurich/provincia";

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token
        ],
        'timeout' => 15
    ];

    $url = $url_base . '?' . http_build_query([
        'Provincia' => $provincia,
    ]);


    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        echo '<pre>Error al convertir provincia: ';
        print_r($response->get_error_message());
        echo '</pre>';
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return $body["Data"][0]["Value"] ? $body["Data"][0]["Value"] : [];
}

function obtener_localidad_zurich($codigoPostal, $provincia, $token)
{
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/zurich/localidad";

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token
        ],
        'timeout' => 15
    ];

    $url = $url_base . '?' . http_build_query([
        'provincia' => $provincia,
        'codigopostal' => $codigoPostal,
    ]);


    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        echo '<pre>Error al convertir codigo posatl: ';
        print_r($response->get_error_message());
        echo '</pre>';
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);


    return $body["Data"] ? $body["Data"] : [];
}


function obtener_planId($productorvendedor, $token)
{
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/zurich/planid";

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token
        ],
        'timeout' => 15
    ];

    $url = $url_base . '?' . http_build_query([
        'productorvendedor' => $productorvendedor,

    ]);


    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        echo '<pre>Error al obtener PlanId: ';
        print_r($response->get_error_message());
        echo '</pre>';
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);


    return $body["Data"] ? $body["Data"][0]["Value"] : [];
}
