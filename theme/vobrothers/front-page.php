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
		<h1 class="hero__title">We Build<br><span>People</span></h1>
		<p class="hero__lead">VO Brothers Mechanical builds and services the systems that keep Utah's labs, hospitals, campuses and plants running, from pure water and clean steam to medical gas, chilled water and plumbing.</p>
		<div class="hero__actions">
			<a class="btn btn--primary btn--lg" href="<?php echo esc_url( home_url( '/contact/#vob-form-quote' ) ); ?>">Request a Quote</a>
			<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( get_post_type_archive_link( 'vob_project' ) ); ?>">View Our Projects</a>
		</div>
	</div>
	<a class="hero__emergency" href="<?php echo esc_url( vob_emergency_url() ); ?>">
		<span class="pulse" aria-hidden="true"></span>
		<span>System down? <strong>24/7/365 emergency service</strong> &rarr;</span>
	</a>
</section>

<section class="proof">
	<div class="container proof__grid">
		<div class="proof__item"><strong>4,900<small> tons</small></strong><span>Central chiller plant at Weber State</span></div>
		<div class="proof__item"><strong>2M<small> gallons</small></strong><span>Thermal energy storage tank at Utah State</span></div>
		<div class="proof__item"><strong>3</strong><span>Utah industry awards, including two in 2025</span></div>
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
			<p class="section-lead">Years of work on every kind of mechanical system have taught us how facilities really operate. We plan every detail up front, so time-sensitive projects run smoothly.</p>
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
			<p>We don't just install systems; we keep them running. Our service team handles emergency repairs around the clock and builds preventative maintenance plans around your equipment, schedule and regulatory requirements.</p>
			<ul class="checklist">
				<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> 24/7 emergency response</li>
				<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Custom preventative maintenance plans</li>
				<li><?php echo vob_icon( 'check', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> HVAC/R, plumbing, steam and process system service</li>
			</ul>
			<div class="btn-row">
				<a class="btn btn--primary" href="<?php echo esc_url( $service_page ? get_permalink( $service_page ) . '#vob-form-maintenance' : home_url( '/contact/' ) ); ?>">Get a Maintenance Plan</a>
				<a class="btn btn--outline" href="<?php echo esc_url( vob_emergency_url() ); ?>">24/7 Emergency Service</a>
			</div>
		</div>
	</div>
</section>

<section class="section fab-band" aria-labelledby="fab-title">
	<?php
	$fab_page = get_page_by_path( 'welding-fabrication', OBJECT, 'vob_service' );
	if ( $fab_page && has_post_thumbnail( $fab_page ) ) {
		echo get_the_post_thumbnail( $fab_page, 'vob-hero', array( 'class' => 'fab-band__bg', 'alt' => '', 'loading' => 'lazy' ) );
	}
	?>
	<div class="container fab-band__inner">
		<div>
			<p class="eyebrow eyebrow--light">Prefabrication //</p>
			<h2 class="section-title" id="fab-title">Built in the shop, faster in the field</h2>
			<p>As a <?php echo esc_html( vob_t_info( 'parent_name' ) ); ?> company, VO Brothers can draw on Quantum's 136,000 sq ft pipe fabrication facility and its robotic cutting and welding equipment. Spools built under controlled shop conditions mean safer job sites, consistent welds and shorter schedules.</p>
			<ul class="tag-list">
				<li><a href="<?php echo esc_url( $fab_page ? get_permalink( $fab_page ) : home_url( '/services/' ) ); ?>">HGG ProCutter 900 RB</a></li>
				<li><a href="<?php echo esc_url( $fab_page ? get_permalink( $fab_page ) : home_url( '/services/' ) ); ?>">Novarc Spool Welding Robot</a></li>
				<li><a href="<?php echo esc_url( $fab_page ? get_permalink( $fab_page ) : home_url( '/services/' ) ); ?>">BIM-driven spool design</a></li>
			</ul>
			<a class="btn btn--primary" href="<?php echo esc_url( $fab_page ? get_permalink( $fab_page ) : home_url( '/services/' ) ); ?>">Fabrication &amp; welding</a>
		</div>
		<div class="fab-stats">
			<div class="fab-stat"><strong>136,000<small> sq ft</small></strong><span>Quantum pipe fabrication facility</span></div>
			<div class="fab-stat"><strong>3–12<small>×</small></strong><span>Faster pipe welding with the Novarc robot vs. manual</span></div>
			<div class="fab-stat"><strong>7<small> min</small></strong><span>To weld 6″ Sch. 40 stainless (29 min by MIG)</span></div>
			<div class="fab-stat"><strong>55<small> min</small></strong><span>To weld 30″ Sch. 40 stainless (2.75 hrs by MIG)</span></div>
		</div>
	</div>
</section>

<section class="section section--mist" aria-labelledby="awards-title">
	<div class="container">
		<div class="section-head">
			<p class="eyebrow">Recognition //</p>
			<h2 class="section-title" id="awards-title">Proven in Utah</h2>
		</div>
		<div class="award-row">
			<?php
			$awards = array(
				array( 'Most Outstanding Specialty Contracting Project', 'Utah Construction + Design', '2025', 'Provo Wastewater Reclamation Facility' ),
				array( 'Most Outstanding Water Project', 'Utah Construction + Design', '2025', 'Davis Aqueduct Reach 1 Parallel Pipeline' ),
				array( 'Sub-Contractor of the Year', 'Associated Builders and Contractors of Utah', '2020', '' ),
			);
			foreach ( $awards as list( $award, $org, $year, $project ) ) :
				?>
				<div class="recognition__award">
					<?php echo vob_icon( 'award' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div>
						<strong><?php echo esc_html( $award ); ?></strong>
						<span><?php echo esc_html( $org . ' · ' . $year ); ?></span>
						<?php if ( $project ) : ?>
							<em><?php echo esc_html( $project ); ?></em>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="recognition">
			<div class="recognition__item"><strong>Safety first</strong><span>A dedicated safety manager and a zero-incident goal on every site.</span></div>
			<div class="recognition__item"><strong>Quantum backed</strong><span>Part of Quantum Industrial, with fabrication, training and resources to match.</span></div>
			<div class="recognition__item"><strong>24/7/365</strong><span>Emergency service and preventative maintenance, around the clock.</span></div>
		</div>
	</div>
</section>

<section class="section careers-band" aria-labelledby="careers-title">
	<div class="container careers-band__inner">
		<div>
			<p class="eyebrow eyebrow--light">Careers //</p>
			<h2 class="section-title" id="careers-title">Build your career with us</h2>
			<p>Developing and empowering our people is our most important mission. You'll get competitive wages, real benefits and room to grow through apprenticeships and training, alongside a team our people sum up in one word: brotherhood.</p>
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
		<img class="parent-band__logo" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/quantum-logo.png' ) ); ?>" width="447" height="96" alt="<?php echo esc_attr( vob_t_info( 'parent_name' ) ); ?>" loading="lazy">
		<div>
			<h2 class="parent-band__title" id="parent-title">A <?php echo esc_html( vob_t_info( 'parent_name' ) ); ?> Company</h2>
			<p>In 2024 VO Brothers Mechanical joined <?php echo esc_html( vob_t_info( 'parent_name' ) ); ?>, an industrial mechanical and plumbing contractor serving clients across the United States. We still operate as VO Brothers, with the same local team, from our shop in Ogden.</p>
		</div>
		<a class="btn btn--quantum" href="<?php echo esc_url( home_url( '/about/quantum-industrial/' ) ); ?>">Learn more</a>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
