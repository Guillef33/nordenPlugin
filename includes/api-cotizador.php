<?php

if (!defined('ABSPATH')) exit;
function resultado_cotizador_auto() {

    //  echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';
    
    // Validar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '<p>Error: Método de petición no válido.</p>';
    }

    try {
        // Validar y obtener token
        $token = obtener_token_norden();
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

$prov_codigos = get_multiple_provincias($provincia_sanitized, $token);
$localidades = get_multiple_localidades($cp, $cpName, $prov_codigos, $token);

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

$fechaNacimiento = $_POST['fecha_nac']; // por ejemplo: "2002-06-06"
$fechaNacimientoDate = new DateTime($fechaNacimiento);
$hoy = new DateTime();

$edad = $fechaNacimientoDate->diff($hoy)->y;

$menor25anos = $edad < 25 ? 1 : 2;

       $url_cotizar = 'https://quickbi4.norden.com.ar/api_externa/autos/cotizador/cotizar';

$generales = [
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
];

// Parametrización por aseguradora
$aseguradoras = [
    "Sancor" => [
        "ClausulaAjuste" => "0",
        "NeumaticosAuxiliares" => "1",
        "Garage" => "1",
        "KilometrosAnuales" => "1",
        "TipoIva" => "4",
        "PlanDePago" => "1",
        "FechaEmisionValor" => $fechaActual,
        "Provincia" => $prov_codigos["sancor"],
        "Localidad" => $localidades["sancor"],
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
        "Provincia" => $prov_codigos["zurich"],
        "IdPlan" => "350",
        "Localidad" => $localidades["zurich"],
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
        "Localidad" => $localidades["experta"],
        "Comision" => "EX0",
        "FechaInicioVigencia" => $fechaActual,
        "TipoFacturacionCustom" => "M",
        "PlanPago" => "1"
    ]
];

// Preparar multi-cURL
$multiHandle = curl_multi_init();
$curlHandles = [];
$responses = [];

foreach ($aseguradoras as $aseguradora => $parametros) {
    $body = [
        "ParametrosGenerales" => $generales,
        "ParametrosEspecificos" => [
            $aseguradora => $parametros
        ]
    ];

    $ch = curl_init($url_cotizar);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 100,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]
    ]);

    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[$aseguradora] = $ch;
}

// Ejecutar en paralelo
$running = null;
do {
    $status = curl_multi_exec($multiHandle, $running);
    if ($status != CURLM_OK) {
        break;
    }

    if (curl_multi_select($multiHandle) === -1) {
        usleep(100); // Espera mínima para evitar CPU alta
    }
} while ($running > 0);

// Recoger las respuestas con manejo de errores
foreach ($curlHandles as $aseguradora => $ch) {
    $result = curl_multi_getcontent($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($error || $httpCode !== 200) {
        $responses[$aseguradora] = [
            'error' => $error,
            'http_code' => $httpCode,
            'response' => $result
        ];
    } else {
        $data = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responses[$aseguradora] = [
                'json_error' => json_last_error_msg(),
                'response' => $result
            ];
        } else {
            $responses[$aseguradora] = $data;
        }
    }

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);


$allCotizaciones = [];

echo "<pre>";
print_r($responses);
echo "</pre>";

foreach ($responses as $response) {
    if (isset($response['Data']['Cotizaciones'])) {
        $allCotizaciones = array_merge($allCotizaciones, $response['Data']['Cotizaciones']);
    }
}

$body = [
    'Data' => [
        'Cotizaciones' => array_values($allCotizaciones)
    ]
];

$errores = [];


foreach ($curlHandles as $aseguradora => $ch) {
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_msg = curl_error($ch);

    if ($error_msg || $http_code !== 200) {
        $errores[] = "<p>Error en $aseguradora: " . ($error_msg ?: "Código HTTP $http_code") . "</p>";
        unset($responses[$aseguradora]); // quitamos esa cotización del resultado
    }
}

// Si todas fallaron
if (empty($responses)) {
    return '<p>Error: No se pudo conectar con ninguna aseguradora.</p>' . implode('', $errores);
}

// Si alguna falló pero otras funcionaron
if (!empty($errores)) {
    // Podés loguearlas o mostrarlas según tu necesidad
    // return implode('', $errores); // para mostrar errores parciales
}


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
            'Sancor' => ['PREMIUM MAX', 'TODO RIESGO 2%', 'TODO RIESGO 4%'],
            'Zurich' => [
                'CG PREMIUM CON GRANIZO',
                'TODO RIESGO CON FRANQUICIA – PLAN D2 2%',
                'TODO RIESGO CON FRANQUICIA – PLAN DV 4%',
                // 'TR CON FRANQUICIA – TALLER ZURICH (DZ)'
            ],
            'San Cristobal' => ['CM', 'TODO RIESGO 2%', 
            'Todo riesgo con franq. del 5',
            'Todo riesgo con franq. del 2', 
            // "D102", "D101"
        ],
            'Experta' => [
                'PREMIUM MAX',
                // 'TODO RIESGO FRANQ. VARIABLE XL - 1%',
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


            // // Omitir resultados si la compañía es Sancor y no tiene coberturas
            // if (
            //     $aseguradora['Aseguradora'] === 'Sancor' &&
            //     empty($aseguradora['Coberturas'])
            // ) {
            //     continue;
            // }

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
        } else {
            // echo '<p>No se encontraron coberturas para ' . esc_html($nombre_aseguradora) . '.</p>';
        }
    }
    echo '</div>';


        return ob_get_clean();

    } catch (Exception $e) {
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
