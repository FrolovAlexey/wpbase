<?php
/*
Template Name: Home Template
*/
get_header(); ?>

<div id="content">

	<?php while ( have_posts( ) ) : the_post(); ?>
	<div class="post" id="post-<?php the_ID(); ?>">
		<?php the_title( '<div class="title"><h1>', '</h1></div>' ); ?>
		<div class="content">
			<?php the_content(); ?>			
			<?php edit_post_link( __( 'Edit', 'base' ) ); ?>
			<?php
			$r = new WP_Query( array(
						'post_type'   => 'page',
						'post_status' => 'publish',
						'post_parent' => get_the_ID(),
						)
					);

			if ( $r->have_posts() ) : while ( $r->have_posts() ) : $r->the_post();
			?>

			<blockquote>
				<?php the_content(); ?>
			</blockquote>

			<?php endwhile; ?>

			<?php endif; ?>

			<?php wp_reset_postdata(); ?>
		</div>
	</div>
	<?php endwhile; ?>

</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>