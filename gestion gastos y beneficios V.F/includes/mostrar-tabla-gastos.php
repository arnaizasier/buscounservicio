<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tabla_Gastos_Beneficios {
    private $wpdb;
    private $registros_por_pagina = 20;
    private $mes_actual;
    private $anio_actual;
    private $pagina_actual;
    private $filtro_tipo; // Nueva propiedad

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Inicializar valores
        $this->mes_actual = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
        $this->anio_actual = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));
        $this->pagina_actual = isset($_GET['pag']) ? max(1, intval($_GET['pag'])) : 1;
        $this->filtro_tipo = isset($_GET['filtro']) ? sanitize_text_field($_GET['filtro']) : 'todos';
        
        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    public function registrar_assets() {
        wp_enqueue_style(
            'tabla-gastos-beneficios-estilos',
            plugin_dir_url(__FILE__) . '../assets/tabla-gastos.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'tabla-gastos-beneficios-script',
            plugin_dir_url(__FILE__) . '../assets/tabla-gastos.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('tabla-gastos-beneficios-script', 'tablaGastosAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eliminar_registro_nonce')
        ]);
    }

    private function obtener_total_registros() {
        $where_tipo = '';
        if ($this->filtro_tipo !== 'todos') {
            $where_tipo = $this->wpdb->prepare(" AND tipo = %s", $this->filtro_tipo);
        }

        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}gastos_beneficios 
            WHERE MONTH(fecha) = %d AND YEAR(fecha) = %d" . $where_tipo,
            $this->mes_actual,
            $this->anio_actual
        ));
    }

    private function obtener_registros() {
        $offset = ($this->pagina_actual - 1) * $this->registros_por_pagina;
        
        $where_tipo = '';
        if ($this->filtro_tipo !== 'todos') {
            $where_tipo = $this->wpdb->prepare(" AND tipo = %s", $this->filtro_tipo);
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT id, tipo, cantidad, categoria, fecha, descripcion
            FROM {$this->wpdb->prefix}gastos_beneficios 
            WHERE MONTH(fecha) = %d AND YEAR(fecha) = %d" . $where_tipo . " 
            ORDER BY fecha DESC 
            LIMIT %d OFFSET %d",
            $this->mes_actual,
            $this->anio_actual,
            $this->registros_por_pagina,
            $offset
        ));
    }

    private function obtener_meses_disponibles() {
        return $this->wpdb->get_results(
            "SELECT DISTINCT YEAR(fecha) as anio, MONTH(fecha) as mes
            FROM {$this->wpdb->prefix}gastos_beneficios 
            ORDER BY fecha DESC"
        );
    }

    private function mostrar_selector_meses($meses_disponibles) {
        $html = '<div class="selector-meses">';
        $html .= '<label for="historial-meses">Seleccionar período: </label>';
        $html .= '<select id="historial-meses" onchange="cambiarMes(this.value)">';
        
        foreach ($meses_disponibles as $mes) {
            $fecha = DateTime::createFromFormat('!m', $mes->mes);
            $nombre_mes = ucfirst(strftime('%B', $fecha->getTimestamp()));
            $selected = ($mes->mes == $this->mes_actual && $mes->anio == $this->anio_actual) ? 'selected' : '';
            
            $html .= sprintf(
                '<option value="%d-%d" %s>%s %d</option>',
                $mes->mes,
                $mes->anio,
                $selected,
                $nombre_mes,
                $mes->anio
            );
        }
        
        $html .= '</select>';
        $html .= '</div>';
        
        return $html;
    }

    private function mostrar_filtro_tipo() {
        $html = '<div class="filtro-tipo">';
        $html .= '<label for="filtro-tipo">Mostrar: </label>';
        $html .= '<select id="filtro-tipo" onchange="cambiarFiltro(this.value)">';
        $html .= sprintf('<option value="todos" %s>Todos</option>', 
               $this->filtro_tipo === 'todos' ? 'selected' : '');
        $html .= sprintf('<option value="gasto" %s>Solo Gastos</option>', 
               $this->filtro_tipo === 'gasto' ? 'selected' : '');
        $html .= sprintf('<option value="beneficio" %s>Solo Beneficios</option>', 
               $this->filtro_tipo === 'beneficio' ? 'selected' : '');
        $html .= '</select>';
        $html .= '</div>';
        
        return $html;
    }

    private function mostrar_tabla($results) {
        if (empty($results)) {
            return '<p class="sin-registros">No hay registros para este mes.</p>';
        }

        $html = '<div class="tabla-scroll">'; // Añadido contenedor
        $html .= '<table class="tabla-gastos-beneficios">';
        $html .= '<thead><tr>';
        $html .= '<th>Tipo</th>';
        $html .= '<th>Cantidad</th>';
        $html .= '<th>Categoría</th>';
        $html .= '<th>Fecha</th>';
        $html .= '<th>Descripción</th>';
        $html .= '<th>Acciones</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($results as $row) {
            $tipo_clase = $row->tipo === 'ingreso' ? 'ingreso' : 'gasto';
            $html .= sprintf(
                '<tr data-id="%d" class="%s">',
                esc_attr($row->id),
                $tipo_clase
            );
            $html .= sprintf('<td>%s</td>', esc_html(ucfirst($row->tipo)));
            $html .= sprintf('<td>%.2f €</td>', esc_html($row->cantidad));
            $html .= sprintf('<td>%s</td>', esc_html($row->categoria));
            $html .= sprintf('<td>%s</td>', esc_html(date('d/m/Y', strtotime($row->fecha))));
            $html .= sprintf('<td>%s</td>', esc_html($row->descripcion));
            $html .= '<td>';
            $html .= sprintf(
                '<button class="boton-eliminar" onclick="eliminarRegistro(%d)">
                    <span class="dashicons dashicons-trash"></span>
                </button>',
                esc_js($row->id)
            );
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function mostrar_paginacion($total_registros) {
        $total_paginas = ceil($total_registros / $this->registros_por_pagina);
        
        if ($total_paginas <= 1) {
            return '';
        }

        $html = '<div class="paginacion">';
        
        // Botón anterior
        if ($this->pagina_actual > 1) {
            $html .= sprintf(
                '<a href="?mes=%d&anio=%d&pag=%d&filtro=%s" class="boton-paginacion">&laquo;</a>',
                $this->mes_actual,
                $this->anio_actual,
                $this->pagina_actual - 1,
                $this->filtro_tipo
            );
        }

        // Números de página
        for ($i = max(1, $this->pagina_actual - 2); $i <= min($total_paginas, $this->pagina_actual + 2); $i++) {
            if ($i == $this->pagina_actual) {
                $html .= sprintf('<span class="pagina-actual">%d</span>', $i);
            } else {
                $html .= sprintf(
                    '<a href="?mes=%d&anio=%d&pag=%d&filtro=%s" class="pagina">%d</a>',
                    $this->mes_actual,
                    $this->anio_actual,
                    $i,
                    $this->filtro_tipo,
                    $i
                );
            }
        }

        // Botón siguiente
        if ($this->pagina_actual < $total_paginas) {
            $html .= sprintf(
                '<a href="?mes=%d&anio=%d&pag=%d&filtro=%s" class="boton-paginacion">&raquo;</a>',
                $this->mes_actual,
                $this->anio_actual,
                $this->pagina_actual + 1,
                $this->filtro_tipo
            );
        }

        $html .= '</div>';
        return $html;
    }

    public function render() {
        $total_registros = $this->obtener_total_registros();
        $results = $this->obtener_registros();
        $meses_disponibles = $this->obtener_meses_disponibles();

        ob_start();
        
        echo '<div class="controles-tabla">';
        echo $this->mostrar_selector_meses($meses_disponibles);
        echo $this->mostrar_filtro_tipo();
        echo '</div>';
        
        echo $this->mostrar_tabla($results);
        echo $this->mostrar_paginacion($total_registros);
        
        return ob_get_clean();
    }
}

function mostrar_tabla_gastos_beneficios() {
    $tabla = new Tabla_Gastos_Beneficios();
    return $tabla->render();
}

add_shortcode('mostrar_tabla', 'mostrar_tabla_gastos_beneficios');