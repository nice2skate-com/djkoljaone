<?php
/**
 * Ladezeit (seit 1.54.0) – Ergebnisse aus PageSpeed Insights:
 *  1) Browser-Cache und Komprimierung für CSS, JS, Bilder und Schriften per .htaccess
 *     (Strato lieferte diese Dateien ohne Cache-Angabe und unkomprimiert aus).
 *  2) Unbenutzte Elementor-Schriften Roboto und Roboto Slab nicht mehr laden (die Seite nutzt nur Fira Sans).
 *  3) Logo: feste Breite/Höhe (kein Layout-Sprung), Kopf-Logo sofort laden statt „lazy“, Alt-Text.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_TEMPO_HT = 2; // bei Änderungen an den Regeln hochzählen

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
		'ExpiresByType image/webp "access plus 3 months"',
		'ExpiresByType image/avif "access plus 3 months"',
		'ExpiresByType image/jpeg "access plus 3 months"',
		'ExpiresByType image/png "access plus 3 months"',
		'ExpiresByType image/gif "access plus 3 months"',
		'ExpiresByType image/svg+xml "access plus 3 months"',
		'ExpiresByType image/x-icon "access plus 3 months"',
		'ExpiresByType image/vnd.microsoft.icon "access plus 3 months"',
		'ExpiresByType video/mp4 "access plus 3 months"',
		'ExpiresByType video/webm "access plus 3 months"',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'<FilesMatch "\.(css|js|woff2?|ttf|otf)$">',
		'Header set Cache-Control "public, max-age=31536000"',
		'</FilesMatch>',
		'<FilesMatch "\.(jpe?g|png|gif|webp|avif|svg|ico|mp4|webm)$">',
		'Header set Cache-Control "public, max-age=7776000"',
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
					if ( $erstes ) {
						$img    = preg_replace( '/\sloading="lazy"/', ' fetchpriority="high"', $img );
						$erstes = false;
					}
				}
				return $img;
			},
			$content
		);
	},
	10,
	2
);
