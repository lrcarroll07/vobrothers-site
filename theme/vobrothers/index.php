<?php
/**
 * Fallback template: news/blog listing, search results and anything else.
 */

get_header();

vob_page_hero(
	array(
		'title' => is_search() ? sprintf( 'Search results for "%s"', get_search_query() ) : ( is_home() && get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : wp_strip_all_tags( get_the_archive_title() ) ),
	)
);
?>

<section class="section">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article class="post-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'vob-card', array( 'alt' => '' ) ); ?></a>
						<?php endif; ?>
						<div class="post-card__body">
							<p class="post-card__date"><?php echo esc_html( get_the_date() ); ?></p>
							<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p>Nothing here yet.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
