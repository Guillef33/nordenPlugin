<?php

if (!defined('ABSPATH')) exit;
function resultado_cotizador_auto() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';

        $token = obtener_token_norden();

        if (!$token) {
            echo '<p style="color:red;">Error de autenticación. Intente más tarde.</p>';
            return;
        } else {
            echo '<p style="color:green;">Se ha autenticado correctamente.</p>';
        }
        
        $arr=explode(" - ",$_POST['codigo_postal']);
        $intId=$arr[0];
        $cp=$arr[1];
        $cpName=$arr[2];

        // Metodo Sancor


        $provincia_sancor=obtener_provincia_sancor(sanitize_text_field($_POST['provincia']), $token);
        $localidades_sancor=obtener_localidad_sancor(sanitize_text_field($cp), $token);

        
        $sancorLocalidad=compare_strings($cpName,$localidades_sancor)["Value"];


        echo '<pre>Respuesta API sancor: ';
        print_r($sancorLocalidad);
        echo '</pre>';

        // Fin metodo Sancor

        $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';

        $params = [
            "ParametrosGenerales" => [
                "ProductorVendedor" => "208",
                "Año" => sanitize_text_field($_POST['anio'] ?? ''),
                "CeroKm" => false,
                "CodVehiculoExterno" => sanitize_text_field($_POST['cod_vehiculo'] ?? ''),
                "Provincia" => sanitize_text_field($_POST['provincia'] ?? ''),
                "Localidad" => sanitize_text_field($_POST['localidad'] ?? ''),
                "MedioDePago" => "T",
                "TipoFacturacion" => "M",
                "TipoIva" => "CF",
                "TipoPersona" => "P",
                "FechaNacimiento" => sanitize_text_field($_POST['fecha_nacimiento'] ?? ''),
                "Sexo" => sanitize_text_field($_POST['sexo'] ?? ''),
                "EstadoCivil" => sanitize_text_field($_POST['estado_civil'] ?? ''),
                "SnGNC" => "N",
                "ValuacionGNC" => ""
            ],
            "ParametrosEspecificos" => [
                "Sancor" => [
                    "ClausulaAjuste" => "0",
                    "NeumaticosAuxiliares" => "1",
                    "Garage" => "1",
                    "KilometrosAnuales" => "1",
                    "TipoIva" => "4",
                    "PlanDePago" => "0",
                    "FechaEmisionValor" => "2025-04-23 00:00:00",
                    "Provincia" => "01",
                    "Localidad" => $sancorLocalidad,
                    "Menor25Años" => "2",
                    "DescuentoEspecial" => "0",
                    "TipoFacturacionCustom" => ""
                ],
                "Zurich" => [
                    "Beneficio" => "1",
                    "ClausulaAjuste" => "A",
                    "Descuento" => "10",
                    "Comision" => "10",
                    "DescuentoComision" => "10",
                    "PlanDePago" => "91",
                    "Rastreador" => "1",
                    "TipoIva" => "1",
                    "EstadoCivil" => "1",
                    "Provincia" => "01",
                    "IdPlan" => "350",
                    "Localidad" => "001",
                    "Asistencia" => "31",
                    "TipoFacturacionCustom" => "M"
                ]
            ]
        ];

        $url_with_params = add_query_arg(['data' => json_encode($params)], $url_cotizar);

        
        if (($url_with_params)) {
            echo '<pre>La URL con parametros enviada fue: ';
            print_r( $url_with_params);
            echo '</pre>';
            return null;
        }


        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 20,
        ];

        $response = wp_remote_get($url_with_params, $args);

        if (is_wp_error($response)) {
            echo '<pre>Error WP: ';
            print_r($response->get_error_message());
            echo '</pre>';
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        echo '<pre>Respuesta API: ';
        print_r($body);
        echo '</pre>';

        // if (!empty($body['Data']['Cotizaciones'])) {
            ob_start();
            echo '<h3>Resultados de Cotización</h3>';
            foreach ($body['Data']['Cotizaciones'] as $coti) {
                echo '<p>Plan: ' . esc_html($coti['DescripcionPlan']) . ' - Prima: $' . esc_html($coti['Prima']) . '</p>';
            }
            return ob_get_clean();
        // } else {
        //     return '<p>No se encontraron cotizaciones disponibles.</p>';
        // }
    }

    return '<p>Formulario no enviado.</p>';
}

function compare_strings($fraseObjetivo, $resultados) {
    $mejorSimilitud = -1;
    $mejorCoincidencia = null;

    foreach ($resultados as $oracion) {
        similar_text($fraseObjetivo, $oracion["Text"], $porcentaje);
        if ($porcentaje > $mejorSimilitud) {
            $mejorSimilitud = $porcentaje;
            $mejorCoincidencia = $oracion;
        }
    }

    return $mejorCoincidencia;
}
