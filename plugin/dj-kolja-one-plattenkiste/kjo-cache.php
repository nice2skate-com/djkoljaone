<?php
/**
 * Seiten-Cache (seit 1.55.0)
 *
 * Die Elementor-Seiten werden für Besucher ohne Anmeldung einmal erzeugt und dann bis zu 8 Stunden als fertiges
 * HTML ausgeliefert (vorher bei jedem Aufruf rund 0,8 s Rechenzeit auf dem Server).
 * Nicht zwischengespeichert werden:
 *  - angemeldete Nutzer, POST-Anfragen und alle Aufrufe mit Parametern (?datum=, ?song=, ?gclid=, ?songwunsch= …),
 *  - Seiten mit Formular bzw. Warenkorb (Kontakt, Technik mieten) und alles außer Seiten mit der Vorlage „Canvas“.
 * Verfügbarkeit, Songs, Bewertungen und Songwünsche kommen ohnehin per REST nachgeladen und bleiben aktuell.
 * Geleert wird automatisch bei jeder Änderung an Seiten, Medien, Einstellungen, Plugins und beim Einspielen der Vorlagen.
 * Abschalten: add_filter( 'kjo_seiten_cache', '__return_false' );
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_CACHE_TTL  = 8 * HOUR_IN_SECONDS; // kürzer als die Gültigkeit von WordPress-Nonces (12–24 h)
const KJO_CACHE_OHNE  = array( 'kontakt', 'technik-mieten' );

function kjo_cache_dir() {
	return WP_CONTENT_DIR . '/cache/kjo-seiten/';
}
function kjo_cache_datei() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ); // nicht der Host-Kopf der Anfrage: der ist frei wählbar (seit 1.72.1)
	$pfad = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '/';
	$http = is_ssl() ? 'https' : 'http';
	return kjo_cache_dir() . md5( KJO_VERSION . '|' . $http . '://' . $host . $pfad ) . '.html';
}

/** Darf diese Anfrage aus dem Cache kommen bzw. in den Cache? */
function kjo_cache_erlaubt() {
	if ( ! apply_filters( 'kjo_seiten_cache', true ) || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	$m = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( ! in_array( $m, array( 'GET', 'HEAD' ), true ) || ! empty( $_SERVER['QUERY_STRING'] ) || is_user_logged_in() ) {
		return false;
	}
	foreach ( array_keys( $_COOKIE ) as $c ) { // phpcs:ignore
		if ( preg_match( '/^(wordpress_logged_in_|wp-postpass_|comment_author_)/', $c ) ) {
			return false;
		}
	}
	if ( ! is_singular( 'page' ) || is_preview() || is_404() ) {
		return false;
	}
	$id = get_queried_object_id();
	if ( 'elementor_canvas' !== get_page_template_slug( $id ) || post_password_required( $id ) ) {
		return false;
	}
	return ! in_array( get_post_field( 'post_name', $id ), KJO_CACHE_OHNE, true );
}

add_action(
	'template_redirect',
	function () {
		if ( ! kjo_cache_erlaubt() ) {
			return;
		}
		$f = kjo_cache_datei();
		if ( is_readable( $f ) && ( time() - (int) filemtime( $f ) ) < KJO_CACHE_TTL ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'X-KJO-Cache: HIT' );
			readfile( $f ); // phpcs:ignore
			exit;
		}
		header( 'X-KJO-Cache: MISS' );
		ob_start(
			function ( $html ) use ( $f ) {
				if ( 200 === http_response_code() && strlen( $html ) > 5000 && false !== stripos( $html, '</html>' ) ) {
					if ( wp_mkdir_p( kjo_cache_dir() ) ) {
						$dir = kjo_cache_dir();
						if ( ! file_exists( $dir . '.htaccess' ) ) {
							@file_put_contents( $dir . '.htaccess', "Require all denied\n" ); // phpcs:ignore
						}
						$tmp = $f . '.' . wp_rand() . '.tmp';
						if ( false !== @file_put_contents( $tmp, $html . "\n<!-- DJ KOLJA ONE Seiten-Cache " . gmdate( 'Y-m-d H:i' ) . " UTC -->" ) ) { // phpcs:ignore
							@rename( $tmp, $f ); // phpcs:ignore
						}
					}
				}
				return $html;
			}
		);
	},
	0 // vor allen anderen template_redirect-Aufgaben (Weiterleitungen werden nicht gespeichert, nur Status 200)
);

/** Cache komplett leeren. Rückgabe: Anzahl gelöschter Seiten. */
function kjo_cache_leeren() {
	$n = 0;
	foreach ( (array) glob( kjo_cache_dir() . '*.html' ) as $f ) {
		if ( @unlink( $f ) ) { // phpcs:ignore
			$n++;
		}
	}
	return $n;
}
/* Inhalte, die auf den Seiten erscheinen (nicht: Anfragen, Songwünsche, Musiklisten – die würden den Cache bei jedem Gast leeren). */
add_action(
	'save_post',
	function ( $id ) {
		if ( in_array( get_post_type( $id ), array( 'page', 'post', 'attachment', 'elementor_library', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 'nav_menu_item' ), true ) ) {
			kjo_cache_leeren();
		}
	}
);
foreach ( array( 'add_attachment', 'edit_attachment', 'delete_attachment', 'switch_theme', 'wp_update_nav_menu', 'customize_save_after', 'upgrader_process_complete', 'activated_plugin', 'deactivated_plugin', 'elementor/core/files/clear_cache' ) as $kjo_h ) {
	add_action( $kjo_h, 'kjo_cache_leeren' );
}
/* Einstellungen von WordPress, Elementor, Complianz, Site Kit und diesem Plugin: Cache leeren (Zwischenwerte ausgenommen). */
add_action(
	'updated_option',
	function ( $name ) {
		if ( 0 === strpos( $name, '_transient' ) || 0 === strpos( $name, '_site_transient' ) || in_array( $name, array( 'cron', 'kjo_sw_abend', 'kjm_testdaten_ab' ), true ) ) {
			return;
		}
		if ( preg_match( '/^(blogname|blogdescription|site_icon|page_on_front|show_on_front|elementor_|cmplz|complianz|googlesitekit|kjo_|kjm_)/', $name ) ) {
			kjo_cache_leeren();
		}
	}
);
/* Täglich alte Dateien wegräumen. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'kjo_cache_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kjo_cache_cron' );
		}
	}
);
add_action(
	'kjo_cache_cron',
	function () {
		foreach ( (array) glob( kjo_cache_dir() . '*' ) as $f ) {
			if ( is_file( $f ) && '.htaccess' !== basename( $f ) && time() - (int) filemtime( $f ) > KJO_CACHE_TTL ) {
				@unlink( $f ); // phpcs:ignore
			}
		}
	}
);
