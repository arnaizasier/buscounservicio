jQuery(document).ready(function($) {
    function cambiarPeriodoBalance(periodo) {
        // Obtener los parámetros actuales de la URL
        const urlParams = new URLSearchParams(window.location.search);
        
        // Actualizar el período en los parámetros
        urlParams.set('periodo', periodo);
        
        // Si cambiamos a anual, eliminar el parámetro mes
        if (periodo === 'anual') {
            urlParams.delete('mes');
        }
        
        // Mostrar indicador de carga con animación
        const loadingHtml = `
            <div class="balance-loading">
                <div class="loading-spinner"></div>
                <span>Actualizando...</span>
            </div>
        `;
        $('.balance-content').addClass('loading').append(loadingHtml);

        // Realizar la petición AJAX
        $.ajax({
            url: tablaGastosAjax.ajaxurl, // Usar la URL de AJAX de WordPress
            type: 'POST',
            data: {
                action: 'actualizar_balance',
                periodo: periodo,
                mes: urlParams.get('mes') || new Date().getMonth() + 1,
                anio: urlParams.get('anio') || new Date().getFullYear(),
                nonce: tablaGastosAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Animar la transición del contenido
                    $('.balance-content').fadeOut(300, function() {
                        $(this).html(response.data.html).fadeIn(300);
                    });
                    
                    // Actualizar la URL sin recargar la página
                    window.history.pushState({}, '', '?' + urlParams.toString());
                } else {
                    // Mostrar mensaje de error
                    mostrarMensajeError('Error al actualizar el balance');
                }
            },
            error: function(xhr, status, error) {
                mostrarMensajeError('Error de conexión');
                console.error('Error en la petición AJAX:', error);
            },
            complete: function() {
                // Eliminar indicador de carga
                $('.balance-content').removeClass('loading');
                $('.balance-loading').fadeOut(300, function() {
                    $(this).remove();
                });
            }
        });
    }

    // Función para mostrar mensajes de error
    function mostrarMensajeError(mensaje) {
        const errorHtml = `
            <div class="balance-error-message">
                ${mensaje}
                <button class="cerrar-error">&times;</button>
            </div>
        `;
        
        // Eliminar mensajes de error anteriores
        $('.balance-error-message').remove();
        
        // Mostrar el nuevo mensaje
        $('.balance-wrapper').prepend(errorHtml);
        
        // Configurar el cierre del mensaje
        $('.cerrar-error').on('click', function() {
            $(this).parent().fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            $('.balance-error-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Hacer la función disponible globalmente
    window.cambiarPeriodoBalance = cambiarPeriodoBalance;

    // Añadir botón de refrescar
    const refreshButtonHtml = `
        <button id="refresh-balance" class="balance-refresh-btn">Refrescar</button>
    `;
    $('.balance-wrapper').prepend(refreshButtonHtml);

    // Añadir evento al botón de refrescar
    $('#refresh-balance').on('click', function() {
        const currentPeriodo = new URLSearchParams(window.location.search).get('periodo') || 'mensual';
        cambiarPeriodoBalance(currentPeriodo);
    });

    // Añadir estilos para el botón de refrescar
    $('<style>')
        .text(`
            .balance-refresh-btn {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                margin-bottom: 20px;
                border-radius: 30px;
            }
            
            .balance-refresh-btn:hover {
                background-color: #45a049;
            }
        `)
        .appendTo('head');
});