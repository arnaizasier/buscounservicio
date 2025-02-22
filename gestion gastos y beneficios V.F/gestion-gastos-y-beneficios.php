<?php
/**
 * Plugin Name: Gestión de Gastos y Beneficios
 * Description: Plugin para registrar y gestionar gastos y beneficios en WordPress.
 * Version: 1.0
 * Author: Asier Arnaiz
 * Text Domain: gestion-gastos-beneficios
 */

// Evita el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Configuración de depuración
if (!defined('GASTOS_BENEFICIOS_DEBUG')) {
    define('GASTOS_BENEFICIOS_DEBUG', false);
}

function debug_log_gastos_beneficios($mensaje) {
    if (GASTOS_BENEFICIOS_DEBUG) {
        error_log('[DEBUG Gastos Beneficios] ' . $mensaje);
    }
}

// Función para cargar los archivos necesarios
function gestion_gastos_beneficios_init() {
    require_once plugin_dir_path(__FILE__) . 'includes/validador.php';
    require_once plugin_dir_path(__FILE__) . 'includes/procesar-formulario.php';
    require_once plugin_dir_path(__FILE__) . 'includes/mostrar-tabla-gastos.php';
    require_once plugin_dir_path(__FILE__) . 'includes/mostrar-balance-mensual.php';
}
add_action('plugins_loaded', 'gestion_gastos_beneficios_init');

// Función para cargar los estilos y scripts
function cargar_estilos_gastos_beneficios() {
    wp_enqueue_script('jquery');
    
    // CSS principal
    $css_url = plugin_dir_url(__FILE__) . 'assets/styles.css';
    $css_path = plugin_dir_path(__FILE__) . 'assets/styles.css';
    
    // CSS de la tabla
    $tabla_css_url = plugin_dir_url(__FILE__) . 'assets/tabla-gastos.css';
    $tabla_css_path = plugin_dir_path(__FILE__) . 'assets/tabla-gastos.css';
    
    // CSS del balance
    $balance_css_url = plugin_dir_url(__FILE__) . 'assets/balance-mensual.css';
    $balance_css_path = plugin_dir_path(__FILE__) . 'assets/balance-mensual.css';
    
    // JS de la tabla
    $tabla_js_url = plugin_dir_url(__FILE__) . 'assets/tabla-gastos.js';
    $tabla_js_path = plugin_dir_path(__FILE__) . 'assets/tabla-gastos.js';
    
    // JS del balance
    $balance_js_url = plugin_dir_url(__FILE__) . 'assets/balance-mensual.js';
    $balance_js_path = plugin_dir_path(__FILE__) . 'assets/balance-mensual.js';
    
    // Cargar CSS principal
    if (file_exists($css_path)) {
        wp_enqueue_style(
            'gastos-beneficios-styles',
            $css_url,
            array(),
            filemtime($css_path)
        );
    }
    
    // Cargar CSS de la tabla
    if (file_exists($tabla_css_path)) {
        wp_enqueue_style(
            'tabla-gastos-css',
            $tabla_css_url,
            array(),
            filemtime($tabla_css_path)
        );
    }
    
    // Cargar CSS del balance
    if (file_exists($balance_css_path)) {
        wp_enqueue_style(
            'balance-mensual-css',
            $balance_css_url,
            array(),
            filemtime($balance_css_path)
        );
    }
    
    // Cargar JS de la tabla
    if (file_exists($tabla_js_path)) {
        wp_enqueue_script(
            'tabla-gastos-js',
            $tabla_js_url,
            array('jquery'),
            filemtime($tabla_js_path),
            true
        );
        
        wp_localize_script('tabla-gastos-js', 'tablaGastosAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tabla_gastos_nonce')
        ));
    }
    
    // Cargar JS del balance
    if (file_exists($balance_js_path)) {
        wp_enqueue_script(
            'balance-mensual-js',
            $balance_js_url,
            array('jquery'),
            filemtime($balance_js_path),
            true
        );
        
        // Añadir variables para AJAX del balance usando el mismo nonce
        wp_localize_script('balance-mensual-js', 'tablaGastosAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tabla_gastos_nonce')
        ));
    }
}

// Asegurar que los estilos se carguen tanto en el frontend como en el admin
add_action('wp_enqueue_scripts', 'cargar_estilos_gastos_beneficios');
add_action('admin_enqueue_scripts', 'cargar_estilos_gastos_beneficios');

// Registrar los shortcodes
function registrar_shortcodes_gastos_beneficios() {
    add_shortcode('formulario_gastos_beneficios', 'mostrar_formulario_gastos_beneficios');
    add_shortcode('tabla_gastos_beneficios', 'mostrar_tabla_gastos_beneficios');
    add_shortcode('balance_financiero', 'mostrar_balance');
}
add_action('init', 'registrar_shortcodes_gastos_beneficios');

// Función para el formulario
function mostrar_formulario_gastos_beneficios() {
    if (!is_user_logged_in()) {
        return '<p>Debes iniciar sesión para acceder a este formulario.</p>';
    }

    ob_start();
    include plugin_dir_path(__FILE__) . 'includes/formulario.php';
    return ob_get_clean();
}

// Registrar las acciones para procesar el formulario
add_action('admin_post_procesar_gastos_beneficios', 'procesar_formulario_gasto_beneficio');
add_action('admin_post_nopriv_procesar_gastos_beneficios', 'procesar_formulario_gasto_beneficio');

// Función AJAX para manejar la eliminación de registros
function eliminar_registro_gasto_callback() {
    check_ajax_referer('tabla_gastos_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('No tienes permisos para realizar esta acción');
    }

    global $wpdb;
    $id = intval($_POST['id']);
    
    $resultado = $wpdb->delete(
        $wpdb->prefix . 'gastos_beneficios',
        array('id' => $id),
        array('%d')
    );

    if ($resultado !== false) {
        wp_send_json_success(array(
            'mensaje' => 'Registro eliminado correctamente'
        ));
    } else {
        wp_send_json_error('Error al eliminar el registro');
    }
}
add_action('wp_ajax_eliminar_registro_gasto', 'eliminar_registro_gasto_callback');

// Registro de acciones AJAX para el balance
add_action('wp_ajax_actualizar_balance', 'actualizar_balance_ajax');
add_action('wp_ajax_nopriv_actualizar_balance', 'actualizar_balance_ajax');

// Función para mostrar mensajes de depuración
if (GASTOS_BENEFICIOS_DEBUG) {
    add_action('wp_footer', function() {
        if (current_user_can('administrator')) {
            echo '<div style="display:none;">Shortcodes registrados: ' . 
                 implode(', ', array_keys($GLOBALS['shortcode_tags'])) . 
                 '</div>';
        }
    });
}