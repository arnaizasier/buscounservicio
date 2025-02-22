<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('procesar_formulario_gasto_beneficio')) {
    function procesar_formulario_gasto_beneficio() {
        global $wpdb;
        
        error_log('Iniciando procesamiento del formulario - ' . date('Y-m-d H:i:s'));
        
        // Verificar el nonce para seguridad
        if (!isset($_POST['gasto_beneficio_nonce']) || 
            !wp_verify_nonce($_POST['gasto_beneficio_nonce'], 'guardar_gasto_beneficio')) {
            error_log('Error de nonce - POST data: ' . print_r($_POST, true));
            wp_redirect(wp_get_referer() . '?error=nonce');
            exit;
        }

        error_log('Nonce verificado correctamente');

        // Sanitizar y validar los datos
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : '';
        $cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']) : 0;
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : '';
        $descripcion = isset($_POST['descripcion']) ? sanitize_textarea_field($_POST['descripcion']) : '';
        $fecha = isset($_POST['fecha']) ? sanitize_text_field($_POST['fecha']) : date('Y-m-d');
        $iva_porcentaje = isset($_POST['iva_porcentaje']) ? floatval($_POST['iva_porcentaje']) : 21.00; // Nuevo campo IVA
        $user_id = get_current_user_id();

        error_log('Datos recibidos: ' . print_r([
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'fecha' => $fecha,
            'iva_porcentaje' => $iva_porcentaje,
            'user_id' => $user_id
        ], true));

        // Validaciones básicas
        if (!in_array($tipo, ['gasto', 'beneficio'])) {
            error_log('Tipo inválido: ' . $tipo);
            wp_redirect(wp_get_referer() . '?error=tipo');
            exit;
        }

        if ($cantidad <= 0) {
            error_log('Cantidad inválida: ' . $cantidad);
            wp_redirect(wp_get_referer() . '?error=cantidad');
            exit;
        }

        // Validar IVA
        if ($iva_porcentaje < 0 || $iva_porcentaje > 100) {
            error_log('IVA inválido: ' . $iva_porcentaje);
            wp_redirect(wp_get_referer() . '?error=iva');
            exit;
        }

        // Corregido el nombre de la tabla
        $tabla = $wpdb->prefix . 'gastos_beneficios';
        
        error_log('Intentando insertar en la tabla: ' . $tabla);

        // Insertar en la base de datos
        $datos = array(
            'user_id' => $user_id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'fecha' => $fecha,
            'iva_porcentaje' => $iva_porcentaje // Nuevo campo IVA
        );

        $formato = array(
            '%d', // user_id
            '%s', // tipo
            '%f', // cantidad
            '%s', // categoria
            '%s', // descripcion
            '%s', // fecha
            '%f'  // iva_porcentaje
        );

        error_log('Query SQL que se ejecutará: ' . $wpdb->prepare(
            "INSERT INTO $tabla 
            (user_id, tipo, cantidad, categoria, descripcion, fecha, iva_porcentaje) 
            VALUES (%d, %s, %f, %s, %s, %s, %f)",
            $user_id, $tipo, $cantidad, $categoria, $descripcion, $fecha, $iva_porcentaje
        ));

        $resultado = $wpdb->insert($tabla, $datos, $formato);

        if ($resultado === false) {
            error_log('Error al insertar en la base de datos: ' . $wpdb->last_error);
            error_log('Último query ejecutado: ' . $wpdb->last_query);
            wp_redirect(wp_get_referer() . '?error=db');
            exit;
        }

        $insert_id = $wpdb->insert_id;
        error_log('Registro insertado correctamente. ID: ' . $insert_id);

        // Verificar que el registro existe
        $verificacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $insert_id
        ));

        if ($verificacion) {
            error_log('Verificación exitosa - Registro encontrado: ' . print_r($verificacion, true));
        } else {
            error_log('Error: No se pudo verificar el registro insertado');
        }

        // Redireccionar con mensaje de éxito
        wp_redirect(wp_get_referer() . '?success=1');
        exit;
    }
}