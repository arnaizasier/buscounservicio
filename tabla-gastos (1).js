jQuery(document).ready(function($) {
    function cambiarMes(valor) {
        const [mes, anio] = valor.split('-');
        window.location.href = `?mes=${mes}&anio=${anio}&pag=1`;
    }

    function eliminarRegistro(id) {
        if (!confirm('¿Estás seguro de que deseas eliminar este registro?')) {
            return;
        }

        $.ajax({
            url: tablaGastosAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'eliminar_registro_gasto',
                id: id,
                nonce: tablaGastosAjax.nonce
            },
            beforeSend: function() {
                // Deshabilitar el botón mientras se procesa
                $(`tr[data-id="${id}"] .boton-eliminar`).prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Eliminar la fila con animación
                    $(`tr[data-id="${id}"]`).fadeOut(400, function() {
                        $(this).remove();
                        
                        // Verificar si quedan registros
                        if ($('.tabla-gastos-beneficios tbody tr').length === 0) {
                            $('.tabla-gastos-beneficios').fadeOut(400, function() {
                                $(this).replaceWith('<p class="sin-registros">No hay registros para este mes.</p>');
                            });
                        }
                        
                        // Actualizar los totales
                        actualizarTotales();
                    });

                    // Mostrar mensaje de éxito
                    mostrarMensaje('Registro eliminado correctamente', 'exito');
                } else {
                    // Mostrar mensaje de error
                    mostrarMensaje(response.data || 'Error al eliminar el registro', 'error');
                    // Reactivar el botón
                    $(`tr[data-id="${id}"] .boton-eliminar`).prop('disabled', false);
                }
            },
            error: function() {
                mostrarMensaje('Error al comunicarse con el servidor', 'error');
                // Reactivar el botón
                $(`tr[data-id="${id}"] .boton-eliminar`).prop('disabled', false);
            }
        });
    }

    function actualizarTotales() {
        $.ajax({
            url: tablaGastosAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'obtener_totales_mes',
                mes: new URLSearchParams(window.location.search).get('mes') || new Date().getMonth() + 1,
                anio: new URLSearchParams(window.location.search).get('anio') || new Date().getFullYear(),
                nonce: tablaGastosAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.resumen-mes').html(response.data.html);
                }
            }
        });
    }

    function mostrarMensaje(mensaje, tipo) {
        // Crear el elemento del mensaje si no existe
        let $mensaje = $('#mensaje-resultado');
        if ($mensaje.length === 0) {
            $mensaje = $('<div id="mensaje-resultado"></div>');
            $('body').append($mensaje);
        }

        // Configurar el mensaje
        $mensaje
            .removeClass('mensaje-exito mensaje-error')
            .addClass('mensaje-' + tipo)
            .html(mensaje)
            .fadeIn(400);

        // Ocultar después de 5 segundos
        setTimeout(function() {
            $mensaje.fadeOut(400);
        }, 5000);
    }

    // Hacer global la función cambiarMes
    window.cambiarMes = cambiarMes;
    window.eliminarRegistro = eliminarRegistro;

    // Inicializar tooltips y otros elementos interactivos
    $('[data-toggle="tooltip"]').tooltip();
});

function cambiarFiltro(valor) {
    // Obtener los parámetros actuales de la URL
    const urlParams = new URLSearchParams(window.location.search);
    
    // Actualizar o añadir el parámetro de filtro
    urlParams.set('filtro', valor);
    
    // Resetear la página a 1 al cambiar el filtro
    urlParams.set('pag', '1');
    
    // Redirigir con los nuevos parámetros
    window.location.href = '?' + urlParams.toString();
}

// Hacer global la función
window.cambiarFiltro = cambiarFiltro;