<?php

if (!defined('ABSPATH')) exit;

function resultado_cotizador_auto()
{
    // Habilitar logging detallado
    error_log("=== INICIO COTIZADOR AUTO ===");
    
    // Validar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Error: Método no POST");
        return '<p>Error: Método de petición no válido.</p>';
    }

    try {
        // Log de datos recibidos (sin datos sensibles)
        error_log("POST recibido - Condición: " . ($_POST['condicion'] ?? 'NO_SET'));
        error_log("POST recibido - Año: " . ($_POST['anio'] ?? 'NO_SET'));
        error_log("POST recibido - Modelo: " . ($_POST['modelo'] ?? 'NO_SET'));
        
        // Validar y obtener token
        error_log("Obteniendo token...");
        $token = obtener_token_norden();
        if (empty($token)) {
            error_log("Error: Token vacío");
            return '<p>Error: No se pudo obtener el token de autorización.</p>';
        }
        error_log("Token obtenido correctamente");

        // Validar que exista codigo_postal
        if (!isset($_POST['codigo_postal']) || empty($_POST['codigo_postal'])) {
            error_log("Error: Código postal no proporcionado");
            return '<p>Error: Código postal no proporcionado.</p>';
        }

        // Validar formato del código postal
        $arr = explode(" - ", $_POST['codigo_postal']);
        if (count($arr) < 3) {
            error_log("Error: Formato código postal incorrecto: " . $_POST['codigo_postal']);
            return '<p>Error: Formato de código postal incorrecto. Debe ser: ID - CP - Nombre</p>';
        }

        $intId = trim($arr[0]);
        $cp = trim($arr[1]);
        $cpName = trim($arr[2]);
        error_log("Código postal parseado - ID: $intId, CP: $cp, Nombre: $cpName");

        // Validar que intId sea numérico
        if (!is_numeric($intId)) {
            error_log("Error: ID localidad no numérico: $intId");
            return '<p>Error: ID de localidad no válido.</p>';
        }

        // Validar que exista provincia
        if (!isset($_POST['provincia']) || empty($_POST['provincia'])) {
            error_log("Error: Provincia no proporcionada");
            return '<p>Error: Provincia no proporcionada.</p>';
        }

        $provincia_sanitized = sanitize_text_field($_POST['provincia']);
        error_log("Provincia sanitizada: $provincia_sanitized");

        // Validaciones para Sancor
        $provincia_sancor = null;
        $sancorLocalidad = null;
        try {
            error_log("Obteniendo provincia Sancor...");
            $provincia_sancor = obtener_provincia_sancor($provincia_sanitized, $token);
            error_log("Provincia Sancor obtenida: " . ($provincia_sancor ?? 'NULL'));
            
            if ($provincia_sancor) {
                error_log("Obteniendo localidades Sancor...");
                $localidades_sancor = obtener_localidad_sancor(sanitize_text_field($cp), $provincia_sancor, $token);
                error_log("Localidades Sancor - Count: " . (is_array($localidades_sancor) ? count($localidades_sancor) : 'NO_ARRAY'));
                
                if ($localidades_sancor && is_array($localidades_sancor)) {
                    $result_sancor = compare_strings($cpName, $localidades_sancor);
                    $sancorLocalidad = isset($result_sancor["Value"]) ? $result_sancor["Value"] : null;
                    error_log("Sancor localidad final: " . ($sancorLocalidad ?? 'NULL'));
                }
            }
        } catch (Exception $e) {
            error_log("Error obteniendo datos de Sancor: " . $e->getMessage());
            error_log("Stack trace Sancor: " . $e->getTraceAsString());
            $sancorLocalidad = null;
        }

        // Validaciones para Zurich
        $provincia_zurich = null;
        $zurichLocalidad = null;
        try {
            error_log("Obteniendo provincia Zurich...");
            $provincia_zurich = obtener_provincia_zurich($provincia_sanitized, $token);
            error_log("Provincia Zurich obtenida: " . ($provincia_zurich ?? 'NULL'));
            
            if ($provincia_zurich) {
                error_log("Obteniendo localidades Zurich...");
                $localidades_zurich = obtener_localidad_zurich(sanitize_text_field($cp), $provincia_zurich, $token);
                error_log("Localidades Zurich - Count: " . (is_array($localidades_zurich) ? count($localidades_zurich) : 'NO_ARRAY'));
                
                if ($localidades_zurich && is_array($localidades_zurich)) {
                    $result_zurich = compare_strings($cpName, $localidades_zurich);
                    $zurichLocalidad = isset($result_zurich["Value"]) ? $result_zurich["Value"] : null;
                    error_log("Zurich localidad final: " . ($zurichLocalidad ?? 'NULL'));
                }
            }
        } catch (Exception $e) {
            error_log("Error obteniendo datos de Zurich: " . $e->getMessage());
            error_log("Stack trace Zurich: " . $e->getTraceAsString());
            $zurichLocalidad = null;
        }

        // Validaciones para Experta
        $provincia_experta = null;
        $expertaLocalidad = null;
        try {
            error_log("Obteniendo provincia Experta...");
            $provincia_experta = obtener_provincia_experta($provincia_sanitized, $token);
            error_log("Provincia Experta obtenida: " . ($provincia_experta ?? 'NULL'));
            
            if ($provincia_experta) {
                error_log("Obteniendo localidades Experta...");
                $localidades_experta = obtener_localidad_experta(sanitize_text_field($cp), $provincia_experta, $token);
                error_log("Localidades Experta - Count: " . (is_array($localidades_experta) ? count($localidades_experta) : 'NO_ARRAY'));
                
                if ($localidades_experta && is_array($localidades_experta)) {
                    $result_experta = compare_strings($cpName, $localidades_experta);
                    $expertaLocalidad = isset($result_experta["Value"]) ? $result_experta["Value"] : null;
                    error_log("Experta localidad final: " . ($expertaLocalidad ?? 'NULL'));
                }
            }
        } catch (Exception $e) {
            error_log("Error obteniendo datos de Experta: " . $e->getMessage());
            error_log("Stack trace Experta: " . $e->getTraceAsString());
            $expertaLocalidad = null;
        }

        // Validar fecha actual
        try {
            error_log("Generando fecha actual...");
            $fecha = new DateTime();
            $fecha->modify('+1 day');
            $fechaActual = $fecha->format('Y-m-d') . ' 00:00:00';
            error_log("Fecha actual generada: $fechaActual");
        } catch (Exception $e) {
            error_log("Error generando fecha: " . $e->getMessage());
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

        error_log("Validando campos requeridos...");
        foreach ($campos_requeridos as $campo => $descripcion) {
            if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
                error_log("Campo faltante: $campo");
                return "<p>Error: $descripcion es requerido.</p>";
            }
        }
        error_log("Todos los campos requeridos están presentes");

        // Validar formato de fecha de nacimiento
        $fecha_nac = sanitize_text_field($_POST['fecha_nac']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nac)) {
            error_log("Error: Formato fecha nacimiento incorrecto: $fecha_nac");
            return '<p>Error: Formato de fecha de nacimiento incorrecto (debe ser YYYY-MM-DD).</p>';
        }

        // Validar año del vehículo
        $anio = sanitize_text_field($_POST['anio']);
        if (!is_numeric($anio) || $anio < 1900 || $anio > (date('Y') + 1)) {
            error_log("Error: Año vehículo no válido: $anio");
            return '<p>Error: Año del vehículo no válido.</p>';
        }

        // Validar número de documento
        $nro_doc = sanitize_text_field($_POST['nro_doc']);
        if (!is_numeric($nro_doc) || strlen($nro_doc) < 7 || strlen($nro_doc) > 8) {
            error_log("Error: Número documento no válido: $nro_doc");
            return '<p>Error: Número de documento no válido.</p>';
        }

        // PUNTO CRÍTICO: Diferencia entre usado y 0km
        $condicion = sanitize_text_field($_POST['condicion']);
        error_log("Condición del vehículo: $condicion");
        
        // Para autos usados, usar el año proporcionado; para 0km usar 2025
        $anio_final = ($condicion == "usado") ? $anio : '2025';
        error_log("Año final a usar en la cotización: $anio_final");

        $fechaNacimiento = $_POST['fecha_nac'];
        $fechaNacimientoDate = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $fechaNacimientoDate->diff($hoy)->y;
        $menor25anos = $edad < 25 ? 1 : 2;
        
        error_log("Edad calculada: $edad, Menor 25 años: $menor25anos");

        $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';

        

        // Construir body request
        error_log("Construyendo body request...");
        $bodyReq = [
            "ParametrosGenerales" => [
                "ProductorVendedor" => "27923",
                "Año" => $anio_final, // DIFERENCIA CLAVE ENTRE USADO Y 0KM
                "CeroKm" => ($condicion == "0km"),
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
                    // "Localidad" => $zurichLocalidad ?? "1",
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

        if (!empty($zurichLocalidad)) {
            $bodyReq["ParametrosEspecificos"]["Zurich"]["Localidad"] = (string) $zurichLocalidad;
        }

        // Log del body request (sin datos sensibles)
        error_log("Body request construido:");
        error_log("- Año: " . $bodyReq["ParametrosGenerales"]["Año"]);
        error_log("- CeroKm: " . ($bodyReq["ParametrosGenerales"]["CeroKm"] ? 'true' : 'false'));
        error_log("- Modelo: " . $bodyReq["ParametrosGenerales"]["CodVehiculoExterno"]);
        error_log("- Provincia Sancor: " . ($provincia_sancor ?? 'NULL'));
        error_log("- Localidad Sancor: " . ($sancorLocalidad ?? 'NULL'));
        error_log("- Provincia Zurich: " . ($provincia_zurich ?? 'NULL'));
        error_log("- Localidad Zurich: " . ($zurichLocalidad ?? 'NULL'));
        error_log("- Localidad Experta: " . ($expertaLocalidad ?? 'NULL'));

        $args = [
            'body' => json_encode($bodyReq),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 100,
        ];

        error_log("Enviando request a API...");
        $response = wp_remote_post($url_cotizar, $args);

        // Validar respuesta HTTP
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Error WP: $error_message");
            return '<p>Error: No se pudo conectar con el servicio de cotización.</p>';
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        
        error_log("Respuesta HTTP code: $http_code");
        error_log("Respuesta body length: " . strlen($body_raw));
        
        if ($http_code !== 200) {
            error_log("Error HTTP $http_code - Body: " . substr($body_raw, 0, 500));
            return '<p>Error: El servicio respondió con código ' . $http_code . '. Detalles: ' . esc_html($body_raw) . '</p>';
        }

        $body = json_decode($body_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error decodificando JSON: " . json_last_error_msg());
            error_log("Body raw: " . substr($body_raw, 0, 500));
            return '<p>Error: Respuesta del servidor no válida.</p>';
        }

        // Validar estructura de la respuesta
        if (!$body || !isset($body['Data']) || !isset($body['Data']['Cotizaciones'])) {
            error_log("Error: Estructura de respuesta no válida");
            error_log("Body structure: " . print_r(array_keys($body ?? []), true));
            return '<p>Error: Respuesta del servicio de cotización no válida.</p>';
        }

        if (!is_array($body['Data']['Cotizaciones']) || empty($body['Data']['Cotizaciones'])) {
            error_log("No se encontraron cotizaciones en la respuesta");
            error_log("Cotizaciones data: " . print_r($body['Data']['Cotizaciones'], true));
            return '<p>No se encontraron cotizaciones disponibles para los datos proporcionados.</p>';
        }

        error_log("Cotizaciones encontradas: " . count($body['Data']['Cotizaciones']));

        ob_start();

        // Lista de planes permitidos por aseguradora
        $planes_permitidos = [
            'Sancor' => ['PREMIUM MAX', 'TODO RIESGO 2%', 'TODO RIESGO 4%'],
            'Zurich' => [
                'CG',
                'TODO RIESGO CON FRANQUICIA - PLAN D2 2%',
                'TODO RIESGO CON FRANQUICIA – PLAN DV 4%',
                // 'TR CON FRANQUICIA – TALLER ZURICH (DZ)'
            ],
            'San Cristobal' => [
                'CM',
                'TODO RIESGO 2%',
                'Todo riesgo con franq. del 5',
                'Todo riesgo con franq. del 2,0',
                // "D102", "D101"
            ],
            'Experta' => [
                'TERCEROS COMPLETO XL + GRANIZO',
                // 'PREMIUM MAX',
                // 'TODO RIESGO FRANQ. VARIABLE XL - 1%',
                'TODO RIESGO 2%',
                'TODO RIESGO 5%'
            ]
        ];

        $nombres = [
            'PREMIUM MAX' => 'Terceros Completos Premium',
            'TODO RIESGO 2%' => 'Todo Riesgo Franquicia 2%',
            'TODO RIESGO 4%' => 'Todo Riesgo Franquicia 4%',
            'CG' => 'Terceros Completos Premium',
            'TODO RIESGO CON FRANQUICIA - PLAN D2 2%' => 'Todo Riesgo Franquicia 2%',
            'TODO RIESGO CON FRANQUICIA – PLAN DV 4%' => 'Todo Riesgo Franquicia 5%',
            'CM' => 'Terceros Completos Premium',
            'TODO RIESGO 2%' => 'Todo Riesgo Franquicia 2%',
            'Todo riesgo con franq. del 5' => 'Todo Riesgo Franquicia 5%',
            'Todo riesgo con franq. del 2,0' => 'Todo Riesgo Franquicia 2%',
            'TERCEROS COMPLETO XL + GRANIZO' => 'Terceros Completos Premium',
            'TODO RIESGO 2%' => 'Todo Riesgo Franquicia 2%',
            'TODO RIESGO 5%' => 'Todo Riesgo Franquicia 5%'
        ];

        echo '<div class="aseguradoras-container">';

              echo '<pre>';
        print_r($bodyReq);
        echo '</pre>';

           echo '<pre>';
        print_r($body["Data"]['Cotizaciones']);
        echo '</pre>';

        foreach ($body["Data"]['Cotizaciones'] as $aseguradora) {
            // Validar estructura de aseguradora
            if (!isset($aseguradora['Aseguradora'])) {
                error_log("Aseguradora sin nombre encontrada");
                continue;
            }

            $nombre_aseguradora = $aseguradora["Aseguradora"];
            error_log("Procesando aseguradora: $nombre_aseguradora");

            if (!isset($planes_permitidos[$nombre_aseguradora])) {
                error_log("Aseguradora no permitida: $nombre_aseguradora");
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
                error_log("Coberturas encontradas para $nombre_aseguradora: " . count($aseguradora['Coberturas']));
                
                echo '<div class="aseguradora">';

                // Mostrar logo si existe
                if (!empty($logo_url)) {
                    echo '<div class="aseguradora-logo-wrapper"> <img src="' . esc_url($logo_url) . '" alt="' . esc_attr($nombre_aseguradora) . ' logo" class="aseguradora-logo"></div>';
                }

                echo '<ul class="coberturas-list">';

                $coberturas_mostradas = 0;

                foreach ($aseguradora['Coberturas'] as $index => $coti) {
                    // Validar estructura de cobertura
                    if (!isset($coti['DescCobertura']) || !isset($coti['Prima'])) {
                        error_log("Cobertura con estructura incompleta en $nombre_aseguradora");
                        continue;
                    }

                    $permitido = false;

                    foreach ($planes_permitidos[$nombre_aseguradora] as $plan) {
                        if (stripos($coti['DescCobertura'], $plan) !== false) {
                            $permitido = true;
                            error_log("Plan permitido encontrado: " . $coti['DescCobertura']);
                            break;
                        }
                    }

                    if ($permitido) {
                        $coberturas_mostradas++;
                        $id = 'cobertura_' . $index . '_' . md5($coti['DescCobertura']);

                        echo '<li class="cobertura-item">';
                        echo '<div class="cobertura-content">';
                        
                        // Buscar una coincidencia en las claves del array $nombres
                        $nombre_mostrado = false;
                        foreach ($nombres as $clave => $valor) {
                            if (stripos($coti['DescCobertura'], $clave) !== false) {
                                // Si encuentra coincidencia, mostrar el valor mapeado
                                echo '<p class="nombre-mapeado">' . esc_html($valor) . '</p>';
                                $nombre_mostrado = true;
                                break; // Si querés que solo se muestre la primera coincidencia
                            }
                        }
                        
                        if (!$nombre_mostrado) {
                            error_log("No se encontró mapeo para: " . $coti['DescCobertura']);
                        }
                        
                        echo '<h5>$ ' . number_format((float) $coti['Prima'], 2, ',', '.') . '</h5>';

                        echo '<a href="#"> 
                <span> 
                    <img class="whatsapp-icon" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/whatsapp-icon.png" width="19px" height="19px" alt="icono-whatsapp" /> 
                </span>
                Contratar ahora
            </a>';
                        echo '</div>';
                        echo '</li>';
                    } else {
                        error_log("Plan no permitido: " . $coti['DescCobertura']);
                    }
                }

                echo '</ul>';
                echo '</div>';
                
                error_log("Coberturas mostradas para $nombre_aseguradora: $coberturas_mostradas");
            } else {
                error_log("No se encontraron coberturas válidas para $nombre_aseguradora");
            }
        }
        echo '</div>';

        error_log("=== COTIZADOR COMPLETADO EXITOSAMENTE ===");

        return ob_get_clean();
        
    } catch (Exception $e) {
        error_log("EXCEPCIÓN GENERAL: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return '<p>Error interno del servidor. Por favor, inténtelo nuevamente.</p>';
    } catch (Error $e) {
        error_log("ERROR FATAL: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return '<p>Error crítico del servidor.</p>';
    }
}

function compare_strings($fraseObjetivo, $resultados)
{
    error_log("Comparando strings - Frase objetivo: $fraseObjetivo");
    error_log("Resultados count: " . (is_array($resultados) ? count($resultados) : 'NO_ARRAY'));
    
    $mejorSimilitud = -1;
    $mejorCoincidencia = null;

    foreach ($resultados as $oracion) {
        if (!isset($oracion["Text"])) {
            error_log("Resultado sin campo Text encontrado");
            continue;
        }
        
        similar_text($fraseObjetivo, $oracion["Text"], $porcentaje);
        error_log("Similitud con '" . $oracion["Text"] . "': $porcentaje%");
        
        if ($porcentaje > $mejorSimilitud) {
            $mejorSimilitud = $porcentaje;
            $mejorCoincidencia = $oracion;
        }
    }

    error_log("Mejor coincidencia: " . ($mejorCoincidencia ? $mejorCoincidencia["Text"] : 'NULL') . " (Similitud: $mejorSimilitud%)");
    return $mejorCoincidencia;
}