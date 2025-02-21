<?php
$template_loader = new Listeo_Core_Template_Loader;
$is_featured = listeo_core_is_featured($post->ID);
$is_instant = listeo_core_is_instant_booking($post->ID);
$listing_type = get_post_meta($post->ID, '_listing_type', true);

$show_as_ad = false;
if (isset($data)) :
    $show_as_ad = isset($data->ad) ? $data->ad : '';
    if ($show_as_ad) {
        $ad_type = get_post_meta($post->ID, 'ad_type', true);
        $ad_id = get_post_meta($post->ID, 'ad_id', true);
    }
endif;
?>

<!-- Listing Item -->
<div class="col-lg-12 col-md-12">
    <div <?php if ($show_as_ad) : ?> data-ad-id="<?php echo $ad_id; ?>" data-campaign-type="<?php echo $ad_type; ?>" <?php endif; ?> class="listing-item-container listing-geo-data list-layout <?php echo esc_attr('listing-type-' . $listing_type) ?>" <?php echo listeo_get_geo_data($post); ?>>
        <a href="<?php the_permalink(); ?>" class="listing-item <?php if ($is_featured) { ?>featured-listing<?php } ?>">

            <div class="listing-small-badges-container">
                <?php if ($is_featured) { ?>
                    <div class="listing-small-badge featured-badge"><i class="fa fa-star"></i> <?php esc_html_e('Featured', 'listeo_core'); ?></div><br>
                <?php } ?>
            </div>

            <!-- Image -->
            <div class="listing-item-image">
                <?php $template_loader->get_template_part('content-listing-image'); ?>
                <?php $terms = get_the_terms(get_the_ID(), 'listing_category');
                if ($terms && !is_wp_error($terms)) :
                    $categories = array();
                    foreach ($terms as $term) {
                        $categories[] = $term->name;
                    }
                    $categories_list = join(", ", $categories);
                ?>
                    <span class="tag">
                        <?php esc_html_e($categories_list) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div class="listing-item-content">
                <?php if (get_post_meta($post->ID, '_opening_hours_status', true)) {
                    if (listeo_check_if_open()) { ?>
                        <div class="listing-badge now-open"><?php esc_html_e('Now Open', 'listeo_core'); ?></div>
                    <?php } else {
                        if (listeo_check_if_has_hours()) { ?>
                            <div class="listing-badge now-closed"><?php esc_html_e('Now Closed', 'listeo_core'); ?></div>
                    <?php }
                    }
                } ?>

                <div class="listing-item-inner">
                    <h3><?php if ($show_as_ad) : ?><div class="listeo-ad-badge tip" data-tip-content="<?php echo esc_html_e('This is paid advertisement', 'listeo_core'); ?>"><?php esc_html_e('AD', 'listeo_core'); ?></div><?php endif; ?>
                        <?php the_title(); ?>
                        <?php if (listeo_core_is_verified($post->ID)) : ?><i class="verified-icon"></i><?php endif; ?>
                    </h3>
                    <span><?php the_listing_location_link($post->ID, false); ?></span>

                    <!-- RESEÑAS PRIMERO -->
                    <?php
                    if (!get_option('listeo_disable_reviews')) {
                        $rating = get_post_meta($post->ID, 'listeo-avg-rating', true);
                        if (!$rating && get_option('listeo_google_reviews_instead')) {
                            $reviews = listeo_get_google_reviews($post);
                            if (!empty($reviews['result']['reviews'])) {
                                $rating = number_format_i18n($reviews['result']['rating'], 1);
                                $rating = str_replace(',', '.', $rating);
                            }
                        }
                        if (isset($rating) && $rating > 0) :
                            $rating_type = get_option('listeo_rating_type', 'star');
                            if ($rating_type == 'numerical') { ?>
                                <div class="numerical-rating" data-rating="<?php $rating_value = esc_attr(round($rating, 1));
                                                                            printf("%0.1f", $rating_value); ?>">
                                <?php } else { ?>
                                    <div class="star-rating" data-rating="<?php echo $rating; ?>">
                                    <?php } ?>
                                    <?php $number = listeo_get_reviews_number($post->ID);
                                    if (!get_post_meta($post->ID, 'listeo-avg-rating', true) && get_option('listeo_google_reviews_instead')) {
                                        $number = $reviews['result']['user_ratings_total'];
                                    } ?>
                                    <div class="rating-counter">(<?php printf(_n('%s review', '%s reviews', $number, 'listeo_core'), number_format_i18n($number)); ?>)</div>
                                    </div>
                            <?php endif;
                    } ?>

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

                </div>

                <?php
                if (listeo_core_check_if_bookmarked($post->ID)) {
                    $nonce = wp_create_nonce("listeo_core_bookmark_this_nonce"); ?>
                    <span class="like-icon listeo_core-unbookmark-it liked" data-post_id="<?php echo esc_attr($post->ID); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"></span>
                    <?php } else {
                    if (is_user_logged_in()) {
                        $nonce = wp_create_nonce("listeo_core_remove_fav_nonce"); ?>
                        <span class="save listeo_core-bookmark-it like-icon" data-post_id="<?php echo esc_attr($post->ID); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"></span>
                    <?php } else { ?>
                        <span class="save like-icon tooltip left" title="<?php esc_html_e('Login To Bookmark Items', 'listeo_core'); ?>"></span>
                    <?php } ?>
                <?php } ?>

            </div>
        </a>
    </div>
</div>
<!-- Listing Item / End -->
