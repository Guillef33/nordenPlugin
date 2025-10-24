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

        // Parsear Marca (formato: ID|Nombre)
        $marca_id = 'No disponible';
        $marca_nombre = 'No disponible';
        if (isset($_POST['marcas']) && !empty($_POST['marcas'])) {
            $marca_parts = explode('|', $_POST['marcas']);
            $marca_id = $marca_parts[0];
            $marca_nombre = $marca_parts[1] ?? $marca_id; // Usar ID si el nombre no está
        }

        // Parsear Modelo (formato: ID|Nombre)
        $modelo_id = 'No disponible';
        $modelo_nombre = 'No disponible';
        if (isset($_POST['modelo']) && !empty($_POST['modelo'])) {
            $modelo_parts = explode('|', $_POST['modelo']);
            $modelo_id = $modelo_parts[0];
            $modelo_nombre = $modelo_parts[1] ?? $modelo_id; // Usar ID si el nombre no está
        }

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

        // Parsear Provincia (formato: ID|Nombre)
        $provincia_id = 'No disponible';
        $provincia_nombre = 'No disponible';
        $provincia_parts = explode('|', $_POST['provincia']);
        $provincia_id = sanitize_text_field($provincia_parts[0]);
        $provincia_nombre = isset($provincia_parts[1]) ? sanitize_text_field($provincia_parts[1]) : $provincia_id;

        error_log("Provincia parseada - ID: $provincia_id, Nombre: $provincia_nombre");

        // Validaciones para Sancor
        $provincia_sancor = null;
        $sancorLocalidad = null;
        try {
            error_log("Obteniendo provincia Sancor...");
            $provincia_sancor = obtener_provincia_sancor($provincia_id, $token);
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
        $zurichPlanId = null;
        try {
            error_log("Obteniendo provincia Zurich...");
            $provincia_zurich = obtener_provincia_zurich($provincia_id, $token);
            error_log("Provincia Zurich obtenida: " . ($provincia_zurich ?? 'NULL'));

            error_log("Obteniendo PlanId Zurich...");
            $zurichPlanId = obtener_planId(PRODUCTOR_VENDEDOR, $token);
            error_log("PlanId Zurich obtenido: " . ($zurichPlanId ?? 'NULL'));

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
            $provincia_experta = obtener_provincia_experta($provincia_id, $token);
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
            //'estado_civil' => 'Estado civil',
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

        // Para autos usados, usar el año proporcionado; para 0km usar año actual
        $anio_final = ($condicion == "usado") ? $anio : date('Y');
        error_log("Año final a usar en la cotización: $anio_final");

        $fechaNacimiento = $_POST['fecha_nac'];
        $fechaNacimientoDate = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $fechaNacimientoDate->diff($hoy)->y;
        $menor25anos = $edad < 25 ? 1 : 2;
        $sexo = sanitize_text_field($_POST['sexo']);

        error_log("Edad calculada: $edad, Menor 25 años: $menor25anos");

        $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';



        // Construir body request
        error_log("Construyendo body request...");
        $bodyReq = [
            "ParametrosGenerales" => [
                "ProductorVendedor" => PRODUCTOR_VENDEDOR,
                "Año" => $anio_final, // DIFERENCIA CLAVE ENTRE USADO Y 0KM
                "CeroKm" => ($condicion == "0km"),
                "CodVehiculoExterno" => $modelo_id,
                "Provincia" => $provincia_id,
                "Localidad" => $intId,
                "MedioDePago" => "T",
                "TipoFacturacion" => "M",
                "TipoIva" => "CF",
                "TipoPersona" => "P",
                "FechaNacimiento" => $fecha_nac,
                "Sexo" => $sexo,
                "EstadoCivil" => "01", //sanitize_text_field($_POST['estado_civil']),
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
                    "DescuentoEspecial" => "15",
                    "TipoFacturacionCustom" => "M",
                    "Deducible" => "0",
                    "DescuentoPromocional" => true,
                ],
                "Zurich" => [
                    "Beneficio" => "0",
                    "ClausulaAjuste" => "0",
                    "Descuento" => "15",
                    "Comision" => "15",
                    "DescuentoComision" => "0",
                    "PlanDePago" => "91",
                    "Rastreador" => "0",
                    "TipoIva" => "1",
                    "EstadoCivil" => "1",
                    "Provincia" => $provincia_zurich,
                    "IdPlan" => $zurichPlanId,
                    "Localidad" => $zurichLocalidad,
                    "Asistencia" => "31",
                    "TipoFacturacionCustom" => "M"
                ],
                "SanCristobal" => [
                    "TipoFacturacionCustom" => "M",
                    "TipoDocumento" => sanitize_text_field($_POST['tipo_doc']),
                    "NroDocumento" => $nro_doc,
                    "FechaInicioVigencia" => $fechaActual,
                    "CantidadCuotas" => "12",
                    "ClausulaAjuste" => "5",
                    "AlternativaComercial" => "-20",
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

        error_log('--------------REQUEST-----------------------');
        error_log(print_r($bodyReq, true));
        error_log('-------------------------------------------');

        if (!empty($zurichLocalidad)) {
            $bodyReq["ParametrosEspecificos"]["Zurich"]["Localidad"] = (string) $zurichLocalidad;
        }

        // Log del body request (sin datos sensibles)
        /*
        error_log("Body request construido:");
        error_log("- Año: " . $bodyReq["ParametrosGenerales"]["Año"]);
        error_log("- CeroKm: " . ($bodyReq["ParametrosGenerales"]["CeroKm"] ? 'true' : 'false'));
        error_log("- Modelo: " . $bodyReq["ParametrosGenerales"]["CodVehiculoExterno"]);
        error_log("- Provincia Sancor: " . ($provincia_sancor ?? 'NULL'));
        error_log("- Localidad Sancor: " . ($sancorLocalidad ?? 'NULL'));
        error_log("- Provincia Zurich: " . ($provincia_zurich ?? 'NULL'));
        error_log("- Localidad Zurich: " . ($zurichLocalidad ?? 'NULL'));
        error_log("- Localidad Experta: " . ($expertaLocalidad ?? 'NULL'));
        */

        $args = [
            'body' => json_encode($bodyReq),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                //'Accept'          => '*/*',
                //'Accept-Encoding' => 'gzip, deflate, br',
                'Connection'      => 'keep-alive',
            ],
            'timeout' => 20,
        ];

        error_log("Enviando request a API...");
        $response = wp_remote_post($url_cotizar, $args);

        // Validar respuesta HTTP
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Error WP: $error_message");
            return '<p>Error: No se pudo conectar con el servicio de cotización.</p>';
        }

        /*
        error_log('--------------RESPONSE-----------------------');
        error_log(print_r($response, true));
        error_log('-------------------------------------------');
        */


        $http_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);

        error_log("Respuesta HTTP code: $http_code");
        error_log("Respuesta body length: " . strlen($body_raw));

        // if ($http_code !== 200) {
        //     error_log("Error HTTP $http_code - Body: " . substr($body_raw, 0, 500));
        //     return '<p>Error: El servicio respondió con código ' . $http_code . '. Detalles: ' . esc_html($body_raw) . '</p>';
        // }

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

        //error_log("Cotizaciones encontradas: " . count($body['Data']['Cotizaciones']));

        /*
        error_log('--------------COTIZACIONES-----------------------');
        error_log(print_r($body['Data']['Cotizaciones'], true));
        error_log('-------------------------------------------');
        */



        // Lista de planes permitidos por aseguradora
        $planes_permitidos = [
            /* 'Sancor' => ['PREMIUM MAX', 'TODO RIESGO 2%', 'TODO RIESGO 4%'],
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
            */
            'Sancor' => [
                '12', //'PREMIUM MAX -TERCEROS COMPLETO PREMIUM', --- ver si es correcto
                '28', //'AUTO TODO RIESGO 2%', 
                '31' //'AUTO TODO RIESGO 4%'
            ],
            'Zurich' => [
                'CG', //TR CON FRANQUICIA – TALLER ZURICH (DZ)
                'D2', //'TODO RIESGO CON FRANQUICIA - PLAN D2 2%',
                'DV' //'TODO RIESGO CON FRANQUICIA - PLAN DV 4%'
            ],
            'San Cristobal' => [
                'CA7_CM', //'CM - Terceros Completos Premium - Destrucción total por accidente, total y parcial por incendio y robo o hurto',
                'CA7_D106', //'D106 - Todo riesgo con franq. del 3,5%',
                'CA7_D102'  //'D102 -Todo riesgo con franq. del 5,0%',
            ],
            'Experta' => [
                '642', //'TERCEROS COMPLETO XL + GRANIZO FULL',
                '964', //'TODO RIESGO FRANQ. VARIABLE XL - 2%',
                '967' //'TODO RIESGO FRANQ. VARIABLE XL - 5%'
            ]
        ];

        $nombres = [
            //Sancor
            '12' => 'Terceros Completos Premium',
            '28' => 'Todo Riesgo Franquicia 2%',
            '31' => 'Todo Riesgo Franquicia 4%',
            //Zurich
            'CG' => 'Terceros Premium con Granizo',
            'D2' => 'Todo Riesgo Franquicia 2%',
            'DV' => 'Todo Riesgo Franquicia 4%',
            //San Cristobal
            'CA7_CM' => 'Terceros Completos Premium',
            'CA7_D106' => 'Todo Riesgo Franquicia 3,5%',
            'CA7_D102' => 'Todo Riesgo Franquicia 5%',
            //Experta
            '642' => 'Terceros Completos Premium',
            '964' => 'Todo Riesgo Franquicia 2%',
            '967' => 'Todo Riesgo Franquicia 5%'

            /*
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
            */

        ];

        $return = '<div class="aseguradoras-container">';
        /*
        echo '<pre>';
        print_r($bodyReq);
        echo '</pre>';

        echo '<pre>';
        print_r($body["Data"]['Cotizaciones']);
        echo '</pre>';

        
*/


        $api_experta_encontradas = 0;
        $api_sancor_encontradas = 0;
        $api_zurich_encontradas = 0;
        $api_sancristobal_encontradas = 0;

        $email_cotizaciones_detail = '';

        foreach ($body["Data"]['Cotizaciones'] as $aseguradora) {
            // Validar estructura de aseguradora
            if (!isset($aseguradora['Aseguradora'])) {
                error_log("Aseguradora sin nombre encontrada");
                continue;
            }





            $nombre_aseguradora = $aseguradora["Aseguradora"];
            error_log("----------------------------");
            error_log("Procesando aseguradora: $nombre_aseguradora");

            if (!isset($planes_permitidos[$nombre_aseguradora])) {
                error_log("Aseguradora no permitida: $nombre_aseguradora");
                continue; // Saltar aseguradoras no permitidas
            }

            $logos = [
                'Sancor' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/logo_sancor.png',
                'Zurich' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/logo_zurich.png',
                'San Cristobal' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/logo_sancristobal.png',
                'Experta' => plugin_dir_url(dirname(__FILE__)) . 'assets/logos/logo_experta.png'
            ];

            $logo_url = isset($logos[$nombre_aseguradora]) ? $logos[$nombre_aseguradora] : '';

            if (!empty($aseguradora['Coberturas']) && is_array($aseguradora['Coberturas'])) {
                error_log("Coberturas encontradas para $nombre_aseguradora: " . count($aseguradora['Coberturas']));

                $return .= '<div class="aseguradora">';

                // Mostrar logo si existe
                if (!empty($logo_url)) {
                    $return .= '<div class="aseguradora-logo-wrapper"> <img src="' . esc_url($logo_url) . '" alt="' . esc_attr($nombre_aseguradora) . ' logo" class="aseguradora-logo"></div>';
                }

                $return .= '<ul class="coberturas-list">';


                $coberturas_mostradas = 0;

                $email_cotizaciones_detail .= '<h3>' . esc_attr($nombre_aseguradora) . '</h3>';
                $email_cotizaciones_detail .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;"><tbody>';
                $email_cotizaciones_detail .= '<thead><tr><th style="text-align: left;">COTIZACIÓN NÚMERO</th><th style="text-align: left;">PLAN</th><th style="text-align: left;">PRECIO</th></tr></thead>';

                foreach ($aseguradora['Coberturas'] as $index => $coti) {





                    if ($aseguradora['IdAseguradora'] == 43) $api_experta_encontradas++;
                    if ($aseguradora['IdAseguradora'] == 168) $api_sancor_encontradas++;
                    if ($aseguradora['IdAseguradora'] == 195) $api_zurich_encontradas++;
                    if ($aseguradora['IdAseguradora'] == 166) $api_sancristobal_encontradas++;



                    // Validar estructura de cobertura
                    if (!isset($coti['DescCobertura']) || !isset($coti['Premio'])) {
                        error_log("Cobertura con estructura incompleta en $nombre_aseguradora");
                        continue;
                    }

                    $permitido = false;

                    foreach ($planes_permitidos[$nombre_aseguradora] as $plan) {
                        //if (stripos($coti['DescCobertura'], $plan) !== false) {
                        if ($coti['CodCoberturaCia'] === $plan) {
                            $permitido = true;
                            error_log("Plan permitido encontrado: " . $coti['CodCoberturaCia'] . ' - ' . $coti['DescCobertura']);
                            break;
                        }
                    }



                    if ($permitido) {
                        $coberturas_mostradas++;
                        //$id = 'cobertura_' . $index . '_' . md5($coti['DescCobertura']);

                        $return .= '<li class="cobertura-item">';
                        $return .= '<div class="cobertura-content" data-id="' . esc_attr($coti['Id']) . '">';

                        // Buscar una coincidencia en las claves del array $nombres
                        /*
                        $nombre_mostrado = false;
                        
                        foreach ($nombres as $clave => $valor) {
                            //if (stripos($coti['Id'], $clave) !== false) {
                            if ($coti['CodCoberturaCia'] === $clave) {
                                // Si encuentra coincidencia, mostrar el valor mapeado
                                $return .= '<p class="nombre-mapeado">' . esc_html($valor) . '</p>';
                                $nombre_mostrado = true;
                                break; // Si querés que solo se muestre la primera coincidencia
                            }
                        }

                        if (!$nombre_mostrado) {
                            error_log("No se encontró mapeo para: "  . $coti['CodCoberturaCia'] . ' - ' . $coti['DescCobertura']);
                        }
                        */
                        $return .= '<p class="nombre-mapeado">' . $nombres[$coti['CodCoberturaCia']] . '</p>';


                        $return .= '<h5>$ ' . number_format((float) $coti['Premio'], 0, ',', '.') . '</h5>';

                        $return .= '<button type="button" class="btn-contacto-whatsapp" data-cotizacion="' . $coti['NroCotizacionCia'] . '" data-cobertura="' . $coti["Id"] . '" data-plan="' . $nombres[$coti['CodCoberturaCia']] . '" data-aseguradora="' . $nombre_aseguradora . '" ><img class="whatsapp-icon" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/whatsapp-icon.png" width="19px" height="19px" alt="icono-whatsapp" /> Contratar ahora</button>
            </a>';
                        $return .= '</div>';
                        $return .= '</li>';


                        $email_cotizaciones_detail .= '<tr><td>' . $coti['Id'] . '</td><td>' . $nombres[$coti['CodCoberturaCia']] . '</td><td>$ ' . number_format((float) $coti['Premio'], 0, ',', '.') . '</td></tr>';
                    } else {
                        error_log("Plan no permitido: " . $coti['DescCobertura']);
                    }
                }

                $email_cotizaciones_detail .= '</tbody></table>';

                $return .= '</ul>';
                $return .= '</div>';



                error_log("Coberturas mostradas para $nombre_aseguradora: $coberturas_mostradas");
            } else {
                error_log("No se encontraron coberturas válidas para $nombre_aseguradora");
            }
        }
        $return .= '</div>';

        $return .= '<script>
        jQuery(function($){
            $(document).on("click", ".btn-contacto-whatsapp", function(e){
                e.preventDefault();

                var $btn = $(this);

                $btn.prop("disabled", true);
               

                $.post("' . admin_url('admin-ajax.php') . '", {
                        action: "contacto_whatsapp",
                        nonce: "' . wp_create_nonce('contacto_whatsapp_nonce') . '",
                        cotizacion: $btn.data("cotizacion"),
                        cobertura: $btn.data("cobertura"),
                        tipodoc : "' . tipoDoc2($_POST['tipo_doc']) . '",
                        fechanac: "' . $fecha_nac . '",
                        sexo: "' . $sexo . '",
                        documento: ' . esc_html($nro_doc) . ',

                }, function(resp){
            
                    console.log(resp);   

                    if(resp.data=="error"){

                        alert("Hubo un error al procesar la solicitud. Por favor, intente nuevamente.");
                        $btn.prop("disabled", false);

                    }else{

                        $btn.prop("disabled", false);

                        let url = "https://wa.me/5491161691404?text="+encodeURIComponent("¡Hola! Te escribo desde la web y me interesa saber más sobre el Presupuesto número "+ resp.data + " Plan " + $btn.data("plan") + " de la Aseguradora " + $btn.data("aseguradora"));

                        window.open(url, "_blank");

                    }

                }, "json").fail(function(){
                    
                    $btn.prop("disabled", true);
                    console.log("Fallo en la conexión AJAX.");

                });
            });
        });

        </script>';

        error_log("------------------------------------------------");
        error_log("Coberturas mostradas para Experta: " . $api_experta_encontradas);
        error_log("Coberturas mostradas para Sancor: " . $api_sancor_encontradas);
        error_log("Coberturas mostradas para Zurich: " . $api_zurich_encontradas);
        error_log("Coberturas mostradas para SanCristobal: " . $api_sancristobal_encontradas);
        error_log("------------------------------------------------");

        enviar_correo_cotizacion($body, $_POST, $nro_doc, $fecha_nac, $marca_nombre, $modelo_nombre, $provincia_nombre, $email_cotizaciones_detail);

        error_log("=== COTIZADOR COMPLETADO EXITOSAMENTE ===");

        return $return;
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

function tipoDoc($tipodoc)
{
    $tipo_doc = [
        "Ext_CUIT80" => "C.U.I.T.",
        "Ext_CUIL86" => "CLAVE UNICA DE IDENTIFICACION LABORAL",
        "Ext_DNI96"  => "DOCUMENTO NACIONAL IDENTIDAD",
        "Ext_LC90"   => "L.C.",
        "Ext_LE89"   => "L.E.",
        "Ext_PAS94"  => "PASAPORTE",
    ];
    return esc_html($tipo_doc[$tipodoc]);
}

function tipoDoc2($tipodoc)
{
    $tipo_doc = [
        "Ext_CUIT80" => "CU",
        "Ext_CUIL86" => "CL",
        "Ext_DNI96"  => "DN",
        "Ext_LC90"   => "LC",
        "Ext_LE89"   => "LE",
        "Ext_PAS94"  => "PP",
    ];
    return esc_html($tipo_doc[$tipodoc]);
}


function enviar_correo_cotizacion($api_response_body, $form_data, $nro_doc, $fecha_nac, $marca_nombre, $modelo_nombre, $provincia_nombre, $email_cotizaciones_detail)
{
    $to = 'pgomez@quickseguro.com';
    $subject = 'Nueva Cotización de Auto Recibida';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $id_cotizacion = 'No disponible';
    if (isset($api_response_body['Data']['IdCotizacion'])) {
        $id_cotizacion = esc_html($api_response_body['Data']['IdCotizacion']);
    }


    $tipodoc = tipoDoc($form_data['tipo_doc']);

    // Construir el cuerpo del email, con el ID de cotización destacado arriba
    $email_body = '<h2>Datos del Cliente</h2>';
    $email_body .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;"><tbody>';
    $email_body .= '<tr><td>Condición</td><td>' . esc_html($form_data['condicion']) . '</td></tr>';
    $email_body .= '<tr><td>Año</td><td>' . esc_html($form_data['anio']) . '</td></tr>';
    $email_body .= '<tr><td>Marca</td><td>' . esc_html($marca_nombre) . '</td></tr>';
    $email_body .= '<tr><td>Modelo</td><td>' . esc_html($modelo_nombre) . '</td></tr>';
    $email_body .= '<tr><td>Usa GNC</td><td>' . esc_html($form_data['gnc']) . '</td></tr>';
    $email_body .= '<tr><td>Provincia</td><td>' . esc_html($provincia_nombre) . '</td></tr>';
    $email_body .= '<tr><td>Código Postal</td><td>' . esc_html($form_data['codigo_postal']) . '</td></tr>';
    $email_body .= '<tr><td>Tipo de Documento</td><td>' . $tipodoc . '</td></tr>';
    $email_body .= '<tr><td>Número de Documento</td><td>' . esc_html($nro_doc) . '</td></tr>';
    $email_body .= '<tr><td>Fecha de Nacimiento</td><td>' . esc_html(date("d/m/Y", strtotime($fecha_nac))) . '</td></tr>';
    $email_body .= '</tbody></table>';
    $email_body .= '<h2>Cotizaciones Obtenidas</h2>';
    $email_body .= $email_cotizaciones_detail;


    if (wp_mail($to, $subject, $email_body, $headers)) {
        error_log("Correo de cotización enviado exitosamente a $to");
        $estado_envio = 'EMAIL ENVIADO';
    } else {
        error_log("Error al enviar el correo de cotización a $to. Guardando en log de respaldo.");
        $estado_envio = 'ERROR AL ENVIAR';
    }

    //Guardar log de respaldo
    $log_file_path = plugin_dir_path(__FILE__) . '../logs/envios-cotizador.log';
    $log_entry = "=================================================================\n";
    $log_entry .= "FECHA: " . date('d-m-Y H:i:s') . "\n";
    $log_entry .= "ESTADO DEL ENVIO: " . $estado_envio . "\n";
    $log_entry .= "Destinatario: " . $to . "\n";
    $log_entry .= "Asunto: " . $subject . "\n";
    $log_entry .= "Cuerpo del Mensaje:\n" . $email_body . "\n";
    $log_entry .= "=================================================================\n\n";
    file_put_contents($log_file_path, $log_entry, FILE_APPEND);
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

function generar_presupuesto()
{

    /*if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'contacto_whatsapp_nonce')) {
        wp_send_json_error(array('error' => 'Nonce inválido.'), 400);
    }*/

    check_ajax_referer('contacto_whatsapp_nonce', 'nonce');

    // Sanitizar inputs
    $token = obtener_token_norden();
    $cotizacion = isset($_POST['cotizacion']) ? sanitize_text_field(wp_unslash($_POST['cotizacion'])) : '';
    $cobertura = isset($_POST['cobertura']) ? sanitize_text_field(wp_unslash($_POST['cobertura'])) : '';
    $tipodoc = isset($_POST['tipodoc']) ? sanitize_text_field(wp_unslash($_POST['tipodoc'])) : '';
    $documento = isset($_POST['documento']) ? sanitize_text_field(wp_unslash($_POST['documento'])) : '';
    $fechanac = isset($_POST['fechanac']) ? sanitize_text_field(wp_unslash($_POST['fechanac'])) : '';
    $sexo = isset($_POST['sexo']) ? sanitize_text_field(wp_unslash($_POST['sexo'])) : '';




    $url = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/generarpresupuesto';

    $bodyReq = [
        "ParametrosGenerales" => [
            "NroCotizacion" => (int)$cotizacion,
            "Coberturas" => [$cobertura],
            "Cliente" => [
                "CodTipoDocumento" =>  $tipodoc,
                "NroFiscal" => (int)$documento,
                "ApellidoRazonSocial" =>  "Contacto Web", //este dato no lo tenemos
                "Nombre" => "Contacto Web", //este dato no lo tenemos
                "CodSexo" => $sexo, //"F"
                "FecNacimiento" => $fechanac . " 00:00:00",
                "CodTipoTelefono" => "CEL", //este dato no lo tenemos
                "Prefijo" => "11", //este dato no lo tenemos
                "NroTelefono" => "22514970", //este dato no lo tenemos
                "Email" => "contactoweb@norden.com.ar", //este dato no lo tenemos
                "CodTipoEstadoCivil" => "01" //este dato no lo tenemos
            ]
        ]
    ];


    /******* */
    //TEST- QUITAR- Devolver peticion
    //wp_send_json_success($bodyReq);
    //return;
    /******* */


    $args = [
        //'body' => json_encode($bodyReq),
        'body' => json_encode($bodyReq, JSON_UNESCAPED_UNICODE),
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Cache-Control' => 'no-cache',
            'Connection'    => 'keep-alive',
        ],
        'timeout' => 20
    ];


    // error_log(print_r(json_encode($bodyReq), true));



    //$response = wp_remote_get($url, $args);
    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('Error WP: ' . $response->get_error_message());
    } else {
        error_log('HTTP CODE: ' . wp_remote_retrieve_response_code($response));
        error_log('BODY: ' . wp_remote_retrieve_body($response));
    }

    // Validar respuesta HTTP
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("Error WP: $error_message");
        error_log('<p>Error: No se pudo conectar con el servicio de presupuesto.</p>');
        wp_send_json_success('error');
        return;
    }


    //Debug
    $http_code = wp_remote_retrieve_response_code($response);
    $body_raw = wp_remote_retrieve_body($response);
    error_log("PRESUPUESTO Respuesta HTTP code: $http_code");
    error_log("PRESUPUESTO Respuesta body length: " . strlen($body_raw));

    /******* */
    //TEST- QUITAR- Devolver peticion
    //error_log(print_r($body_raw, true));
    //return;
    /******* */


    if (is_wp_error($response)) {
        wp_send_json_success('error');
        return;
    }

    $body = json_decode($body_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decodificando JSON: " . json_last_error_msg());
        error_log("Body raw: " . substr($body_raw, 0, 500));
        //return '<p>Error: Presupuesto. Respuesta del servidor no válida.</p>';
        wp_send_json_success('error');
        return;
    }

    // Validar estructura de la respuesta
    if (!$body || !isset($body['Data']) || !isset($body['Data']["ParametrosGenerales"]['Presupuesto'])) {
        error_log("Error: Estructura de respuesta de Presuuesto no válida");
        error_log("Body structure: " . print_r(array_keys($body ?? []), true));
        error_log("Body structure: " . print_r($body, true));
        //return '<p>Error: Respuesta del servicio de Presupuesto no válida.</p>';
        wp_send_json_success('error');
        return;
    }

    //$body = json_decode(wp_remote_retrieve_body($response), true);


    /******* */
    //TEST- QUITAR- Devolver peticion
    //wp_send_json_success($body);
    error_log(print_r($body, true));
    //return;
    //return $body;
    /******* */

    if ($body["Data"] && isset($body["Data"]["ParametrosGenerales"]["Presupuesto"])) {
        wp_send_json_success($body["Data"]["ParametrosGenerales"]["Presupuesto"]);
        return;
    } else {
        wp_send_json_success('error');
        return;
    }
}
add_action('wp_ajax_contacto_whatsapp', 'generar_presupuesto');
add_action('wp_ajax_nopriv_contacto_whatsapp', 'generar_presupuesto');
