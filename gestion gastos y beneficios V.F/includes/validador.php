<?php
if (!defined('ABSPATH')) {
    exit;
}

function validar_datos_formulario() {
    // Verificar nonce
    if (!isset($_POST['gasto_beneficio_nonce']) || 
        !wp_verify_nonce($_POST['gasto_beneficio_nonce'], 'guardar_gasto_beneficio')) {
        error_log('Nonce ausente o inválido.');
        return false;
    }

    // Verificar que el usuario está logueado
    if (!is_user_logged_in()) {
        error_log('Usuario no autenticado intentando enviar el formulario.');
        return false;
    }

    // Validar campos requeridos
    if (empty($_POST['tipo']) || empty($_POST['cantidad'])) {
        error_log('Campos requeridos faltantes.');
        return false;
    }

    // Validar tipo
    if (!in_array($_POST['tipo'], ['gasto', 'beneficio'])) {
        error_log('Tipo inválido: ' . $_POST['tipo']);
        return false;
    }

    // Validar cantidad
    if (!is_numeric($_POST['cantidad']) || floatval($_POST['cantidad']) <= 0) {
        error_log('Cantidad inválida: ' . $_POST['cantidad']);
        return false;
    }

    return true;
}