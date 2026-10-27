<?php
/**
 * Not found.
 */

get_header();

vob_page_hero( array( 'title' => 'Page not found' ) );
?>

<section class="section">
	<div class="container prose prose--page">
		<p>The page you're looking for has moved or no longer exists. Try one of these instead:</p>
		<ul>
			<li><a href="<?php echo esc_url( get_post_type_archive_link( 'vob_service' ) ); ?>">Services</a></li>
			<li><a href="<?php echo esc_url( get_post_type_archive_link( 'vob_project' ) ); ?>">Projects</a></li>
			<li><a href="<?php echo esc_url( home_url( '/careers/' ) ); ?>">Careers</a></li>
			<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
		</ul>
	</div>
</section>

<?php
get_footer();
