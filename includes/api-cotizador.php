<?php

if (!defined('ABSPATH')) exit;
function resultado_cotizador_auto() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // echo '<pre>';
        // print_r($_POST);
        // echo '</pre>';

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

        $fechaActual = (new DateTime())->format('Y-m-d') . ' 00:00:00';

        // Fin metodo Sancor

        $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';

        $bodyReq = [
            "ParametrosGenerales" => [
                "ProductorVendedor" => "208",
                "Año" => sanitize_text_field($_POST['anio'] ?? ''),
                "CeroKm" => sanitize_text_field($_POST['condicion'] == "0km" ? true : false),
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
                "SnGNC" => sanitize_text_field($_POST['gnc'] == "SI" ? 'S' : "N"),
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
                    "FechaEmisionValor" => $fechaActual,
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
                    "FechaInicioVigencia" => $fechaActual, 
                    "CantidadCuotas" => "12", 
                    "ClausulaAjuste" => "10", 
                    "AlternativaComercial" => "5", 
                    "SnGPS" => false,
                    "GrupoAfinidad" => "pc:50502"
                ],
                 "Experta" => [
                    "Localidad" => $expertaLocalidad,
                    "Comision" => "EX0",
                    "FechaInicioVigencia" => $fechaActual,
                    "TipoFacturacionCustom" => "M",
                    "PlanPago" => "1"
                ]
            ]
        ];
        
       
        $args = [
            'body'=> json_encode($bodyReq),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 100,
        ];

        $response = wp_remote_post($url_cotizar,$args);


        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['Data']['Cotizaciones']) && is_array($body['Data']['Cotizaciones'])) {
            ob_start();
            echo '<h3>Resultados de Cotización</h3>';

            // Lista de planes permitidos por aseguradora (normalizados)
            $planes_permitidos = [
                'Sancor' => ['premium max', 'todo riesgo 2%', 'todo riesgo 4%'],
                'Zurich' => ['cg premium con granizo', 'todo riesgo 2%', 'todo riesgo 4 %'],
                'SanCristobal' => ['cm', 'todo riesgo 2%', 'todo riesgo 5%'],
                'Experta' => ['premium max', 'todo riesgo 2%', 'todo riesgo 5%']
            ];

            function normalizar($texto) {
                $texto = strtolower(trim($texto));
                $texto = preg_replace('/\s+/', ' ', $texto); 
                $texto = preg_replace('/[^a-z0-9 %]/', '', $texto); 
                return $texto;
            }

            foreach ($body["Data"]['Cotizaciones'] as $aseguradora) {
                $nombre_aseguradora = $aseguradora['Aseguradora'] ?? '';

                // Si no tiene coberturas o viene con error, la saltamos
                if (empty($aseguradora['Coberturas']) || !is_array($aseguradora['Coberturas'])) {
                    continue;
                }

                // Filtrar coberturas válidas para esta aseguradora
                $coberturas_filtradas = [];
                foreach ($aseguradora['Coberturas'] as $coti) {
                    $nombre_plan_normalizado = normalizar($coti['DescCobertura'] ?? '');
                    $planes_validos = $planes_permitidos[$nombre_aseguradora] ?? [];

                    // Comparamos contra planes normalizados
                    $planes_validos_normalizados = array_map('normalizar', $planes_validos);
                    if (in_array($nombre_plan_normalizado, $planes_validos_normalizados)) {
                        $coberturas_filtradas[] = $coti;
                    }
                }

                // Si no hay coberturas permitidas, no mostramos nada
                if (empty($coberturas_filtradas)) {
                    continue;
                }

                echo '<div class="aseguradora">';
                echo '<h4>' . esc_html($nombre_aseguradora) . '</h4>';
                    echo '<ul class="coberturas-list">';

                    foreach ($coberturas_filtradas as $index => $coti) {
                        $id = 'cobertura_' . $index . '_' . md5($coti['DescCobertura']);
                        echo '<li class="cobertura-item">';
                        echo '<label for="' . $id . '">';
                        echo '<input type="checkbox" id="' . $id . '" name="coberturas[]" value="' . esc_attr($coti['DescCobertura']) . '">';
                        echo ' ' . esc_html($coti['DescCobertura']) . ' - $' . esc_html($coti['Prima']);
                        echo '</label>';
                        echo '</li>';
                    }

                    echo '</ul>';
                echo '</div>';
            }

            return ob_get_clean();
            }
            else {
                return ''; 
            }
        }
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
