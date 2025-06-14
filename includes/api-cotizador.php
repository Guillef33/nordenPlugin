<?php

if (!defined('ABSPATH')) exit;

// Clase para manejar mediciones de tiempo
class TimeMeasurer
{
    private static $times = [];
    private static $start_times = [];

    public static function start($key)
    {
        self::$start_times[$key] = microtime(true);
    }

    public static function end($key)
    {
        if (isset(self::$start_times[$key])) {
            $execution_time = microtime(true) - self::$start_times[$key];
            self::$times[$key] = $execution_time;

            // Log del tiempo (opcional, para debug)
            error_log("Tiempo de ejecución [$key]: " . number_format($execution_time, 4) . " segundos");

            return $execution_time;
        }
        return false;
    }

    public static function getTimes()
    {
        return self::$times;
    }

    public static function getTotalTime()
    {
        return array_sum(self::$times);
    }

    public static function reset()
    {
        self::$times = [];
        self::$start_times = [];
    }
}

function validar_datos_iniciales()
{
    TimeMeasurer::start('validacion_inicial');

    // Validar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Método de petición no válido.</p>';
    }

    // Validar que exista codigo_postal
    if (!isset($_POST['codigo_postal']) || empty($_POST['codigo_postal'])) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Código postal no proporcionado.</p>';
    }

    // Validar formato del código postal
    $arr = explode(" - ", $_POST['codigo_postal']);
    if (count($arr) < 3) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Formato de código postal incorrecto. Debe ser: ID - CP - Nombre</p>';
    }

    $intId = trim($arr[0]);
    $cp = trim($arr[1]);
    $cpName = trim($arr[2]);

    // Validar que intId sea numérico
    if (!is_numeric($intId)) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: ID de localidad no válido.</p>';
    }

    // Validar que exista provincia
    if (!isset($_POST['provincia']) || empty($_POST['provincia'])) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Provincia no proporcionada.</p>';
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
            TimeMeasurer::end('validacion_inicial');
            return "<p>Error: $descripcion es requerido.</p>";
        }
    }

    // Validar formato de fecha de nacimiento
    $fecha_nac = sanitize_text_field($_POST['fecha_nac']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nac)) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Formato de fecha de nacimiento incorrecto (debe ser YYYY-MM-DD).</p>';
    }

    // Validar año del vehículo
    $anio = sanitize_text_field($_POST['anio']);
    if (!is_numeric($anio) || $anio < 1900 || $anio > (date('Y') + 1)) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Año del vehículo no válido.</p>';
    }

    // Validar número de documento
    $nro_doc = sanitize_text_field($_POST['nro_doc']);
    if (!is_numeric($nro_doc) || strlen($nro_doc) < 7 || strlen($nro_doc) > 8) {
        TimeMeasurer::end('validacion_inicial');
        return '<p>Error: Número de documento no válido.</p>';
    }

    TimeMeasurer::end('validacion_inicial');
    return null; // Sin errores
}

function obtener_datos_sancor($provincia_sanitized, $cp, $cpName, $token)
{
    TimeMeasurer::start('sancor_datos');

    $provincia_sancor = null;
    $sancorLocalidad = null;
    try {
        $provincia_sancor = obtener_provincia_sancor($provincia_sanitized, $token);
        if ($provincia_sancor) {
            $localidades_sancor = obtener_localidad_sancor(sanitize_text_field($cp), $provincia_sancor, $token);
            if ($localidades_sancor && is_array($localidades_sancor)) {
                $result_sancor = compare_strings($cpName, $localidades_sancor);
                $sancorLocalidad = isset($result_sancor["Value"]) ? $result_sancor["Value"] : null;
            }
        }
    } catch (Exception $e) {
        error_log("Error obteniendo datos de Sancor: " . $e->getMessage());
        $sancorLocalidad = null;
    }

    TimeMeasurer::end('sancor_datos');

    return [
        'provincia' => $provincia_sancor,
        'localidad' => $sancorLocalidad
    ];
}

function obtener_datos_zurich($provincia_sanitized, $cp, $cpName, $token)
{
    TimeMeasurer::start('zurich_datos');

    $provincia_zurich = null;
    $zurichLocalidad = null;
    try {
        $provincia_zurich = obtener_provincia_zurich($provincia_sanitized, $token);
        if ($provincia_zurich) {
            $localidades_zurich = obtener_localidad_zurich(sanitize_text_field($cp), $provincia_zurich, $token);
            if ($localidades_zurich && is_array($localidades_zurich)) {
                $result_zurich = compare_strings($cpName, $localidades_zurich);
                $zurichLocalidad = isset($result_zurich["Value"]) ? $result_zurich["Value"] : null;
            }
        }
    } catch (Exception $e) {
        error_log("Error obteniendo datos de Zurich: " . $e->getMessage());
        $zurichLocalidad = null;
    }

    TimeMeasurer::end('zurich_datos');

    return [
        'provincia' => $provincia_zurich,
        'localidad' => $zurichLocalidad
    ];
}

function obtener_datos_experta($provincia_sanitized, $cp, $cpName, $token)
{
    TimeMeasurer::start('experta_datos');

    $provincia_experta = null;
    $expertaLocalidad = null;
    try {
        $provincia_experta = obtener_provincia_experta($provincia_sanitized, $token);
        if ($provincia_experta) {
            $localidades_experta = obtener_localidad_experta(sanitize_text_field($cp), $provincia_experta, $token);
            if ($localidades_experta && is_array($localidades_experta)) {
                $result_experta = compare_strings($cpName, $localidades_experta);
                $expertaLocalidad = isset($result_experta["Value"]) ? $result_experta["Value"] : null;
            }
        }
    } catch (Exception $e) {
        error_log("Error obteniendo datos de Experta: " . $e->getMessage());
        $expertaLocalidad = null;
    }

    TimeMeasurer::end('experta_datos');

    return [
        'provincia' => $provincia_experta,
        'localidad' => $expertaLocalidad
    ];
}

function resultado_cotizador_auto()
{
    try {
        // Resetear mediciones previas
        TimeMeasurer::reset();
        TimeMeasurer::start('total_proceso');

        // Validaciones iniciales
        $error_validacion = validar_datos_iniciales();
        if ($error_validacion) {
            return $error_validacion;
        }

        // Validar y obtener token
        TimeMeasurer::start('obtener_token');
        $token = obtener_token_norden();
        TimeMeasurer::end('obtener_token');

        if (empty($token)) {
            return '<p>Error: No se pudo obtener el token de autorización.</p>';
        }

        // Procesar código postal
        $arr = explode(" - ", $_POST['codigo_postal']);
        $intId = trim($arr[0]);
        $cp = trim($arr[1]);
        $cpName = trim($arr[2]);

        $provincia_sanitized = sanitize_text_field($_POST['provincia']);

        // Obtener datos de aseguradoras (en paralelo conceptual)
        $datos_sancor = obtener_datos_sancor($provincia_sanitized, $cp, $cpName, $token);
        $datos_zurich = obtener_datos_zurich($provincia_sanitized, $cp, $cpName, $token);
        $datos_experta = obtener_datos_experta($provincia_sanitized, $cp, $cpName, $token);

        // Validar fecha actual
        TimeMeasurer::start('preparar_datos');
        try {
            $fecha = new DateTime();
            $fecha->modify('+1 day');
            $fechaActual = $fecha->format('Y-m-d') . ' 00:00:00';
        } catch (Exception $e) {
            return '<p>Error: No se pudo generar la fecha actual.</p>';
        }

        // Calcular edad
        $fechaNacimiento = $_POST['fecha_nac'];
        $fechaNacimientoDate = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $fechaNacimientoDate->diff($hoy)->y;
        $menor25anos = $edad < 25 ? 1 : 2;

        // Preparar datos para el request
        $anio = sanitize_text_field($_POST['anio']);
        $fecha_nac = sanitize_text_field($_POST['fecha_nac']);
        $nro_doc = sanitize_text_field($_POST['nro_doc']);

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
                    "Provincia" => $datos_sancor['provincia'],
                    "Localidad" => $datos_sancor['localidad'],
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
                    "Provincia" => $datos_zurich['provincia'],
                    "IdPlan" => "350",
                    "Localidad" => $datos_zurich['localidad'],
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
                    "Localidad" => $datos_experta['localidad'],
                    "Comision" => "EX0",
                    "FechaInicioVigencia" => $fechaActual,
                    "TipoFacturacionCustom" => "M",
                    "PlanPago" => "1"
                ]
            ]
        ];
        TimeMeasurer::end('preparar_datos');

        // Realizar petición HTTP
        TimeMeasurer::start('peticion_cotizacion');
        TimeMeasurer::start('args');

        $args = [
            'body' => json_encode($bodyReq),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Connection' => 'close', // Cierra conexión después de la respuesta
                'Cache-Control' => 'no-cache'
            ],
            'timeout' => 30,
            'redirection' => 3,     // Máximo 3 redirects
            'httpversion' => '1.1', // Usar HTTP/1.1 específicamente
            'blocking' => true,     // Petición síncrona
            'compress' => true,     // Comprimir respuesta si es posible
            'decompress' => true,   // Descomprimir respuesta automáticamente
            'sslverify' => true,    // Verificar SSL (cambiar a false solo si hay problemas)
            'stream' => false,      // No usar streaming para esta petición
            'cookies' => array()    // No enviar cookies
        ];
        TimeMeasurer::end('args');

        TimeMeasurer::start('remote post');
        error_log("Cotización POST: " . strlen($args['body']) . " chars - " . (strlen($args['body']) > 1024 ? "GRANDE" : "OK"));

        $response = wp_remote_post($url_cotizar, $args);
        TimeMeasurer::end('remote post');
        TimeMeasurer::end('peticion_cotizacion');

        // Validar respuesta HTTP
        if (is_wp_error($response)) {
            return '<p>Error: No se pudo conectar con el servicio de cotización.</p>';
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            return '<p>Error: El servicio de cotización respondió con código ' . $http_code . '</p>';
        }

        TimeMeasurer::start('procesar_respuesta');
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Validar estructura de la respuesta
        if (!$body || !isset($body['Data']) || !isset($body['Data']['Cotizaciones'])) {
            return '<p>Error: Respuesta del servicio de cotización no válida.</p>';
        }

        if (!is_array($body['Data']['Cotizaciones']) || empty($body['Data']['Cotizaciones'])) {
            return '<p>No se encontraron cotizaciones disponibles para los datos proporcionados.</p>';
        }

        ob_start();

        // Lista de planes permitidos por aseguradora
        $planes_permitidos = [
            'Sancor' =>
            ['PREMIUM MAX', 'TODO RIESGO 2%', 'TODO RIESGO 4%'],
            'Zurich' =>
            [
                'CG PREMIUM CON GRANIZO',
                'TODO RIESGO CON FRANQUICIA – PLAN D2 2%',
                'TODO RIESGO CON FRANQUICIA – PLAN DV 4%',
            ],
            'San Cristobal' => [
                'CM',
                'TODO RIESGO 2%',
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

        TimeMeasurer::end('procesar_respuesta');
        TimeMeasurer::end('total_proceso');

        // Log resumen de tiempos al final
        $tiempos = TimeMeasurer::getTimes();
        $tiempo_total = TimeMeasurer::getTotalTime();

        error_log("=== RESUMEN DE TIEMPOS DE EJECUCIÓN ===");
        foreach ($tiempos as $funcion => $tiempo) {
            error_log(sprintf("%-20s: %s segundos", $funcion, number_format($tiempo, 4)));
        }
        error_log("TIEMPO TOTAL: " . number_format($tiempo_total, 4) . " segundos");
        error_log("=======================================");

        return ob_get_clean();
    } catch (Exception $e) {
        error_log("Error en resultado_cotizador_auto: " . $e->getMessage());
        return '<p>Error interno: No se pudo procesar la cotización.</p>';
    }
}

// Función auxiliar para obtener reporte de tiempos (opcional)
function get_execution_times_report()
{
    $tiempos = TimeMeasurer::getTimes();
    if (empty($tiempos)) {
        return "No hay mediciones disponibles.";
    }

    $reporte = "Tiempos de ejecución:\n";
    foreach ($tiempos as $funcion => $tiempo) {
        $reporte .= sprintf("- %s: %s segundos\n", $funcion, number_format($tiempo, 4));
    }
    $reporte .= sprintf("Total: %s segundos", number_format(TimeMeasurer::getTotalTime(), 4));

    return $reporte;
}

function compare_strings($fraseObjetivo, $resultados)
{
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
