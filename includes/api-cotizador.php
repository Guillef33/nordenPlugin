<?php

if (!defined('ABSPATH')) exit;

function resultado_cotizador_auto() {
    // Iniciar timer general
    $tiempo_inicio = microtime(true);
    $debug_log = [];
    
    // Función helper para logging
    function log_tiempo($mensaje, &$debug_log, $tiempo_inicio) {
        $tiempo_actual = microtime(true);
        $tiempo_transcurrido = round(($tiempo_actual - $tiempo_inicio) * 1000, 2);
        $debug_log[] = "[{$tiempo_transcurrido}ms] $mensaje";
        error_log("COTIZADOR DEBUG: [{$tiempo_transcurrido}ms] $mensaje");
    }
    
    log_tiempo("Inicio de función", $debug_log, $tiempo_inicio);
    
    // Validar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '<p>Error: Método de petición no válido.</p>';
    }

    try {
        // Validar y obtener token
        log_tiempo("Iniciando obtención de token", $debug_log, $tiempo_inicio);
        $token = obtener_token_norden();
        log_tiempo("Token obtenido", $debug_log, $tiempo_inicio);
        
        if (empty($token)) {
            return '<p>Error: No se pudo obtener el token de autorización.</p>';
        }
        
        // Validar que exista codigo_postal
        if (!isset($_POST['codigo_postal']) || empty($_POST['codigo_postal'])) {
            return '<p>Error: Código postal no proporcionado.</p>';
        }

        // Validar formato del código postal
        $arr = explode(" - ", $_POST['codigo_postal']);
        if (count($arr) < 3) {
            return '<p>Error: Formato de código postal incorrecto. Debe ser: ID - CP - Nombre</p>';
        }

        $intId = trim($arr[0]);
        $cp = trim($arr[1]);
        $cpName = trim($arr[2]);

        // Validar que intId sea numérico
        if (!is_numeric($intId)) {
            return '<p>Error: ID de localidad no válido.</p>';
        }

        // Validar que exista provincia
        if (!isset($_POST['provincia']) || empty($_POST['provincia'])) {
            return '<p>Error: Provincia no proporcionada.</p>';
        }

        $provincia_sanitized = sanitize_text_field($_POST['provincia']);
        log_tiempo("Validaciones básicas completadas", $debug_log, $tiempo_inicio);

        // Validaciones para Sancor
        $provincia_sancor = null;
        $sancorLocalidad = null;
        try {
            log_tiempo("Iniciando consulta Sancor - Provincia", $debug_log, $tiempo_inicio);
            $provincia_sancor = obtener_provincia_sancor($provincia_sanitized, $token);
            log_tiempo("Provincia Sancor obtenida", $debug_log, $tiempo_inicio);
            
            if ($provincia_sancor) {
                log_tiempo("Iniciando consulta Sancor - Localidades", $debug_log, $tiempo_inicio);
                $localidades_sancor = obtener_localidad_sancor(sanitize_text_field($cp), $provincia_sancor, $token);
                log_tiempo("Localidades Sancor obtenidas", $debug_log, $tiempo_inicio);
                
                if ($localidades_sancor && is_array($localidades_sancor)) {
                    $result_sancor = compare_strings($cpName, $localidades_sancor);
                    $sancorLocalidad = isset($result_sancor["Value"]) ? $result_sancor["Value"] : null;
                }
            }
            log_tiempo("Procesamiento Sancor completado", $debug_log, $tiempo_inicio);
        } catch (Exception $e) {
            error_log("Error obteniendo datos de Sancor: " . $e->getMessage());
            log_tiempo("Error en Sancor: " . $e->getMessage(), $debug_log, $tiempo_inicio);
            $sancorLocalidad = null;
        }

        // Validaciones para Zurich
        $provincia_zurich = null;
        $zurichLocalidad = null;
        try {
            log_tiempo("Iniciando consulta Zurich - Provincia", $debug_log, $tiempo_inicio);
            $provincia_zurich = obtener_provincia_zurich($provincia_sanitized, $token);
            log_tiempo("Provincia Zurich obtenida", $debug_log, $tiempo_inicio);
            
            if ($provincia_zurich) {
                log_tiempo("Iniciando consulta Zurich - Localidades", $debug_log, $tiempo_inicio);
                $localidades_zurich = obtener_localidad_zurich(sanitize_text_field($cp), $provincia_zurich, $token);
                log_tiempo("Localidades Zurich obtenidas", $debug_log, $tiempo_inicio);
                
                if ($localidades_zurich && is_array($localidades_zurich)) {
                    $result_zurich = compare_strings($cpName, $localidades_zurich);
                    $zurichLocalidad = isset($result_zurich["Value"]) ? $result_zurich["Value"] : null;
                }
            }
            log_tiempo("Procesamiento Zurich completado", $debug_log, $tiempo_inicio);
        } catch (Exception $e) {
            error_log("Error obteniendo datos de Zurich: " . $e->getMessage());
            log_tiempo("Error en Zurich: " . $e->getMessage(), $debug_log, $tiempo_inicio);
            $zurichLocalidad = null;
        }

        // Validaciones para Experta
        $provincia_experta = null;
        $expertaLocalidad = null;
        try {
            log_tiempo("Iniciando consulta Experta - Provincia", $debug_log, $tiempo_inicio);
            $provincia_experta = obtener_provincia_experta($provincia_sanitized, $token);
            log_tiempo("Provincia Experta obtenida", $debug_log, $tiempo_inicio);
            
            if ($provincia_experta) {
                log_tiempo("Iniciando consulta Experta - Localidades", $debug_log, $tiempo_inicio);
                $localidades_experta = obtener_localidad_experta(sanitize_text_field($cp), $provincia_experta, $token);
                log_tiempo("Localidades Experta obtenidas", $debug_log, $tiempo_inicio);
                
                if ($localidades_experta && is_array($localidades_experta)) {
                    $result_experta = compare_strings($cpName, $localidades_experta);
                    $expertaLocalidad = isset($result_experta["Value"]) ? $result_experta["Value"] : null;
                }
            }
            log_tiempo("Procesamiento Experta completado", $debug_log, $tiempo_inicio);
        } catch (Exception $e) {
            error_log("Error obteniendo datos de Experta: " . $e->getMessage());
            log_tiempo("Error en Experta: " . $e->getMessage(), $debug_log, $tiempo_inicio);
            $expertaLocalidad = null;
        }

        // Validar fecha actual
        try {
            $fecha = new DateTime();
            $fecha->modify('+1 day');
            $fechaActual = $fecha->format('Y-m-d') . ' 00:00:00';
        } catch (Exception $e) {
            return '<p>Error: No se pudo generar la fecha actual.</p>';
        }

        // Validaciones de campos requeridos
        $campos_requeridos = [
            'anio' => 'Año del vehículo',
            'condicion' => 'Condición del vehículo',
            'modelo' => 'Modelo del vehículo',
            'fecha_nac' => 'Fecha de nacimiento',
            'sexo' => 'Sexo',
            'estado_civil' => 'Estado civil',
            'gnc' => 'GNC',
            'tipo_doc' => 'Tipo de documento',
            'nro_doc' => 'Número de documento'
        ];

        foreach ($campos_requeridos as $campo => $descripcion) {
            if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
                return "<p>Error: $descripcion es requerido.</p>";
            }
        }

        // Validar formato de fecha de nacimiento
        $fecha_nac = sanitize_text_field($_POST['fecha_nac']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nac)) {
            return '<p>Error: Formato de fecha de nacimiento incorrecto (debe ser YYYY-MM-DD).</p>';
        }

        // Validar año del vehículo
        $anio = sanitize_text_field($_POST['anio']);
        if (!is_numeric($anio) || $anio < 1900 || $anio > (date('Y') + 1)) {
            return '<p>Error: Año del vehículo no válido.</p>';
        }

        // Validar número de documento
        $nro_doc = sanitize_text_field($_POST['nro_doc']);
        if (!is_numeric($nro_doc) || strlen($nro_doc) < 7 || strlen($nro_doc) > 8) {
            return '<p>Error: Número de documento no válido.</p>';
        }

        $fechaNacimiento = $_POST['fecha_nac'];
        $fechaNacimientoDate = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $fechaNacimientoDate->diff($hoy)->y;
        $menor25anos = $edad < 25 ? 1 : 2;

        log_tiempo("Preparando request de cotización", $debug_log, $tiempo_inicio);

        $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';

        $bodyReq = [
            "ParametrosGenerales" => [
                "ProductorVendedor" => "208",
                "Año" => $anio,
                "CeroKm" => (sanitize_text_field($_POST['condicion']) == "0km"),
                "CodVehiculoExterno" => sanitize_text_field($_POST['modelo']),
                "Provincia" => $provincia_sanitized,
                "Localidad" => $intId,
                "MedioDePago" => "T",
                "TipoFacturacion" => "M",
                "TipoIva" => "CF",
                "TipoPersona" => "P",
                "FechaNacimiento" => $fecha_nac,
                "Sexo" => sanitize_text_field($_POST['sexo']),
                "EstadoCivil" => sanitize_text_field($_POST['estado_civil']),
                "SnGNC" => (sanitize_text_field($_POST['gnc']) == "SI" ? 'S' : "N"),
                "ValuacionGNC" => ""
            ],
            "ParametrosEspecificos" => [
                "Sancor" => [
                    "ClausulaAjuste" => "0",
                    "NeumaticosAuxiliares" => "1",
                    "Garage" => "1",
                    "KilometrosAnuales" => "1",
                    "TipoIva" => "4",
                    "PlanDePago" => "1",
                    "FechaEmisionValor" => $fechaActual,
                    "Provincia" => $provincia_sancor,
                    "Localidad" => $sancorLocalidad,
                    "Menor25Años" => $menor25anos,
                    "DescuentoEspecial" => "0",
                    "TipoFacturacionCustom" => "M",
                    "Deducible" => "0",
                    "DescuentoPromocional" => "0",
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
                    "NroDocumento" => $nro_doc,
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
            'body' => json_encode($bodyReq),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 100,
        ];

        log_tiempo("Enviando request de cotización", $debug_log, $tiempo_inicio);
        $response = wp_remote_post($url_cotizar, $args);
        log_tiempo("Response de cotización recibido", $debug_log, $tiempo_inicio);

        // Validar respuesta HTTP
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            log_tiempo("Error en request: " . $error_msg, $debug_log, $tiempo_inicio);
            return '<p>Error: No se pudo conectar con el servicio de cotización.</p>';
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            log_tiempo("HTTP Error: Código " . $http_code, $debug_log, $tiempo_inicio);
            return '<p>Error: El servicio de cotización respondió con código ' . $http_code . '</p>';
        }

        log_tiempo("Procesando respuesta JSON", $debug_log, $tiempo_inicio);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Validar estructura de la respuesta
        if (!$body || !isset($body['Data']) || !isset($body['Data']['Cotizaciones'])) {
            log_tiempo("Error: Estructura de respuesta inválida", $debug_log, $tiempo_inicio);
            return '<p>Error: Respuesta del servicio de cotización no válida.</p>';
        }
        
        if (!is_array($body['Data']['Cotizaciones']) || empty($body['Data']['Cotizaciones'])) {
            log_tiempo("No se encontraron cotizaciones", $debug_log, $tiempo_inicio);
            return '<p>No se encontraron cotizaciones disponibles para los datos proporcionados.</p>';
        }
        
        log_tiempo("Iniciando generación de HTML", $debug_log, $tiempo_inicio);
        ob_start();

        // Lista de planes permitidos por aseguradora
        $planes_permitidos = [
            'Sancor' => ['PREMIUM MAX', 'TODO RIESGO 2%', 'TODO RIESGO 4%'],
            'Zurich' => [
                'CG PREMIUM CON GRANIZO',
                'TODO RIESGO CON FRANQUICIA – PLAN D2 2%',
                'TODO RIESGO CON FRANQUICIA – PLAN DV 4%',
            ],
            'San Cristobal' => ['CM', 'TODO RIESGO 2%', 
                'Todo riesgo con franq. del 5',
                'Todo riesgo con franq. del 2', 
            ],
            'Experta' => [
                'PREMIUM MAX',
                'TODO RIESGO 2%',
                'TODO RIESGO 5%'
            ]
        ];
        
        echo '<div class="aseguradoras-container">';

        foreach ($body["Data"]['Cotizaciones'] as $aseguradora) {
            // Validar estructura de aseguradora
            if (!isset($aseguradora['Aseguradora'])) {
                continue;
            }

            $nombre_aseguradora = $aseguradora["Aseguradora"];

            if (!isset($planes_permitidos[$nombre_aseguradora])) {
                continue; // Saltar aseguradoras no permitidas
            }

            $logos = [
                'Sancor' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/sancor.webp',
                'Zurich' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/zurich.webp',
                'San Cristobal' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/SanCristobal.webp',
                'Experta' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/experta.webp'
            ];

            $logo_url = isset($logos[$nombre_aseguradora]) ? $logos[$nombre_aseguradora] : '';
            
            if (!empty($aseguradora['Coberturas']) && is_array($aseguradora['Coberturas'])) {
                echo '<div class="aseguradora">';

                // Mostrar logo si existe
                if (!empty($logo_url)) {
                    echo '<div class="aseguradora-logo-wrapper"> <img src="' . esc_url($logo_url) . '" alt="' . esc_attr($nombre_aseguradora) . ' logo" class="aseguradora-logo"></div>';
                }

                echo '<ul class="coberturas-list">';

                foreach ($aseguradora['Coberturas'] as $index => $coti) {
                    // Validar estructura de cobertura
                    if (!isset($coti['DescCobertura']) || !isset($coti['Prima'])) {
                        continue;
                    }

                    $permitido = false;

                    foreach ($planes_permitidos[$nombre_aseguradora] as $plan) {
                        if (stripos($coti['DescCobertura'], $plan) !== false) {
                            $permitido = true;
                            break;
                        }
                    }

                    if ($permitido) {
                        $id = 'cobertura_' . $index . '_' . md5($coti['DescCobertura']);

                        echo '<li class="cobertura-item">';
                        echo '<div class="cobertura-content">';
                        echo '<p>' . esc_html($coti['DescCobertura']) . '</p>';
                        echo '<h5>$ ' . number_format((float) $coti['Prima'], 2, ',', '.') . '</h5>';

                        echo '<a href="#"> 
                            <span> 
                                <img class="whatsapp-icon" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/whatsapp-icon.png" width="19px" height="19px" alt="icono-whatsapp" /> 
                            </span>
                            Contratar ahora
                        </a>';
                        echo '</div>';
                        echo '</li>';
                    }
                }

                echo '</ul>';
                echo '</div>';
            }
        }
        echo '</div>';

        log_tiempo("HTML generado completamente", $debug_log, $tiempo_inicio);
        
        // Log del tiempo total
        $tiempo_total = microtime(true) - $tiempo_inicio;
        $debug_log[] = "TIEMPO TOTAL: " . round($tiempo_total * 1000, 2) . "ms";
        error_log("COTIZADOR DEBUG: TIEMPO TOTAL: " . round($tiempo_total * 1000, 2) . "ms");
        
        // Mostrar debug info en HTML
        echo '<div style="margin-top: 20px;">';
        echo '<h3>Debug Info - Tiempos de Ejecución:</h3>';
        echo '<pre style="background: #f4f4f4; padding: 10px; border-radius: 5px; font-size: 12px; max-height: 300px; overflow-y: auto;">';
        echo implode("\n", $debug_log);
        echo '</pre>';
        echo '</div>';

        return ob_get_clean();

    } catch (Exception $e) {
        log_tiempo("Exception: " . $e->getMessage(), $debug_log, $tiempo_inicio);
        error_log("Error en resultado_cotizador_auto: " . $e->getMessage());
        return '<p>Error interno: No se pudo procesar la cotización.</p>';
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