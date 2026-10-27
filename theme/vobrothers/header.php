<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">Skip to content</a>

<div class="topbar">
	<div class="container topbar__inner">
		<a class="topbar__parent" href="<?php echo esc_url( vob_t_info( 'parent_url' ) ); ?>" target="_blank" rel="noopener">A <strong><?php echo esc_html( vob_t_info( 'parent_name' ) ); ?></strong> Company</a>
		<div class="topbar__contact">
			<a class="topbar__emergency" href="<?php echo esc_attr( vob_t_tel( vob_t_info( 'emergency_phone' ) ) ); ?>">
				<?php echo vob_icon( 'phone', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span>24/7 Emergency Service</span> <strong><?php echo esc_html( vob_t_info( 'emergency_phone' ) ); ?></strong>
			</a>
			<a class="topbar__email" href="mailto:<?php echo esc_attr( vob_t_info( 'email' ) ); ?>">
				<?php echo vob_icon( 'mail', 'icon icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php echo esc_html( vob_t_info( 'email' ) ); ?></span>
			</a>
		</div>
	</div>
</div>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img class="brand__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/img/logo-mark.png' ) ); ?>" width="199" height="240" alt="<?php echo esc_attr( vob_t_info( 'company_name' ) ?: get_bloginfo( 'name' ) ); ?>">
		</a>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<span class="nav-toggle__open"><?php echo vob_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="nav-toggle__close"><?php echo vob_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="screen-reader-text">Menu</span>
		</button>

		<nav class="site-nav" id="site-nav" aria-label="Main" data-nav>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'fallback_cb'    => false,
					'depth'          => 2,
				)
			);
			?>
			<div class="site-nav__cta">
				<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/#vob-form-quote' ) ); ?>">Request a Quote</a>
			</div>
		</nav>
	</div>
</header>

<main id="main" class="site-main">
