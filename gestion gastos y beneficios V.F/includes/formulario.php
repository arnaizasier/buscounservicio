<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="formulario-gastos-beneficios-wrapper">
    <form id="formulario-gastos-beneficios" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="procesar_gastos_beneficios">
        <?php wp_nonce_field('guardar_gasto_beneficio', 'gasto_beneficio_nonce'); ?>
        
        <fieldset>
            <h2>Añadir datos</h2>
            
            <!-- Campo: Tipo (Gasto o Beneficio) -->
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="gasto" selected>Gasto</option>
                    <option value="beneficio">Beneficio</option>
                </select>
            </div>

            <!-- Campo: Cantidad en euros -->
            <div class="form-group">
                <label for="cantidad">Cantidad (€):</label>
                <input type="number" id="cantidad" name="cantidad" placeholder="Ejemplo: 50" step="0.01" min="0" required>
            </div>

            <div class="form-group">
    <label for="iva_porcentaje">IVA: (opcional)</label>
    <select name="iva_porcentaje" id="iva_porcentaje" class="form-control">
        <option value="21">21% - IVA General</option>
        <option value="10">10% - IVA Reducido</option>
        <option value="4">4% - IVA Superreducido</option>
        <option value="0">0% - Exento</option>
    </select>
</div>

            <!-- Campo: Categoría -->
            <div class="form-group">
                <label for="categoria">Categoría: (opcional)</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <!-- Las opciones se cargarán dinámicamente con JavaScript -->
                </select>
            </div>

            <!-- Campo: Descripción -->
            <div class="form-group">
                <label for="descripcion">Descripción: (opcional)</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>

            <!-- Campo: Fecha -->
            <div class="form-group">
                <label for="fecha">Fecha: (opcional)</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <!-- Botón de enviar -->
            <div class="form-group">
                <button type="submit" id="btn-submit">Guardar</button>
            </div>
        </fieldset>
    </form>
    
    <?php
    // Mensajes de éxito/error
    if (isset($_GET['success'])) {
        echo '<div class="mensaje-exito" role="alert">Tu registro se ha añadido correctamente</div>';
    }
    if (isset($_GET['error'])) {
        $error_message = 'Error al añadir tu registro';
        switch ($_GET['error']) {
            case 'nonce':
                $error_message = 'Error de seguridad. Por favor, intenta de nuevo.';
                break;
            case 'tipo':
                $error_message = 'El tipo seleccionado no es válido.';
                break;
            case 'cantidad':
                $error_message = 'La cantidad debe ser mayor que 0.';
                break;
            case 'db':
                $error_message = 'Error al guardar en la base de datos.';
                break;
        }
        echo '<div class="mensaje-error" role="alert">' . esc_html($error_message) . '</div>';
    }
    ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoSelect = document.getElementById('tipo');
    const categoriaSelect = document.getElementById('categoria');
    
    // Definir las categorías para cada tipo
    const categorias = {
        'gasto': [
            'Gastos básicos',
            'Nóminas',
            'Impuestos',
            'Materiales y suministros',
            'Transporte y viajes',
            'Productos',
            'Mantenimiento y arreglos',
            'Publicidad y marketing',
            'Otros gastos'
        ],
        'beneficio': [
            'Servicio puntual',
            'Bono',
            'Suscripciones',
            'Venta de productos',
            'Reembolsos',
            'Otros ingresos'
        ]
    };
    
    // Función para actualizar las categorías
    function actualizarCategorias() {
        // Limpiar las opciones actuales
        categoriaSelect.innerHTML = '';
        
        const tipoSeleccionado = tipoSelect.value;
        
        if (tipoSeleccionado && categorias[tipoSeleccionado]) {
            // Agregar las nuevas opciones
            categorias[tipoSeleccionado].forEach((categoria, index) => {
                const option = document.createElement('option');
                option.value = categoria.toLowerCase();
                option.textContent = categoria;
                // Seleccionar la primera opción por defecto
                if (index === 0) {
                    option.selected = true;
                }
                categoriaSelect.appendChild(option);
            });
        }
    }
    
    // Escuchar cambios en el select de tipo
    tipoSelect.addEventListener('change', actualizarCategorias);
    
    // Inicializar las categorías inmediatamente ya que 'gasto' está seleccionado por defecto
    actualizarCategorias();
});

// Manejar el envío del formulario
document.getElementById('formulario-gastos-beneficios').addEventListener('submit', function(e) {
    // Obtener el botón
    const submitButton = document.getElementById('btn-submit');
    
    // Deshabilitar el botón y cambiar el texto
    submitButton.disabled = true;
    submitButton.innerHTML = 'Guardando...';
});

</script>