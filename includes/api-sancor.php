<?php

if (!defined('ABSPATH')) exit;

function obtener_provincia_sancor($provincia, $token) {
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/sancor/provincia";
    
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
    
    function obtener_localidad_sancor($codigoPostal, $provincia, $token) {
    $url_base = "https://quickbi4.norden.com.ar/api/conversion/sancor/localidadRest";
    
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'timeout' => 15
        ];

        $url = $url_base . '?' . http_build_query([
            'idProductor' => "207",
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
    

    function get_multiple_provincias($provincia, $token) {
    $urls = [
        'zurich' => "https://quickbi4.norden.com.ar/api/conversion/zurich/provincia?" . http_build_query(['Provincia' => $provincia]),
        'experta' => "https://quickbi4.norden.com.ar/api/conversion/experta/getcodprovinciaexperta?" . http_build_query(['codprovincia' => $provincia]),
        'sancor' => "https://quickbi4.norden.com.ar/api/conversion/sancor/provincia?" . http_build_query(['Provincia' => $provincia]),
    ];

    $mh = curl_multi_init();
    $curl_handles = [];
    $results = [];

    foreach ($urls as $key => $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token
            ]
        ]);
        curl_multi_add_handle($mh, $ch);
        $curl_handles[$key] = $ch;
    }

    // Ejecutar en paralelo
    do {
        $status = curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    // Obtener resultados
    foreach ($curl_handles as $key => $ch) {
        $body = curl_multi_getcontent($ch);
        $response = json_decode($body, true);

        $results[$key] = $response["Data"][0]["Value"] ?? null;
        if($results[$key]==null){
            $results[$key] = $response["Data"] ?? null;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $results;
}

function get_multiple_localidades($cp, $cpName, $prov_codigos, $token) {
    $urls = [
        'zurich' => "https://quickbi4.norden.com.ar/api/conversion/zurich/localidad?" . http_build_query([
            'provincia' => $prov_codigos['zurich'],
            'codigopostal' => $cp,
        ]),
        'experta' => "https://quickbi4.norden.com.ar/api/conversion/experta/localidades?" . http_build_query([
            'codprovinciaexperta' => $prov_codigos['experta'],
            'codigopostal' => $cp,
        ]),
        'sancor' => "https://quickbi4.norden.com.ar/api/conversion/sancor/localidadRest?" . http_build_query([
            'idProductor' => 207,
            'codigopostal' => $cp,
        ]),
    ];

    $mh = curl_multi_init();
    $curl_handles = [];
    $results = [];

    foreach ($urls as $key => $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token
            ]
        ]);
        curl_multi_add_handle($mh, $ch);
        $curl_handles[$key] = $ch;
    }

    do {
        $status = curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    foreach ($curl_handles as $key => $ch) {
        $body = curl_multi_getcontent($ch);
        $response = json_decode($body, true);
        $localidades = $response["Data"] ?? [];
        $results[$key] = compare_strings($cpName, $localidades)["Value"] ?? null;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $results;
}
