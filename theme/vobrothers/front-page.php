<?php
/**
 * Home page.
 */

get_header();

$featured = new WP_Query(
	array(
		'post_type'      => 'vob_project',
		'posts_per_page' => 10,
		'meta_key'       => 'vob_featured',
		'meta_value'     => '1',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);
$groups = function_exists( 'vob_service_groups' ) ? vob_service_groups() : array();
$jobs   = function_exists( 'vob_jobs' ) ? vob_jobs() : null;
?>

<section class="hero">
	<?php
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( 'vob-hero', array( 'class' => 'hero__bg', 'alt' => '', 'fetchpriority' => 'high', 'loading' => 'eager' ) );
	}
	?>
	<div class="container hero__inner">
		<p class="eyebrow eyebrow--light">Mechanical &middot; Process Piping &middot; Plumbing &middot; Service</p>
		<h1 class="hero__title">Determined<br>for <span>Progress</span></h1>
		<p class="hero__lead">VO Brothers Mechanical builds and services the systems that keep Utah's labs, hospitals, campuses and plants running, from pure water and clean steam to medical gas, chilled water and plumbing.</p>
		<div class="hero__actions">
			<a class="btn btn--primary btn--lg" href="<?php echo esc_url( home_url( '/contact/#vob-form-quote' ) ); ?>">Request a Quote</a>
			<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( get_post_type_archive_link( 'vob_project' ) ); ?>">View Our Projects</a>
		</div>
	</div>
	<a class="hero__emergency" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>">
		<span class="pulse" aria-hidden="true"></span>
		<span>System down? <strong>24/7 emergency service: <?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></strong></span>
	</a>
</section>

<section class="proof">
	<div class="container proof__grid">
		<div class="proof__item"><strong>4,900<small> tons</small></strong><span>Central chiller plant at Weber State</span></div>
		<div class="proof__item"><strong>2M<small> gallons</small></strong><span>Thermal energy storage tank at Utah State</span></div>
		<div class="proof__item"><strong>24</strong><span>Specialty mechanical &amp; process services</span></div>
		<div class="proof__item"><strong>24/7</strong><span>Emergency service &amp; maintenance plans</span></div>
	</div>
</section>

<?php if ( $featured->have_posts() ) : ?>
<section class="section section--mist" aria-labelledby="highlights-title">
	<div class="container">
		<div class="section-head section-head--split">
			<div>
				<p class="eyebrow">Portfolio highlights //</p>
				<h2 class="section-title" id="highlights-title">Projects across Utah</h2>
			</div>
			<div class="carousel-controls">
				<button class="icon-btn" type="button" data-carousel-prev aria-label="Previous projects"><?php echo vob_icon( 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button class="icon-btn" type="button" data-carousel-next aria-label="Next projects"><?php echo vob_icon( 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		</div>
		<div class="carousel" data-carousel tabindex="0" aria-label="Featured projects">
			<?php
			while ( $featured->have_posts() ) {
				$featured->the_post();
				get_template_part( 'template-parts/project-card' );
			}
			wp_reset_postdata();
			?>
		</div>
		<p class="section-foot"><a class="link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'vob_project' ) ); ?>">See all projects <?php echo vob_icon( 'arrow', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
	</div>
</section>
<?php endif; ?>

<section class="section" aria-labelledby="services-title">
	<div class="container">
		<div class="section-head">
			<p class="eyebrow">What we do //</p>
			<h2 class="section-title" id="services-title">Services</h2>
			<p class="section-lead">Years of supporting a wide range of mechanical systems have taught us how facilities really operate. We plan carefully up front so time-sensitive projects go smoothly.</p>
		</div>
		<div class="service-grid">
			<?php foreach ( $groups as $group ) : ?>
				<?php $children = get_children( array( 'post_parent' => $group->ID, 'post_type' => 'vob_service', 'orderby' => 'menu_order', 'order' => 'ASC' ) ); ?>
				<a class="service-card" href="<?php echo esc_url( get_permalink( $group ) ); ?>">
					<span class="service-card__icon"><?php echo vob_icon( get_post_meta( $group->ID, 'vob_icon', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h3 class="service-card__title"><?php echo esc_html( $group->post_title ); ?></h3>
					<p class="service-card__text"><?php echo esc_html( get_post_meta( $group->ID, 'vob_tagline', true ) ); ?></p>
					<?php if ( $children ) : ?>
						<ul class="service-card__list">
							<?php foreach ( array_slice( $children, 0, 4 ) as $child ) : ?>
								<li><?php echo esc_html( $child->post_title ); ?></li>
							<?php endforeach; ?>
							<?php if ( count( $children ) > 4 ) : ?>
								<li class="more">+<?php echo count( $children ) - 4; ?> more</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>
					<span class="service-card__more">Learn more <?php echo vob_icon( 'arrow', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section--dark industries" aria-labelledby="industries-title">
	<div class="container">
		<div class="section-head">
			<p class="eyebrow eyebrow--light">Industries //</p>
			<h2 class="section-title" id="industries-title">Built for demanding facilities</h2>
		</div>
		<ul class="industry-grid">
			<li><?php echo vob_icon( 'cup' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Food &amp; Beverage</strong><span>Dairies, breweries, soda plants and food preparation</span></li>
			<li><?php echo vob_icon( 'flask' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Pharmaceutical &amp; Medical Devices</strong><span>FDA-regulated sanitary, pure water and clean steam systems</span></li>
			<li><?php echo vob_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Healthcare</strong><span>Hospitals, clinics and care facilities, including medical gas</span></li>
			<li><?php echo vob_icon( 'cap' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Higher Education</strong><span>Research labs, central plants, utility tunnels and performance halls</span></li>
			<li><?php echo vob_icon( 'factory' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Manufacturing</strong><span>Process piping, controls and tight environmental tolerances</span></li>
			<li><?php echo vob_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong>Commercial &amp; Data Centers</strong><span>Office buildings with chillers, pumps and rack cooling</span></li>
		</ul>
	</div>
</section>

<section class="section split" aria-labelledby="service-title">
	<div class="container split__inner">
		<div class="split__media">
			<?php
			$service_page = get_page_by_path( 'service-maintenance', OBJECT, 'vob_service' );
			if ( $service_page && has_post_thumbnail( $service_page ) ) {
				echo get_the_post_thumbnail( $service_page, 'vob-card', array( 'loading' => 'lazy', 'alt' => '' ) );
			}
			?>
		</div>
		<div class="split__body">
			<p class="eyebrow">Service &amp; maintenance //</p>
			<h2 class="section-title" id="service-title">Proactive, not reactive</h2>
			<p>The systems we install are the systems we keep running. Our service team handles emergency repairs around the clock and builds preventative maintenance plans around your equipment, schedule and regulatory requirements.</p>
			<ul class="checklist">
				<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> 24/7 emergency response</li>
				<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Custom preventative maintenance plans</li>
				<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> HVAC/R, plumbing, steam and process system service</li>
			</ul>
			<div class="btn-row">
				<a class="btn btn--primary" href="<?php echo esc_url( $service_page ? get_permalink( $service_page ) . '#vob-form-maintenance' : home_url( '/contact/' ) ); ?>">Get a Maintenance Plan</a>
				<a class="btn btn--outline" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>">Call 24/7: <?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></a>
			</div>
		</div>
	</div>
</section>

<section class="section section--mist" aria-labelledby="awards-title">
	<div class="container">
		<div class="section-head">
			<p class="eyebrow">Recognition //</p>
			<h2 class="section-title" id="awards-title">Awards &amp; certifications</h2>
		</div>
		<div class="award-grid">
			<?php for ( $i = 0; $i < 4; $i++ ) : ?>
				<div class="award placeholder-box">
					<?php echo vob_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<strong>Award or certification</strong>
					<span>Year &middot; Awarding organization</span>
				</div>
			<?php endfor; ?>
		</div>
		<p class="placeholder-note">Placeholder: VO Brothers to supply awards, safety recognitions and certifications.</p>
	</div>
</section>

<section class="section careers-band" aria-labelledby="careers-title">
	<div class="container careers-band__inner">
		<div>
			<p class="eyebrow eyebrow--light">Careers //</p>
			<h2 class="section-title" id="careers-title">A community of friends, teammates and talented mechanics</h2>
			<p>We value performance, and we value our friendships. With the right team, magic happens. Grow your career with people who have your back.</p>
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/careers/' ) ); ?>">
				<?php echo is_array( $jobs ) && $jobs ? esc_html( sprintf( 'See %d open position%s', count( $jobs ), 1 === count( $jobs ) ? '' : 's' ) ) : 'See open positions'; ?>
			</a>
		</div>
		<?php if ( is_array( $jobs ) && $jobs ) : ?>
			<ul class="careers-band__jobs">
				<?php foreach ( array_slice( $jobs, 0, 4 ) as $job ) : ?>
					<li><a href="<?php echo esc_url( $job['url'] ); ?>" target="_blank" rel="noopener"><strong><?php echo esc_html( $job['title'] ); ?></strong><span><?php echo esc_html( $job['department'] ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>

<section class="section parent-band" aria-labelledby="parent-title">
	<div class="container parent-band__inner">
		<p class="parent-band__mark" aria-hidden="true">Q</p>
		<div>
			<h2 class="parent-band__title" id="parent-title">A <?php echo esc_html( vob_t_info( 'parent_name' ) ); ?> Company</h2>
			<p>VO Brothers Mechanical is part of <?php echo esc_html( vob_t_info( 'parent_name' ) ); ?>. We operate under the VO Brothers name, with the same local team, from our shop in Ogden.</p>
		</div>
		<a class="btn btn--outline" href="<?php echo esc_url( home_url( '/about/quantum-industrial/' ) ); ?>">Learn more</a>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
