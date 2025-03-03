<?php
wp_redirect(home_url('/error-404')); // Cambia '/pagina-destino' por la URL a la que quieres redirigir
exit;
?>


<div style="background-color: #2755d3; padding: 35px 25px; text-align: center;">
    <!-- Redes Sociales -->
    <div style="margin-bottom: 20px;">
        <a href="https://www.instagram.com/buscounservicio/" target="_blank" style="display: inline-block; margin: 0 10px;">
            <img width="32" height="32" src="https://eoyhwey.stripocdn.email/content/assets/img/social-icons/circle-colored/instagram-circle-colored.png" alt="Instagram" style="border: 0;">
        </a>
    </div>

    <!-- Texto de Soporte -->
    <div style="margin-bottom: 35px;">
        <p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #ffffff; margin: 0;">
            Contacta con nosotros a través de nuestra <a href="https://buscounservicio.es/contacto/" target="_blank" style="color: #ffffff; text-decoration: underline;">página de soporte</a>
        </p>
    </div>

    <!-- Aviso de Seguridad -->
    <div style="margin-bottom: 35px;">
        <p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #ffffff; margin: 0; line-height: 1.5;">
            Protegemos tu seguridad y tu privacidad. Nunca pediremos información personal (como contraseñas o números de tarjetas de crédito) en un correo electrónico.
        </p>
    </div>

    <!-- Enlaces de Políticas -->
    <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <a href="https://buscounservicio.es/politica-de-privacidad/" target="_blank" style="color: #ffffff; text-decoration: none; font-family: arial, helvetica, sans-serif; font-size: 14px;">
            Política de privacidad
        </a>
        <a href="https://buscounservicio.es/terminos-y-condiciones/" target="_blank" style="color: #ffffff; text-decoration: none; font-family: arial, helvetica, sans-serif; font-size: 14px;">
            Términos y condiciones
        </a>
        <a href="https://buscounservicio.es/configura-tus-cookies/" target="_blank" style="color: #ffffff; text-decoration: none; font-family: arial, helvetica, sans-serif; font-size: 14px;">
            Política de cookies
        </a>
        <a href="https://buscounservicio.es/politica-de-devoluciones-y-reembolsos/" target="_blank" style="color: #ffffff; text-decoration: none; font-family: arial, helvetica, sans-serif; font-size: 14px;">
            Política de devoluciones y reembolsos
        </a>
    </div>
</div>














































<!-- SERVICIOS DESPUÉS -->
                    <?php
                    $_menu = get_post_meta(get_the_ID(), '_menu', true);

                    if (!empty($_menu) && is_array($_menu)) {
                        $service_count = 0;
                        echo '<ul class="listing-services">';

                        foreach ($_menu as $menu) {
                            if (!empty($menu['menu_elements'])) {
                                foreach ($menu['menu_elements'] as $item) {
                                    if ($service_count < 3) { // Mostrar solo los primeros tres servicios
                                        echo '<li><strong>' . esc_html($item['name']) . '</strong>';
                                        if (!empty($item['price'])) {
                                            echo '<span class="price">' . esc_html($item['price']) . ' €</span>';
                                        }
                                        echo '</li>';
                                        $service_count++;
                                    }
                                }
                            }
                            if ($service_count >= 3) break; // Salir del bucle después de 3 elementos
                        }
                        echo '</ul>';
                    }
                    ?>
                    <!-- Fin de servicios -->
