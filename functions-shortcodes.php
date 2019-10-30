<?php

// Ateliers
function wallis_shortcode_ateliers() {
    $args = array (
        'post_type' => array('atelier'),
        'posts_per_page' 	=> -1,
        'meta_key'			=> 'dates_0_date',
        'orderby'			=> 'meta_value',
        'order'				=> 'ASC'
    );

    $query = new WP_Query( $args );

    $html = "";

    ob_start();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            get_template_part("snippets/ateliers-demonstrations-item");
        }
    } else {
        get_template_part("snippets/pending-program");
    }
    $html .= ob_get_clean();

    wp_reset_postdata();

    return $html;
}
add_shortcode( 'ateliers', 'wallis_shortcode_ateliers' );

