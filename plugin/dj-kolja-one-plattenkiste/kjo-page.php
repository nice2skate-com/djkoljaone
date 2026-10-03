<?php
/**
 * Vorlage für Seiten ohne Elementor (z. B. Cookie-Richtlinie) im DJ-KOLJA-ONE-Design.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
list( $kjo_tel, $kjo_wa ) = kjo_icons();
$kjo_nav = array(
	'Hochzeit'    => '/hochzeits-dj/',
	'Geburtstag'  => '/geburtstags-dj/',
	'Firmenfeier' => '/firmenfeier-dj/',
	'Events'      => '/event-dj/',
	'Meine Musik' => '/meine-musik/',
	'Über mich'   => '/ueber-mich/',
	'FAQ'         => '/faq/',
	'Regionen'    => '/einsatzgebiete/',
);
$kjo_links = '';
foreach ( $kjo_nav as $kjo_t => $kjo_u ) {
	$kjo_links .= '<a class="kjo-l" href="' . esc_url( home_url( $kjo_u ) ) . '">' . esc_html( $kjo_t ) . '</a>';
}
$kjo_title  = trim( preg_replace( '/\s*\((EU|UK|US|CA|AU|ZA|BR)\)\s*$/', '', get_the_title() ) );
$kjo_cookie = kjo_cookie_url();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
<style id="kjo-plain-css"><?php echo KJO_PLAIN_CSS; // phpcs:ignore ?></style>
</head>
<body <?php body_class( 'kjo-plain' ); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
	wp_body_open();
}
?>
<header class="kjo-head kjo-sh" data-static="1">
	<div class="kjo-sh-row">
		<a class="kjo-sh-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( home_url( '/wp-content/uploads/2026/09/dj-kolja-one-logo-ohne-claim.png' ) ); ?>" alt="DJ KOLJA ONE"></a>
		<nav class="kjo-sh-nav" aria-label="Hauptmenü"><?php echo $kjo_links; // phpcs:ignore ?></nav>
		<div class="kjo-right">
			<div class="kjo-cta">
				<a class="kjo-tel" href="tel:<?php echo esc_attr( KJO_TEL ); ?>" aria-label="Anrufen" title="Anrufen"><?php echo $kjo_tel; // phpcs:ignore ?></a>
				<a class="kjo-wa" href="https://wa.me/<?php echo esc_attr( KJO_WA ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp schreiben" title="WhatsApp schreiben"><?php echo $kjo_wa; // phpcs:ignore ?></a>
			</div>
			<a class="kjo-sh-btn" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Wunschtermin prüfen</a>
		</div>
	</div>
	<nav class="kjo-nav2" aria-label="Menü"><?php echo $kjo_links; // phpcs:ignore ?><a class="kjo-l" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a></nav>
</header>
<main class="kjo-doc">
	<h1><?php echo esc_html( $kjo_title ); ?></h1>
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<footer class="kjo-sf">
	<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> DJ KOLJA ONE · Jedes Event findet nur einmal statt.</span>
	<nav aria-label="Rechtliches">
		<a href="<?php echo esc_url( home_url( '/impressum/' ) ); ?>">Impressum</a>
		<a href="<?php echo esc_url( home_url( '/datenschutz/' ) ); ?>">Datenschutz</a>
		<?php if ( $kjo_cookie ) : ?>
			<a href="<?php echo esc_url( $kjo_cookie ); ?>">Cookie-Richtlinie</a>
		<?php endif; ?>
	</nav>
</footer>
<?php wp_footer(); ?>
</body>
</html>
