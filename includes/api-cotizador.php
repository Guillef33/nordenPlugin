<?php

if (!defined('ABSPATH')) exit;
function resultado_cotizador_auto() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';

        $token = obtener_token_norden();
        
        $arr=explode(" - ",$_POST['codigo_postal']);
        $intId=$arr[0];
        $cp=$arr[1];
        $cpName=$arr[2];

        // Metodo Sancor

        $provincia_sancor=obtener_provincia_sancor(sanitize_text_field($_POST['provincia']), $token);
        $localidades_sancor=obtener_localidad_sancor(sanitize_text_field($cp), $provincia_sancor, $token);
        $sancorLocalidad=compare_strings($cpName,$localidades_sancor)["Value"];

        $provincia_zurich=obtener_provincia_zurich(sanitize_text_field($_POST['provincia']), $token);
        $localidades_zurich=obtener_localidad_zurich(sanitize_text_field($cp), $provincia_zurich, $token);  
        $zurichLocalidad=compare_strings($cpName,$localidades_zurich)["Value"];

        $provincia_experta=obtener_provincia_experta(sanitize_text_field($_POST['provincia']), $token);
        $localidades_experta=obtener_localidad_experta(sanitize_text_field($cp), $provincia_experta, $token);  
        $expertaLocalidad=compare_strings($cpName,$localidades_experta)["Value"];

        echo '<pre>Respuesta API sancor: ';
        print_r($cpName);
        print_r($localidades_experta);
        print_r($expertaLocalidad);
        echo '</pre>';

        // Fin metodo Sancor

        $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';

        $bodyReq = [
            "ParametrosGenerales" => [
                "ProductorVendedor" => "208",
                "Año" => sanitize_text_field($_POST['anio'] ?? ''),
                "CeroKm" => false,
                "CodVehiculoExterno" => sanitize_text_field($_POST['modelo'] ?? ''),
                "Provincia" => sanitize_text_field($_POST['provincia'] ?? ''),
                "Localidad" => $intId ?? '',
                "MedioDePago" => "T",
                "TipoFacturacion" => "M",
                "TipoIva" => "CF",
                "TipoPersona" => "P",
                "FechaNacimiento" => sanitize_text_field($_POST['fecha_nac'] ?? ''),
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
                    "FechaEmisionValor" => "2025-05-14 00:00:00",
                    "Provincia" => $provincia_sancor,
                    "Localidad" => $sancorLocalidad,
                    "Menor25Años" => "2",
                    "DescuentoEspecial" => "0",
                    "TipoFacturacionCustom" => ""
                ],
                "Zurich" => [
                    "Beneficio" => "1",
                    "ClausulaAjuste" => "0",
                    "Descuento" => "10",
                    "Comision" => "10",
                    "DescuentoComision" => "10",
                    "PlanDePago" => "91",
                    "Rastreador" => "0",
                    "TipoIva" => "1",
                    "EstadoCivil" => "1",
                    "Provincia" => $provincia_zurich,
                    "IdPlan" => "350",
                    "Localidad" => $zurichLocalidad,
                    "Asistencia" => "31",
                    "TipoFacturacionCustom" => "M"
                ],
                "SanCristobal" => [
                    "TipoFacturacionCustom" => "",
                    "TipoDocumento" => sanitize_text_field($_POST['tipo_doc']), 
                    "NroDocumento" => sanitize_text_field($_POST['nro_doc']), 
                    "FechaInicioVigencia" => "2025-05-18 00:00:00", 
                    "CantidadCuotas" => "12", 
                    "ClausulaAjuste" => "10", 
                    "AlternativaComercial" => "5", 
                    "SnGPS" => false,
                    "GrupoAfinidad" => "pc:50502"
                ],
                 "Experta" => [
                    "Localidad" => $expertaLocalidad,
                    "Comision" => "EX0",
                    "FechaInicioVigencia" => "2025-04-28 00:00:00",
                    "TipoFacturacionCustom" => "",
                    "PlanPago" => "1"
                ]
            ]
        ];

        // $url_with_params = add_query_arg(['data' => json_encode($params)], $url_cotizar);

        
        if (($bodyReq)) {
            echo '<pre>La URL con parametros enviada fue: ';
            print_r( $bodyReq);
            echo '</pre>';
        }
        
        $args = [
            'body'=> json_encode($bodyReq),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 100,
        ];

        $response = wp_remote_post($url_cotizar,$args);


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
