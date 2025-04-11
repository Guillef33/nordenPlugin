<?php

if (!defined('ABSPATH')) exit;

function resultado_cotizador_auto() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = obtener_token_norden();

        if (!$token) {
            return '<p style="color:red;">Error de autenticación. Intente más tarde.</p>';
        }

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
                "Providencia" => [
                    "Clausula" => "3",
                    "PlanPago" => "624",
                    "DescuentoMedioDePago" => "5",
                    "DescuentoComercial" => "0",
                    "Comision" => "5",
                    "TipoFacturacionCustom" => ""
                ]
            ]
        ];

        $url_with_params = add_query_arg(['data' => json_encode($params)], $url_cotizar);

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 20,
        ];

        $response = wp_remote_get($url_with_params, $args);

        if (is_wp_error($response)) {
            error_log('Error WP: ' . $response->get_error_message());
            return '<p>Error al obtener cotizaciones.</p>';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['Data']['Cotizaciones'])) {
            ob_start();
            echo '<h3>Resultados de Cotización</h3>';
            foreach ($body['Data']['Cotizaciones'] as $coti) {
                echo '<p>Plan: ' . esc_html($coti['DescripcionPlan']) . ' - Prima: $' . esc_html($coti['Prima']) . '</p>';
            }
            return ob_get_clean();
        } else {
            return '<p>No se encontraron cotizaciones disponibles.</p>';
        }
    }

    return '<p>Formulario no enviado.</p>';
}
