<?php

if (!defined('ABSPATH')) exit;

function obtener_provincia_experta($provincia, $token) {
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/experta/getcodprovinciaexperta";
    
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'timeout' => 15
        ];

        $url = $url_base . '?' . http_build_query([
            'codprovincia' => $provincia,
        ]);
    
    
        $response = wp_remote_get($url, $args);
    
        if (is_wp_error($response)) {
            echo '<pre>Error al convertir provincia: ';
            print_r($response->get_error_message());
            echo '</pre>';
            return [];
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        return $body["Data"] ? $body["Data"] : [];
    }
    
    function obtener_localidad_experta($codigoPostal, $provincia, $token) {
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/experta/localidades";
    
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'timeout' => 15
        ];

        $url = $url_base . '?' . http_build_query([
            'codprovinciaexperta' => $provincia,
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
    
