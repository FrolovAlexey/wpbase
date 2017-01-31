<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
	<div class="title">
			<?php if ( is_single() ) :
				the_title( '<h1>', '</h1>' );
			else :
				the_title( '<h2><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
			endif; ?>		
			<p class="meta-info">
				<a href="<?php echo get_date_archive_link() ?>" rel="bookmark">
					 <time datetime="<?php echo get_the_date('Y-m-d'); ?>">
					  <?php the_time( 'F jS, Y' ) ?>
					 </time>
				</a>
				<?php _e( 'by', 'base' ); ?> <a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) ); ?>"><?php the_author(); ?></a>
			</p>
	</div>
	<div class="content">
		<?php the_post_thumbnail( 'full' ); ?>
		<?php if ( is_single() ) :
			the_content();
		else:
			theme_the_excerpt();
		endif; ?>
	</div>
	<?php wp_link_pages(); ?>
	<div class="meta">
		<ul>
			<li><?php _e( 'Posted in', 'base' ); ?> <?php the_category( ', ' ) ?></li>
			<li><?php comments_popup_link( __( 'No Comments', 'base' ), __( '1 Comment', 'base' ), __( '% Comments', 'base' ) ); ?></li>
			<?php the_tags( __( '<li>Tags: ', 'base' ), ', ', '</li>' ); ?>
			<?php edit_post_link( __( 'Edit', 'base' ), '<li>', '</li>' ); ?>
		</ul>
	</div>
</div>
