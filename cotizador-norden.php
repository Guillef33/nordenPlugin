<?php
/**
 * Plugin Name:       Formulario de Cotizacion Norden API 
 * Plugin URI:        https://magnetitte.com/
 * Description:       Plugin para la generacion de formularios de cotizacion con Norden API
 * Version:           0.0.2
 * Requires PHP:      7.2
 * Author:            Guillermo Flores
 * Author URI:        https://magnetitte.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) or die( 'No molestar!' );

// Requerir archivos de funciones
require_once plugin_dir_path(__FILE__) . 'includes/api-auth.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-cotizador.php';
require_once plugin_dir_path(__FILE__) . 'includes/formulario-cotizacion.php';

// Encolar assets
function cotizador_norden_enqueue_assets() {
    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_style(
        'cotizador-norden-style',
        $plugin_url . 'assets/styles.css',
        [],
        '1.0'
    );

    wp_enqueue_script(
        'cotizador-norden-script',
        $plugin_url . 'assets/app.js',
        ['jquery'], // Dependencias
        '1.0',
        true // Cargar en el footer
    );
}
add_action('wp_enqueue_scripts', 'cotizador_norden_enqueue_assets');

// Shortcodes
add_shortcode('resultado_cotizador_auto', 'resultado_cotizador_auto');
add_shortcode('formulario_cotizador_auto', 'formulario_cotizacion_auto');
?>
