<?php
/**
 * Ladezeit (seit 1.54.0) – Ergebnisse aus PageSpeed Insights:
 *  1) Browser-Cache und Komprimierung für CSS, JS, Bilder und Schriften per .htaccess
 *     (Strato lieferte diese Dateien ohne Cache-Angabe und unkomprimiert aus).
 *  2) Unbenutzte Elementor-Schriften Roboto und Roboto Slab nicht mehr laden (die Seite nutzt nur Fira Sans).
 *  3) Logo: feste Breite/Höhe (kein Layout-Sprung), Alt-Text.
 * Seit 1.54.1:
 *  4) Elementor-Seiten (Vorlage „Canvas“): Titel und Viewport nur noch einmal (Elementor gibt beide selbst aus),
 *     keine Block-Theme-Styles (Twenty Twenty-Five) und keine Emoji-Skripte.
 *  5) Elementor-CSS als externe Datei statt 120 KB im HTML (wird zwischengespeichert).
 *  6) readme.html / license.txt gesperrt (verraten die WordPress-Version).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_TEMPO_HT = 4; // bei Änderungen an den Regeln hochzählen

function kjo_tempo_regeln() {
	return array(
		'<IfModule mod_deflate.c>',
		'AddOutputFilterByType DEFLATE text/html text/plain text/css text/xml text/javascript application/javascript application/x-javascript application/json application/ld+json application/xml application/rss+xml image/svg+xml image/x-icon font/ttf font/otf',
		'</IfModule>',
		'<IfModule mod_expires.c>',
		'ExpiresActive On',
		'ExpiresByType text/css "access plus 1 year"',
		'ExpiresByType text/javascript "access plus 1 year"',
		'ExpiresByType application/javascript "access plus 1 year"',
		'ExpiresByType application/x-javascript "access plus 1 year"',
		'ExpiresByType font/woff2 "access plus 1 year"',
		'ExpiresByType font/woff "access plus 1 year"',
		'ExpiresByType application/font-woff2 "access plus 1 year"',
		'ExpiresByType image/webp "access plus 1 year"',
		'ExpiresByType image/avif "access plus 1 year"',
		'ExpiresByType image/jpeg "access plus 1 year"',
		'ExpiresByType image/png "access plus 1 year"',
		'ExpiresByType image/gif "access plus 1 year"',
		'ExpiresByType image/svg+xml "access plus 1 year"',
		'ExpiresByType image/x-icon "access plus 1 year"',
		'ExpiresByType image/vnd.microsoft.icon "access plus 1 year"',
		'ExpiresByType video/mp4 "access plus 3 months"',
		'ExpiresByType video/webm "access plus 3 months"',
		'</IfModule>',
		'<IfModule mod_authz_core.c>',
		'<FilesMatch "^(readme\.html|license\.txt|liesmich\.html|wp-config-sample\.php)$">',
		'Require all denied',
		'</FilesMatch>',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'<FilesMatch "\.(css|js|woff2?|ttf|otf)$">',
		'Header set Cache-Control "public, max-age=31536000"',
		'</FilesMatch>',
		'<FilesMatch "\.(jpe?g|png|gif|webp|avif|svg|ico|mp4|webm)$">',
		'Header set Cache-Control "public, max-age=31536000"',
		'</FilesMatch>',
		'</IfModule>',
	);
}

/** Regeln einmalig (bzw. nach Änderung) in die .htaccess im WordPress-Hauptordner schreiben. */
add_action(
	'admin_init',
	function () {
		if ( (int) get_option( 'kjo_tempo_ht' ) === KJO_TEMPO_HT || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$file = get_home_path() . '.htaccess';
		$ok   = ( file_exists( $file ) ? is_writable( $file ) : is_writable( dirname( $file ) ) ) && insert_with_markers( $file, 'DJ KOLJA ONE Ladezeit', kjo_tempo_regeln() );
		update_option( 'kjo_tempo_ht', $ok ? KJO_TEMPO_HT : -1, false );
	}
);
add_action(
	'admin_notices',
	function () {
		if ( -1 === (int) get_option( 'kjo_tempo_ht' ) && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-warning"><p><strong>DJ KOLJA ONE:</strong> Die Ladezeit-Regeln (Browser-Cache, Komprimierung) konnten nicht in die <code>.htaccess</code> geschrieben werden. Bitte Schreibrechte prüfen.</p></div>';
		}
	}
);

/* Roboto und Roboto Slab (Elementor-Standard) werden nirgends angezeigt – nicht laden. */
add_filter(
	'style_loader_tag',
	function ( $tag, $handle ) {
		return in_array( $handle, array( 'elementor-gf-local-roboto', 'elementor-gf-local-robotoslab', 'elementor-gf-roboto', 'elementor-gf-robotoslab' ), true ) ? '' : $tag;
	},
	10,
	2
);

/* Logo-Bilder: Maße, Alt-Text, das erste (Kopfzeile) ohne lazy und mit hoher Priorität. */
add_filter(
	'elementor/widget/render_content',
	function ( $content, $widget ) {
		static $erstes = true;
		if ( 'image' !== $widget->get_name() || false === strpos( $content, 'dj-kolja-one-logo' ) ) {
			return $content;
		}
		$masse = array(
			'dj-kolja-one-logo-ohne-claim'   => array( 1328, 170 ),
			'dj-kolja-one-logo-transparent' => array( 1328, 240 ),
		);
		return preg_replace_callback(
			'/<img\b[^>]*>/',
			function ( $m ) use ( $masse, &$erstes ) {
				$img = $m[0];
				foreach ( $masse as $name => $wh ) {
					if ( false === strpos( $img, $name ) ) {
						continue;
					}
					if ( false === strpos( $img, ' width=' ) ) {
						$img = str_replace( '<img ', '<img width="' . $wh[0] . '" height="' . $wh[1] . '" ', $img );
					}
					$img = preg_replace( '/\salt=""/', ' alt="DJ KOLJA ONE – zur Startseite"', $img );
					$img = preg_replace( '/\s(loading|fetchpriority)="[^"]*"/', '', $img );
					$img = preg_replace( '/\ssrc="[^"]*"/', ' src="' . esc_url( plugins_url( 'assets/' . $name . '.webp', __FILE__ ) ) . '"', $img ); // 600 px statt 1328 px
					$img = str_replace( '<img ', $erstes ? '<img fetchpriority="high" ' : '<img loading="lazy" ', $img );
					$erstes = false;
				}
				return $img;
			},
			$content
		);
	},
	10,
	2
);

/** Seite mit Elementor-Vorlage „Canvas“ (alle Seiten aus der Vorlage)? */
function kjo_tempo_canvas() {
	return is_singular() && 'elementor_canvas' === get_page_template_slug( get_queried_object_id() );
}
add_action(
	'wp',
	function () {
		if ( is_admin() || ! kjo_tempo_canvas() ) {
			return;
		}
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	}
);
/* Block-Themes hängen Titel und Viewport erst beim Laden der Vorlage an – deshalb direkt vor der Ausgabe entfernen.
 * Elementor Canvas gibt beide selbst aus. */
add_action(
	'wp_head',
	function () {
		if ( ! kjo_tempo_canvas() ) {
			return;
		}
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
		remove_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
	},
	-1000
);
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! kjo_tempo_canvas() ) {
			return;
		}
		foreach ( array( 'global-styles', 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'core-block-supports' ) as $h ) {
			wp_dequeue_style( $h );
		}
	},
	100
);

/* Elementor-CSS als externe Datei (einmalig umstellen, danach Elementor-Cache leeren). */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'kjo_tempo_css' ) || ! current_user_can( 'manage_options' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}
		update_option( 'elementor_css_print_method', 'external' );
		try {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		} catch ( \Throwable $e ) { // phpcs:ignore
		}
		update_option( 'kjo_tempo_css', 1, false );
	}
);
