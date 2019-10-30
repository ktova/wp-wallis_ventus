<?php get_header(); ?>
      <div class="in-page-wrapper">
        <div class="in-page-block-wrapper">
          <h1>Association Bernex.net</h1>
        </div>

        <div class="in-page-block-wrapper">
          <h2>Pr&eacute;sentation</h2>
		  <?php the_post(); the_content(); ?>
        </div>

          <div class="half in-page-block-wrapper">
              <h2>Prochaines ouvertures</h2>
              <?php

                $args = array (
                    "post_type" => array("ouverture"),
                    "posts_per_page" => 4,
                    "meta_query" => array("date" => array("key" => "date", "compare" => ">=", "value" => date("Ymd", time() - 60 * 60 * 24))),
                    "orderby" => "date",
                    "order" => "ASC"
                );

                $query = new WP_Query($args);

                if ( $query->have_posts() ) {
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        get_template_part("snippets/ouverture-item");
                    }
                    ?>
                    <p>&nbsp;</p>
                    <span class="lesson-subtitle">* le jeudi matin est d&eacute;di&eacute; &agrave; la photo et aux conseils g&eacute;n&eacute;raux : pas d'assistance technique.</span>
                    <?php
                } else {
                    get_template_part("snippets/pending-program");
                }

                wp_reset_postdata();

              ?>
          </div>

          <div class="half in-page-block-wrapper">
              <h2>Prochaines conf&eacute;rences (publiques et gratuites)</h2>
              <?php

              $args = array (
                  "post_type" => array("conference"),
                  "posts_per_page" => 2,
                  "meta_query" => array("date" => array("key" => "date", "compare" => ">=", "value" => date("Ymd"))),
                  "orderby" => "date",
                  "order" => "ASC"
              );

              $query = new WP_Query( $args );

              if ( $query->have_posts() ) {
                  while ( $query->have_posts() ) {
                      $query->the_post();
                      get_template_part("snippets/conference-item");
                  }
              } else {
                  get_template_part("snippets/pending-program");
              }

              wp_reset_postdata();
              ?>
          </div>

      <div class="half in-page-block-wrapper">
        <h2>Prochains ateliers</h2>
        <?php

            $args = array (
                "post_type" => array("atelier"),
                "posts_per_page" 	=> 5,
                "meta_query" => array("date" => array("key" => "dates_0_date", "compare" => ">=", "value" => date("Ymd"))),
                "orderby" => array("date" => "ASC"),
            );

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    get_template_part("snippets/ateliers-demonstrations-item");
                }
            } else {
                get_template_part("snippets/pending-program");
            }

            wp_reset_postdata();

        ?>
        <br />
        <a href="/ateliers-demonstrations/" style="color: black;">Tous les ateliers →</a>
    </div>

    <?php

    $args = array (
        "post_type" => array("atelier"),
        "post_per_page" => 4,
        "meta_query" => array("date" => array("key" => "dates_0_dates", "compare" => ">=", "value" => date("Ymd"))),
        "orderby" => array("date" => "ASC"),
     );
    $query = new WP_Query( $args );
    if( $query->have_posts() ){
        while( $query->have_posts() ) {
             $query->the_post();
             get_template_part("/atelier-template");
        }
    } else {
        echo 'Known Error';
    }
    wp_reset_postdata();
    
    ?>