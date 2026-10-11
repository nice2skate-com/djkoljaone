<?php
/**
 * Gerätebilder freistellen (seit 1.46.0) – ohne externe Dienste.
 *
 * Bilder von Geräten (equip_* oder im Feld „Bilder & Videos“ eines Geräts) mit weißem Hintergrund
 * bekommen einen transparenten Hintergrund. Das Original bleibt als „original_image“ erhalten.
 * Neue Bilder werden automatisch nach dem Hochladen bzw. Speichern des Geräts bearbeitet,
 * vorhandene einmalig im Hintergrund (WP-Cron, in kleinen Portionen).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_FREI_V   = 1;    // hochzählen = alle Gerätebilder noch einmal prüfen
const KJO_FREI_MAX = 1600; // längste Seite der freigestellten Datei

/**
 * Stellt ein Bild mit weißem Hintergrund frei. Gibt ein GD-Bild mit Alphakanal zurück
 * oder null, wenn der Rand nicht überwiegend weiß ist (z. B. Foto im Einsatz).
 */
function kjo_frei_gd( $file ) {
	$raw = @file_get_contents( $file ); // phpcs:ignore
	$src = $raw ? @imagecreatefromstring( $raw ) : false; // phpcs:ignore
	if ( ! $src ) {
		return null;
	}
	$w = imagesx( $src );
	$h = imagesy( $src );
	$s = min( 1, KJO_FREI_MAX / max( $w, $h ) );
	$W = max( 1, (int) round( $w * $s ) );
	$H = max( 1, (int) round( $h * $s ) );
	$im = imagecreatetruecolor( $W, $H );
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 255, 255, 255, 0 ) );
	imagealphablending( $im, true );
	imagecopyresampled( $im, $src, 0, 0, 0, 0, $W, $H, $w, $h ); // vorhandene Transparenz landet auf Weiß
	imagedestroy( $src );
	imagealphablending( $im, false );

	$n     = $W * $H;
	$light = new SplFixedArray( $n ); // 0 = Motiv, 1 = hell, 2 = Hintergrund, 3 = weicher Schatten
	$mn    = new SplFixedArray( $n );
	for ( $y = 0; $y < $H; $y++ ) {
		for ( $x = 0; $x < $W; $x++ ) {
			$c = imagecolorat( $im, $x, $y );
			$r = ( $c >> 16 ) & 255;
			$g = ( $c >> 8 ) & 255;
			$b = $c & 255;
			$lo = min( $r, $g, $b );
			$i  = $y * $W + $x;
			$mn[ $i ]    = $lo;
			$light[ $i ] = ( $lo >= 228 && max( $r, $g, $b ) - $lo <= 24 ) ? 1 : 0;
		}
	}
	// Rand muss überwiegend weiß sein.
	$edge = 0;
	$cnt  = 0;
	for ( $x = 0; $x < $W; $x++ ) {
		$edge += $light[ $x ] + $light[ ( $H - 1 ) * $W + $x ];
		$cnt  += 2;
	}
	for ( $y = 0; $y < $H; $y++ ) {
		$edge += $light[ $y * $W ] + $light[ $y * $W + $W - 1 ];
		$cnt  += 2;
	}
	if ( $edge / $cnt < 0.6 ) {
		imagedestroy( $im );
		return null;
	}
	// Helle Flächen zusammenfassen: am Rand oder große reinweiße Flächen (z. B. zwischen Traversenstreben) = Hintergrund.
	$seen = new SplFixedArray( $n );
	$min_area = (int) ( $n * 0.004 );
	$queue    = new SplFixedArray( $n );
	for ( $start = 0; $start < $n; $start++ ) {
		if ( 1 !== $light[ $start ] || $seen[ $start ] ) {
			continue;
		}
		$qh = 0;
		$qt = 0;
		$queue[ $qt++ ] = $start;
		$seen[ $start ] = 1;
		$border = false;
		$sum    = 0;
		while ( $qh < $qt ) {
			$i  = $queue[ $qh++ ];
			$x  = $i % $W;
			$y  = ( $i - $x ) / $W;
			$sum += $mn[ $i ];
			if ( 0 === $x || 0 === $y || $W - 1 === $x || $H - 1 === $y ) {
				$border = true;
			}
			foreach ( array( $x > 0 ? $i - 1 : -1, $x < $W - 1 ? $i + 1 : -1, $y > 0 ? $i - $W : -1, $y < $H - 1 ? $i + $W : -1 ) as $j ) {
				if ( $j >= 0 && 1 === $light[ $j ] && ! $seen[ $j ] ) {
					$seen[ $j ]     = 1;
					$queue[ $qt++ ] = $j;
				}
			}
		}
		if ( $border || ( $qt >= $min_area && $sum / $qt >= 246 ) ) {
			for ( $k = 0; $k < $qt; $k++ ) {
				$light[ $queue[ $k ] ] = 2;
			}
		}
	}
	// Weiche Schatten: vom Hintergrund aus in sanft dunkler werdende, ungesättigte Pixel weitergehen (keine harten Kanten überspringen).
	$qh = 0;
	$qt = 0;
	for ( $i = 0; $i < $n; $i++ ) {
		if ( 2 === $light[ $i ] ) {
			$queue[ $qt++ ] = $i;
		}
	}
	while ( $qh < $qt ) {
		$i = $queue[ $qh++ ];
		$x = $i % $W;
		$y = ( $i - $x ) / $W;
		foreach ( array( $x > 0 ? $i - 1 : -1, $x < $W - 1 ? $i + 1 : -1, $y > 0 ? $i - $W : -1, $y < $H - 1 ? $i + $W : -1 ) as $j ) {
			if ( $j < 0 || $light[ $j ] >= 2 || $mn[ $j ] < 185 || abs( $mn[ $j ] - $mn[ $i ] ) > 5 ) {
				continue;
			}
			$c = imagecolorat( $im, $j % $W, (int) ( $j / $W ) );
			if ( max( ( $c >> 16 ) & 255, ( $c >> 8 ) & 255, $c & 255 ) - $mn[ $j ] > 30 ) {
				continue;
			}
			$light[ $j ]    = 3;
			$queue[ $qt++ ] = $j;
		}
	}
	// Alphakanal schreiben; Kantenpixel neben dem Hintergrund werden je nach Helligkeit halbtransparent (ohne weißen Saum).
	$clear = imagecolorallocatealpha( $im, 255, 255, 255, 127 );
	for ( $y = 0; $y < $H; $y++ ) {
		for ( $x = 0; $x < $W; $x++ ) {
			$i = $y * $W + $x;
			if ( 2 === $light[ $i ] ) {
				imagesetpixel( $im, $x, $y, $clear );
				continue;
			}
			$nb = 3 === $light[ $i ] || ( $x > 0 && $light[ $i - 1 ] >= 2 ) || ( $x < $W - 1 && $light[ $i + 1 ] >= 2 ) || ( $y > 0 && $light[ $i - $W ] >= 2 ) || ( $y < $H - 1 && $light[ $i + $W ] >= 2 );
			if ( ! $nb || $mn[ $i ] < 140 ) {
				continue;
			}
			$c = imagecolorat( $im, $x, $y );
			$a = max( 3 === $light[ $i ] ? 0 : 0.15, min( 1, ( 255 - $mn[ $i ] ) / 115 ) ); // Deckkraft
			if ( $a < 0.02 ) {
				imagesetpixel( $im, $x, $y, $clear );
				continue;
			}
			$f = function ( $v ) use ( $a ) {
				return (int) max( 0, min( 255, round( ( $v - 255 * ( 1 - $a ) ) / $a ) ) );
			};
			imagesetpixel( $im, $x, $y, imagecolorallocatealpha( $im, $f( ( $c >> 16 ) & 255 ), $f( ( $c >> 8 ) & 255 ), $f( $c & 255 ), (int) round( 127 * ( 1 - $a ) ) ) );
		}
	}
	return $im;
}

/** Anhang freistellen: neue Datei (WebP oder PNG mit Transparenz) wird zur Hauptdatei, das Original bleibt erhalten. */
function kjo_frei_attachment( $id ) {
	if ( get_post_meta( $id, '_kjo_frei', true ) ) {
		return 'schon';
	}
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) || ! wp_attachment_is( 'image', $id ) || ! function_exists( 'imagecreatetruecolor' ) ) {
		update_post_meta( $id, '_kjo_frei', 'x' );
		return 'fehler';
	}
	wp_raise_memory_limit( 'image' );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore
	}
	$meta = wp_get_attachment_metadata( $id );
	$orig = is_array( $meta ) && ! empty( $meta['original_image'] ) ? trailingslashit( dirname( $file ) ) . $meta['original_image'] : $file;
	$im   = kjo_frei_gd( file_exists( $orig ) ? $orig : $file );
	if ( ! $im ) {
		update_post_meta( $id, '_kjo_frei', 'nein' ); // kein weißer Hintergrund
		return 'nein';
	}
	$webp = function_exists( 'imagewebp' );
	$base = preg_replace( '/-(scaled|frei)$/', '', pathinfo( $file, PATHINFO_FILENAME ) );
	$name = wp_unique_filename( dirname( $file ), $base . '-frei.' . ( $webp ? 'webp' : 'png' ) );
	$new  = trailingslashit( dirname( $file ) ) . $name;
	$ok   = $webp ? imagewebp( $im, $new, 90 ) : imagepng( $im, $new, 6 );
	imagedestroy( $im );
	if ( ! $ok || ! file_exists( $new ) ) {
		update_post_meta( $id, '_kjo_frei', 'x' );
		return 'fehler';
	}
	update_post_meta( $id, '_kjo_frei_alt', array( 'file' => get_post_meta( $id, '_wp_attached_file', true ), 'mime' => get_post_mime_type( $id ), 'meta' => $meta ) );
	update_attached_file( $id, $new );
	wp_update_post( array( 'ID' => $id, 'post_mime_type' => $webp ? 'image/webp' : 'image/png' ) );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$nm = wp_generate_attachment_metadata( $id, $new );
	if ( is_array( $nm ) ) {
		$nm['original_image'] = wp_basename( $orig );
		wp_update_attachment_metadata( $id, $nm );
	}
	update_post_meta( $id, '_kjo_frei', 'ja' );
	if ( function_exists( 'kjo_media_flush' ) ) {
		kjo_media_flush();
	}
	return 'ja';
}

/** Bild-IDs aller Geräte (über den Dateinamen gefunden oder ausgewählt). */
function kjo_frei_ids() {
	$ids = array();
	if ( ! function_exists( 'kjo_vl_auto_ids' ) ) {
		return $ids;
	}
	foreach ( get_posts( array( 'post_type' => 'kjo_geraet', 'post_status' => 'any', 'posts_per_page' => 500 ) ) as $g ) {
		$sel = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $g->ID, '_kjo_medien', true ) ) ) );
		foreach ( array_merge( $sel, kjo_vl_auto_ids( $g ) ) as $aid ) {
			if ( wp_attachment_is( 'image', $aid ) ) {
				$ids[ $aid ] = 1;
			}
		}
	}
	return array_keys( $ids );
}
function kjo_frei_offen() {
	return array_values( array_filter( kjo_frei_ids(), function ( $id ) { return ! get_post_meta( $id, '_kjo_frei', true ); } ) );
}

/* Hintergrund-Abarbeitung: je Lauf höchstens ~20 Sekunden, danach in einer Minute weiter. */
function kjo_frei_planen( $sek = 30 ) {
	if ( ! wp_next_scheduled( 'kjo_frei_lauf' ) ) {
		wp_schedule_single_event( time() + $sek, 'kjo_frei_lauf' );
	}
}
add_action(
	'kjo_frei_lauf',
	function () {
		$t = microtime( true );
		$rest = kjo_frei_offen();
		while ( $rest && microtime( true ) - $t < 20 ) {
			kjo_frei_attachment( array_shift( $rest ) );
		}
		if ( $rest ) {
			kjo_frei_planen( 60 );
		}
	}
);
// Einmalig je Stand: alle vorhandenen Gerätebilder prüfen.
add_action(
	'admin_init',
	function () {
		if ( (int) get_option( 'kjo_frei_v', 0 ) >= KJO_FREI_V || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		update_option( 'kjo_frei_v', KJO_FREI_V, false );
		kjo_frei_planen( 5 );
	}
);
// Neue Bilder: nach dem Hochladen (equip_…) oder Speichern eines Geräts.
add_action(
	'add_attachment',
	function ( $id ) {
		if ( function_exists( 'kjo_vl_att_key' ) && 0 === strpos( kjo_vl_att_key( $id ), 'equip_' ) ) {
			kjo_frei_planen( 20 );
		}
	}
);
add_action(
	'save_post_kjo_geraet',
	function () {
		kjo_frei_planen( 20 );
	}
);
// Hinweis in der Geräteliste, solange noch Bilder in Arbeit sind.
add_action(
	'admin_notices',
	function () {
		$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $s || 'edit-kjo_geraet' !== $s->id ) {
			return;
		}
		$n = count( kjo_frei_offen() );
		if ( $n ) {
			kjo_frei_planen( 5 );
			echo '<div class="notice notice-info"><p>Gerätebilder freistellen: noch ' . (int) $n . ' Bild' . ( 1 === $n ? '' : 'er' ) . ' in Arbeit. Das läuft im Hintergrund, die Seite muss nicht offen bleiben.</p></div>';
		}
	}
);
