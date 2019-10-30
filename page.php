<?php get_header(); ?>
    <div class="half in-page-block-wrapper">
      <h1><?php the_title(); ?></h1>
		<?php 
			the_post();
			the_content();
		?>				
  	</div>
<?php get_footer(); ?>