<?php
if (!defined('ABSPATH')) {
    exit;
}

function obtener_balance_periodo($periodo = 'mensual', $mes = null, $anio = null, $trimestre = null) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'gastos_beneficios';

    // Si no se especifica mes y año, usar el actual
    if (!$mes) $mes = date('m');
    if (!$anio) $anio = date('Y');
    if (!$trimestre) $trimestre = ceil($mes / 3);

    // Construir la consulta base
    $where_clause = "";
    $params = array();

    switch ($periodo) {
        case 'mensual':
            $where_clause = "MONTH(fecha) = %d AND YEAR(fecha) = %d";
            $params = array($mes, $anio);
            break;
        case 'trimestral':
            $inicio_trimestre = ($trimestre - 1) * 3 + 1;
            $fin_trimestre = $inicio_trimestre + 2;
            $where_clause = "MONTH(fecha) BETWEEN %d AND %d AND YEAR(fecha) = %d";
            $params = array($inicio_trimestre, $fin_trimestre, $anio);
            break;
        case 'anual':
            $where_clause = "YEAR(fecha) = %d";
            $params = array($anio);
            break;
    }

    // Consulta modificada para incluir cálculos con y sin IVA
    $query = $wpdb->prepare(
        "SELECT 
            tipo,
            COALESCE(SUM(cantidad), 0) as total_con_iva,
            COALESCE(SUM(cantidad / (1 + (iva_porcentaje/100))), 0) as total_sin_iva,
            COUNT(*) as num_registros
        FROM $tabla 
        WHERE $where_clause 
        GROUP BY tipo",
        ...$params
    );

    $resultados = $wpdb->get_results($query);

    $datos = array(
        'gastos' => 0,
        'beneficios' => 0,
        'num_gastos' => 0,
        'num_beneficios' => 0
    );

    foreach ($resultados as $resultado) {
        if ($resultado->tipo === 'gasto') {
            $datos['gastos'] = floatval($resultado->total_con_iva);
            $datos['gastos_sin_iva'] = floatval($resultado->total_sin_iva);
            $datos['num_gastos'] = intval($resultado->num_registros);
        } else {
            $datos['beneficios'] = floatval($resultado->total_con_iva);
            $datos['beneficios_sin_iva'] = floatval($resultado->total_sin_iva);
            $datos['num_beneficios'] = intval($resultado->num_registros);
        }
    }

    $datos['balance'] = $datos['beneficios'] - $datos['gastos'];
    $datos['balance_sin_iva'] = $datos['beneficios_sin_iva'] - $datos['gastos_sin_iva'];
    
    return $datos;
}

function mostrar_balance() {
    // [Código anterior para parámetros y validación...]

    $datos_balance = obtener_balance_periodo($periodo, $mes, $anio, $trimestre);
    
    ob_start();
    ?>
    <div class="balance-wrapper">
        <div class="balance-tabs">
            <button class="balance-tab-btn <?php echo $periodo === 'mensual' ? 'active' : ''; ?>" 
                    onclick="cambiarPeriodoBalance('mensual')" 
                    data-periodo="mensual">
                Mensual
            </button>
            <button class="balance-tab-btn <?php echo $periodo === 'trimestral' ? 'active' : ''; ?>" 
                    onclick="cambiarPeriodoBalance('trimestral')" 
                    data-periodo="trimestral">
                Trimestral
            </button>
            <button class="balance-tab-btn <?php echo $periodo === 'anual' ? 'active' : ''; ?>" 
                    onclick="cambiarPeriodoBalance('anual')" 
                    data-periodo="anual">
                Anual
            </button>
        </div>

        <div class="balance-content">
            <div class="balance-periodo-info">
                <h2>
                    <?php
                    switch ($periodo) {
                        case 'mensual':
                            echo "Balance de {$nombres_meses[$mes]} $anio";
                            break;
                        case 'trimestral':
                            echo "Balance del {$nombres_trimestres[$trimestre]} de $anio";
                            break;
                        case 'anual':
                            echo "Balance anual de $anio";
                            break;
                    }
                    ?>
                </h2>
            </div>

            <div class="balance-grid">
                <div class="balance-item gastos">
                    <span class="balance-label">
                        Gastos Totales
                        <small>(<?php echo $datos_balance['num_gastos']; ?> registros)</small>
                    </span>
                    <span class="balance-amount">-<?php echo number_format($datos_balance['gastos'], 2, ',', '.'); ?>€</span>
                </div>
                
                <div class="balance-item beneficios">
                    <span class="balance-label">
                        Beneficios Totales
                        <small>(<?php echo $datos_balance['num_beneficios']; ?> registros)</small>
                    </span>
                    <span class="balance-amount">+<?php echo number_format($datos_balance['beneficios'], 2, ',', '.'); ?>€</span>
                </div>
                
                <div class="balance-item balance <?php echo $datos_balance['balance'] >= 0 ? 'positivo' : 'negativo'; ?>">
                    <span class="balance-label">Balance con IVA</span>
                    <span class="balance-amount">
                        <?php echo ($datos_balance['balance'] >= 0 ? '+' : ''); ?>
                        <?php echo number_format($datos_balance['balance'], 2, ',', '.'); ?>€
                    </span>
                </div>

                <div class="balance-item balance <?php echo $datos_balance['balance_sin_iva'] >= 0 ? 'positivo' : 'negativo'; ?>">
                    <span class="balance-label">Balance sin IVA</span>
                    <span class="balance-amount">
                        <?php echo ($datos_balance['balance_sin_iva'] >= 0 ? '+' : ''); ?>
                        <?php echo number_format($datos_balance['balance_sin_iva'], 2, ',', '.'); ?>€
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Función para manejar la petición AJAX
function actualizar_balance_ajax() {
    check_ajax_referer('tabla_gastos_nonce', 'nonce');
    
    $periodo = isset($_POST['periodo']) ? sanitize_text_field($_POST['periodo']) : 'mensual';
    $mes = isset($_POST['mes']) ? intval($_POST['mes']) : date('m');
    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : date('Y');
    $trimestre = ceil($mes / 3);

    $datos_balance = obtener_balance_periodo($periodo, $mes, $anio, $trimestre);
    
    $nombres_meses = array(
        1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    );

    $nombres_trimestres = array(
        1 => 'Primer trimestre',
        2 => 'Segundo trimestre',
        3 => 'Tercer trimestre',
        4 => 'Cuarto trimestre'
    );

    ob_start();
    ?>
    <div class="balance-periodo-info">
        <h2>
            <?php
            switch ($periodo) {
                case 'mensual':
                    echo "Balance de {$nombres_meses[$mes]} $anio";
                    break;
                case 'trimestral':
                    echo "Balance del {$nombres_trimestres[$trimestre]} de $anio";
                    break;
                case 'anual':
                    echo "Balance anual de $anio";
                    break;
            }
            ?>
        </h2>
    </div>

    <div class="balance-grid">
        <div class="balance-item gastos">
            <span class="balance-label">
                Gastos Totales
                <small>(<?php echo $datos_balance['num_gastos']; ?> registros)</small>
            </span>
            <span class="balance-amount">-<?php echo number_format($datos_balance['gastos'], 2, ',', '.'); ?>€</span>
        </div>
        
        <div class="balance-item beneficios">
            <span class="balance-label">
                Beneficios Totales
                <small>(<?php echo $datos_balance['num_beneficios']; ?> registros)</small>
            </span>
            <span class="balance-amount">+<?php echo number_format($datos_balance['beneficios'], 2, ',', '.'); ?>€</span>
        </div>
        
        <div class="balance-item balance <?php echo $datos_balance['balance'] >= 0 ? 'positivo' : 'negativo'; ?>">
            <span class="balance-label">Balance con IVA</span>
            <span class="balance-amount">
                <?php echo ($datos_balance['balance'] >= 0 ? '+' : ''); ?>
                <?php echo number_format($datos_balance['balance'], 2, ',', '.'); ?>€
            </span>
        </div>

        <div class="balance-item balance <?php echo $datos_balance['balance_sin_iva'] >= 0 ? 'positivo' : 'negativo'; ?>">
            <span class="balance-label">Balance sin IVA</span>
            <span class="balance-amount">
                <?php echo ($datos_balance['balance_sin_iva'] >= 0 ? '+' : ''); ?>
                <?php echo number_format($datos_balance['balance_sin_iva'], 2, ',', '.'); ?>€
            </span>
        </div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}

add_action('wp_ajax_actualizar_balance', 'actualizar_balance_ajax');
add_action('wp_ajax_nopriv_actualizar_balance', 'actualizar_balance_ajax');

add_shortcode('balance_financiero', 'mostrar_balance');