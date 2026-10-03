<?php
/**
 * Plugin Name: DJ KOLJA ONE Plattenkiste
 * Description: Liefert deine Songs aus der Mediathek an das DJ-Pult auf „Meine Musik“ – mit Genre, BPM, Tonart (Camelot), Tempo-Regler, Sync, Automix, Video und Sterne-Bewertungen der Besucher.
 * Version:     1.12.2
 * Author:      DJ KOLJA ONE
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'KJO_VERSION', '1.12.2' );

/* ---------------------------------------------------------------
 * Hilfsfunktionen
 * ------------------------------------------------------------- */

function kjm_is_audio( $post ) {
	return $post && 'attachment' === $post->post_type && 0 === strpos( (string) $post->post_mime_type, 'audio/' );
}

/** Zwischenspeicher leeren (Elementor + gängige Cache-Plugins), damit neue Pult-Versionen sofort erscheinen. Liefert die Namen der geleerten Speicher. */
function kjm_purge_caches() {
	$done = array();
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		$done[] = 'Elementor';
	}
	delete_post_meta_by_key( '_elementor_element_cache' ); // Elementor „Element Caching“.
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
		$done[] = 'WP Rocket';
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
		$done[] = 'W3 Total Cache';
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
		$done[] = 'WP Super Cache';
	}
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
		$done[] = 'SG Optimizer';
	}
	if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
		autoptimizeCache::clearall();
		$done[] = 'Autoptimize';
	}
	if ( has_action( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
		$done[] = 'LiteSpeed';
	}
	return $done;
}
/* Nach jedem Update (auch per SFTP-Deploy) einmalig leeren, sobald jemand das Dashboard öffnet. */
add_action(
	'admin_init',
	function () {
		if ( KJO_VERSION !== get_option( 'kjm_cache_ver' ) && current_user_can( 'manage_options' ) ) {
			update_option( 'kjm_cache_ver', KJO_VERSION, false );
			kjm_purge_caches();
		}
	}
);

/** Tonart im Camelot-Format (1A–12B) oder ''. Akzeptiert z. B. „8a“ oder „08A“. */
function kjm_key_clean( $v ) {
	$v = strtoupper( trim( (string) $v ) );
	$v = preg_replace( '/^0+(?=\d)/', '', $v );
	return preg_match( '/^(1[0-2]|[1-9])[AB]$/', $v ) ? $v : '';
}
function kjm_key( $id ) {
	return kjm_key_clean( get_post_meta( $id, 'kjm_key', true ) );
}

/** Genre: eigenes Feld, sonst das Genre aus der MP3-Datei (ID3). */
function kjm_genre( $id ) {
	$g = trim( (string) get_post_meta( $id, 'kjm_genre', true ) );
	if ( '' === $g ) {
		$m = wp_get_attachment_metadata( $id );
		if ( ! empty( $m['genre'] ) ) {
			$g = trim( (string) $m['genre'] );
		}
	}
	return $g;
}

/** Video: eigenes Feld, sonst ein Video in der Mediathek mit gleichem Dateinamen (z. B. song.mp3 + song.mp4). */
function kjm_video( $id ) {
	$v = trim( (string) get_post_meta( $id, 'kjm_video', true ) );
	if ( '' !== $v ) {
		return $v;
	}
	$file = get_attached_file( $id );
	if ( ! $file ) {
		return '';
	}
	$base   = pathinfo( $file, PATHINFO_FILENAME );
	$videos = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'video',
			'post_status'    => 'inherit',
			'posts_per_page' => 10,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_wp_attached_file',
					'value'   => $base . '.',
					'compare' => 'LIKE',
				),
			),
		)
	);
	foreach ( $videos as $vid ) {
		if ( pathinfo( (string) get_attached_file( $vid->ID ), PATHINFO_FILENAME ) === $base ) {
			return (string) wp_get_attachment_url( $vid->ID );
		}
	}
	return '';
}

function kjm_rating( $id ) {
	$sum = (int) get_post_meta( $id, 'kjm_sum', true );
	$cnt = (int) get_post_meta( $id, 'kjm_count', true );
	return array(
		'avg'   => $cnt ? round( $sum / $cnt, 1 ) : 0,
		'count' => $cnt,
	);
}

function kjm_visible( $id ) {
	return '' !== kjm_genre( $id ) && ! get_post_meta( $id, 'kjm_hide', true );
}

/* Cover: Beitragsbild für MP3s erlauben – WordPress übernimmt dann das Cover aus der MP3 automatisch. */
add_action(
	'init',
	function () {
		add_post_type_support( 'attachment:audio', 'thumbnail' );
	}
);

/* ---------------------------------------------------------------
 * Zusätzliche Felder in der Mediathek (nur bei MP3/Audio)
 * ------------------------------------------------------------- */

add_filter(
	'attachment_fields_to_edit',
	function ( $fields, $post ) {
		if ( ! kjm_is_audio( $post ) ) {
			return $fields;
		}
		$id  = $post->ID;
		$m   = wp_get_attachment_metadata( $id );
		$id3 = ! empty( $m['genre'] ) ? $m['genre'] : '';
		$r   = kjm_rating( $id );

		$fields['kjm_genre'] = array(
			'label' => 'Plattenkiste: Genre',
			'input' => 'text',
			'value' => get_post_meta( $id, 'kjm_genre', true ),
			'helps' => $id3 ? 'Leer lassen = Genre aus der MP3-Datei („' . esc_html( $id3 ) . '“).' : 'z. B. House, Pop, Party, Latin. Ohne Genre erscheint der Song nicht im DJ-Pult.',
		);
		$fields['kjm_bpm']   = array(
			'label' => 'Plattenkiste: BPM',
			'input' => 'text',
			'value' => get_post_meta( $id, 'kjm_bpm', true ),
			'helps' => 'Tempo des Songs, z. B. 124 (optional).',
		);
		$fields['kjm_key']   = array(
			'label' => 'Plattenkiste: Tonart (Camelot)',
			'input' => 'text',
			'value' => get_post_meta( $id, 'kjm_key', true ),
			'helps' => 'Wird beim Hochladen automatisch erkannt, z. B. 8A (a-Moll) oder 5B (Es-Dur). Hier kannst du sie korrigieren.',
		);
		$fields['kjm_video'] = array(
			'label' => 'Plattenkiste: Video-URL',
			'input' => 'text',
			'value' => get_post_meta( $id, 'kjm_video', true ),
			'helps' => 'Optional. Leer lassen, wenn ein MP4 mit gleichem Dateinamen in der Mediathek liegt – es wird automatisch gefunden.',
		);
		$fields['kjm_color'] = array(
			'label' => 'Plattenkiste: Labelfarbe',
			'input' => 'text',
			'value' => get_post_meta( $id, 'kjm_color', true ),
			'helps' => 'Optional, z. B. #B29D75. Leer = automatisch.',
		);
		$fields['kjm_order'] = array(
			'label' => 'Plattenkiste: Reihenfolge',
			'input' => 'text',
			'value' => get_post_meta( $id, 'kjm_order', true ),
			'helps' => 'Optional. Kleinere Zahl = weiter vorne im Genre.',
		);
		$fields['kjm_hide']  = array(
			'label' => 'Plattenkiste',
			'input' => 'html',
			'html'  => '<label><input type="checkbox" name="attachments[' . $id . '][kjm_hide]" value="1" ' . checked( (bool) get_post_meta( $id, 'kjm_hide', true ), true, false ) . '> im DJ-Pult ausblenden</label>',
		);
		$fields['kjm_stats'] = array(
			'label' => 'Bewertung',
			'input' => 'html',
			'html'  => ( $r['count'] ? esc_html( number_format_i18n( $r['avg'], 1 ) . ' von 5 · ' . $r['count'] . ' Bewertung(en)' ) : 'Noch keine Bewertung' )
				. ( $r['count'] ? '<br><label><input type="checkbox" name="attachments[' . $id . '][kjm_reset]" value="1"> Bewertungen zurücksetzen</label>' : '' ),
		);
		return $fields;
	},
	10,
	2
);

add_filter(
	'attachment_fields_to_save',
	function ( $post, $att ) {
		$id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
		if ( ! $id || ! kjm_is_audio( get_post( $id ) ) || ! current_user_can( 'edit_post', $id ) ) {
			return $post;
		}
		if ( isset( $att['kjm_genre'] ) ) {
			update_post_meta( $id, 'kjm_genre', sanitize_text_field( $att['kjm_genre'] ) );
		}
		if ( isset( $att['kjm_bpm'] ) ) {
			$b = absint( $att['kjm_bpm'] );
			update_post_meta( $id, 'kjm_bpm', $b ? $b : '' );
		}
		if ( isset( $att['kjm_key'] ) ) {
			update_post_meta( $id, 'kjm_key', kjm_key_clean( $att['kjm_key'] ) );
			delete_post_meta( $id, 'kjm_pending' );
		}
		if ( isset( $att['kjm_video'] ) ) {
			update_post_meta( $id, 'kjm_video', esc_url_raw( trim( $att['kjm_video'] ) ) );
		}
		if ( isset( $att['kjm_color'] ) ) {
			$c = sanitize_hex_color( trim( $att['kjm_color'] ) );
			update_post_meta( $id, 'kjm_color', $c ? $c : '' );
		}
		if ( isset( $att['kjm_order'] ) ) {
			update_post_meta( $id, 'kjm_order', '' === trim( $att['kjm_order'] ) ? '' : intval( $att['kjm_order'] ) );
		}
		if ( isset( $att['kjm_genre'] ) ) { // Formular wurde abgeschickt → Checkbox auswerten.
			update_post_meta( $id, 'kjm_hide', empty( $att['kjm_hide'] ) ? '' : '1' );
		}
		if ( ! empty( $att['kjm_reset'] ) ) {
			delete_post_meta( $id, 'kjm_sum' );
			delete_post_meta( $id, 'kjm_count' );
		}
		return $post;
	},
	10,
	2
);

/* ---------------------------------------------------------------
 * REST-API für das DJ-Pult
 *   GET  /wp-json/kjm/v1/songs
 *   POST /wp-json/kjm/v1/rate   { id, stars }
 * ------------------------------------------------------------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjm/v1',
			'/songs',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => 'kjm_rest_songs',
			)
		);
		register_rest_route(
			'kjm/v1',
			'/rate',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'kjm_rest_rate',
				'args'                => array(
					'id'    => array(
						'required' => true,
						'type'     => 'integer',
					),
					'stars' => array(
						'required' => true,
						'type'     => 'integer',
						'minimum'  => 1,
						'maximum'  => 5,
					),
				),
			)
		);
	}
);

function kjm_rest_songs() {
	$posts = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'audio',
			'post_status'    => 'inherit',
			'posts_per_page' => 300,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$out = array();
	foreach ( $posts as $p ) {
		if ( ! kjm_visible( $p->ID ) ) {
			continue;
		}
		$m     = wp_get_attachment_metadata( $p->ID );
		$r     = kjm_rating( $p->ID );
		$order = get_post_meta( $p->ID, 'kjm_order', true );
		$out[] = array(
			'id'    => $p->ID,
			'titel' => html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ),
			'info'  => '' !== $p->post_excerpt ? wp_strip_all_tags( $p->post_excerpt ) : ( ! empty( $m['artist'] ) ? $m['artist'] : '' ),
			'genre' => kjm_genre( $p->ID ),
			'bpm'   => (int) get_post_meta( $p->ID, 'kjm_bpm', true ),
			'key'   => kjm_key( $p->ID ),
			'farbe' => (string) get_post_meta( $p->ID, 'kjm_color', true ),
			'audio' => kjm_stream_url( $p->ID, 'a' ),
			'video' => kjm_stream_url( $p->ID, 'v' ),
			'cover' => wp_make_link_relative( (string) get_the_post_thumbnail_url( $p->ID, 'medium_large' ) ),
			'dauer' => ! empty( $m['length_formatted'] ) ? $m['length_formatted'] : '',
			'avg'   => $r['avg'],
			'count' => $r['count'],
			'_o'    => '' === $order ? PHP_INT_MAX : (int) $order,
		);
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $a['_o'] === $b['_o'] ? 0 : ( $a['_o'] < $b['_o'] ? -1 : 1 );
		}
	);
	foreach ( $out as &$o ) {
		unset( $o['_o'] );
	}
	$res = rest_ensure_response( $out );
	$res->header( 'Cache-Control', 'no-store' );
	return $res;
}

function kjm_rest_rate( WP_REST_Request $req ) {
	$id    = (int) $req['id'];
	$stars = (int) $req['stars'];
	$post  = get_post( $id );
	if ( ! kjm_is_audio( $post ) || ! kjm_visible( $id ) ) {
		return new WP_Error( 'kjm_song', 'Unbekannter Song.', array( 'status' => 404 ) );
	}
	if ( $stars < 1 || $stars > 5 ) {
		return new WP_Error( 'kjm_stars', 'Bitte 1 bis 5 Sterne.', array( 'status' => 400 ) );
	}
	// Mehrfach-Bewertungen verhindern: nur ein gekürzter, gesalzener Hash (keine IP im Klartext), 30 Tage.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'kjm_v_' . substr( hash_hmac( 'sha256', $ip . '|' . $id, wp_salt( 'nonce' ) ), 0, 32 );
	if ( get_transient( $key ) ) {
		return new WP_Error( 'kjm_voted', 'Du hast diesen Song bereits bewertet.', array_merge( array( 'status' => 429 ), kjm_rating( $id ) ) );
	}
	set_transient( $key, 1, 30 * DAY_IN_SECONDS );
	$sum = (int) get_post_meta( $id, 'kjm_sum', true ) + $stars;
	$cnt = (int) get_post_meta( $id, 'kjm_count', true ) + 1;
	update_post_meta( $id, 'kjm_sum', $sum );
	update_post_meta( $id, 'kjm_count', $cnt );
	return rest_ensure_response(
		array(
			'id'    => $id,
			'avg'   => round( $sum / $cnt, 1 ),
			'count' => $cnt,
		)
	);
}

/* ---------------------------------------------------------------
 * Übersicht: Medien → Plattenkiste
 * ------------------------------------------------------------- */

add_action(
	'admin_menu',
	function () {
		add_media_page( 'Plattenkiste', 'Plattenkiste', 'upload_files', 'kjm-plattenkiste', 'kjm_admin_page' );
	}
);

function kjm_admin_page() {
	$posts = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'audio',
			'post_status'    => 'inherit',
			'posts_per_page' => 300,
			'no_found_rows'  => true,
		)
	);
	echo '<div class="wrap"><h1>Plattenkiste</h1>';
	if ( isset( $_GET['kjm_purge'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'kjm_purge' ) ) { // phpcs:ignore
		$d = kjm_purge_caches();
		echo '<div class="notice notice-success inline"><p>Zwischenspeicher geleert' . ( $d ? ': ' . esc_html( implode( ', ', $d ) ) : '' ) . '. Falls die Seite noch alt aussieht, zusätzlich den Cache deines Hosters leeren und die Seite neu laden.</p></div>';
	}
	echo '<p>So kommt ein Song ins DJ-Pult: MP3 unter <strong>Medien → Datei hinzufügen</strong> hochladen, Titel prüfen und ein <strong>Genre</strong> eintragen. Ein Video erscheint automatisch, wenn eine MP4 mit gleichem Dateinamen in der Mediathek liegt.</p>';
	kjo_update_box();
	echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'upload.php?page=kjm-plattenkiste&kjm_purge=1' ), 'kjm_purge' ) ) . '">Zwischenspeicher leeren</a> <span class="description">Wenn nach einem Update auf der Seite noch die alte Version erscheint.</span></p>';
	$n = kjm_protect_sync();
	if ( false === $n ) {
		echo '<div class="notice notice-warning inline"><p><strong>Download-Schutz nur teilweise aktiv:</strong> Die Adressen der Songs sind versteckt, aber die Sperrdatei <code>wp-content/uploads/.htaccess</code> konnte nicht geschrieben werden.</p></div>';
	} else {
		echo '<div class="notice notice-success inline"><p><strong>Download-Schutz aktiv:</strong> ' . (int) $n . ' Datei(en) sind für den direkten Aufruf gesperrt. Das Pult spielt sie über zeitlich begrenzte Links ab.</p></div>';
	}
	echo '<p>Die Songs laufen im Pult in der Qualität, in der du sie hochlädst. Der direkte Download der Dateien ist gesperrt.</p>';
	echo '<table class="widefat striped"><thead><tr><th>Titel</th><th>Genre</th><th>BPM</th><th>Tonart</th><th>Qualität</th><th>Video</th><th>Bewertung</th><th>Im Pult</th><th></th></tr></thead><tbody>';
	if ( ! $posts ) {
		echo '<tr><td colspan="9">Noch keine MP3s in der Mediathek.</td></tr>';
	}
	foreach ( $posts as $p ) {
		$r   = kjm_rating( $p->ID );
		$bpm = get_post_meta( $p->ID, 'kjm_bpm', true );
		$key = kjm_key( $p->ID );
		echo '<tr><td>' . esc_html( $p->post_title ) . '</td><td>' . esc_html( kjm_genre( $p->ID ) ?: '–' ) . '</td><td><span class="kjm-bpm-val">' . esc_html( $bpm ?: '–' ) . '</span></td><td><span class="kjm-key-val">' . esc_html( $key ?: '–' ) . '</span> <button type="button" class="button button-small kjm-bpm-btn" data-id="' . (int) $p->ID . '" data-has="' . ( $bpm && $key ? 1 : 0 ) . '" data-url="' . esc_url( wp_make_link_relative( (string) wp_get_attachment_url( $p->ID ) ) ) . '">erkennen</button></td><td>' . kjm_quality( $p->ID ) . '</td><td>' . ( kjm_video( $p->ID ) ? 'ja' : '–' ) . '</td><td>' . ( $r['count'] ? esc_html( number_format_i18n( $r['avg'], 1 ) . ' (' . $r['count'] . ')' ) : '–' ) . '</td><td>' . ( kjm_visible( $p->ID ) ? 'ja' : 'nein' ) . '</td><td><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">Bearbeiten</a></td></tr>';
	}
	echo '</tbody></table>';
	echo '<p><button type="button" class="button button-primary" id="kjm-bpm-all">BPM &amp; Tonart für alle Songs ohne Angabe erkennen</button> <span id="kjm-bpm-msg"></span></p>';
	echo '<p class="description">Neue MP3s werden beim Hochladen automatisch analysiert (BPM und Tonart im Camelot-Format, z. B. 8A). Die Erkennung läuft in deinem Browser und dauert pro Song ein paar Sekunden. Stimmt ein Wert nicht (z. B. halbes oder doppeltes Tempo, Dur/Moll verwechselt), trag ihn beim Song von Hand ein.</p>';
	echo '</div>';
	$nonce = wp_create_nonce( 'kjm_bpm' );
	echo '<script>' . file_get_contents( __DIR__ . '/assets/kjm-bpm.js' ) . file_get_contents( __DIR__ . '/assets/kjm-admin.js' ) . // phpcs:ignore
		'(function(){var AJ=' . wp_json_encode( admin_url( 'admin-ajax.php', 'relative' ) ) . ',N=' . wp_json_encode( $nonce ) . ',msg=document.getElementById("kjm-bpm-msg");
function run(btn){var row=btn.parentNode.parentNode,bc=row.querySelector(".kjm-bpm-val"),kc=row.querySelector(".kjm-key-val");btn.disabled=true;btn.textContent="läuft …";
 return kjmAdmin.analyse(btn.getAttribute("data-url")).then(function(res){if(!res.bpm&&!res.key)throw new Error("weder Takt noch Tonart klar erkannt");
  return kjmAdmin.save(AJ,N,btn.getAttribute("data-id"),res).then(function(d){if(d.bpm)bc.textContent=d.bpm;if(d.key)kc.textContent=d.key;btn.setAttribute("data-has",d.bpm&&d.key?"1":"0");btn.textContent="neu erkennen";btn.disabled=false;return true})})
 .catch(function(e){btn.textContent="erkennen";btn.disabled=false;msg.textContent="Fehler: "+e.message;return false})}
[].forEach.call(document.querySelectorAll(".kjm-bpm-btn"),function(b){if(b.getAttribute("data-has")==="1")b.textContent="neu erkennen";b.addEventListener("click",function(){msg.textContent="";run(b)})});
document.getElementById("kjm-bpm-all").addEventListener("click",function(){var l=[].filter.call(document.querySelectorAll(".kjm-bpm-btn"),function(b){return b.getAttribute("data-has")!=="1"}),i=0,all=this,bad=0;
 if(!l.length){msg.textContent="Alle Songs haben schon BPM und Tonart.";return}all.disabled=true;
 (function next(){if(i>=l.length){all.disabled=false;msg.textContent="Fertig: "+(l.length-bad)+" erkannt"+(bad?", "+bad+" ohne klares Ergebnis (bitte von Hand eintragen).":".");return}msg.textContent="Song "+(i+1)+" von "+l.length+" …";run(l[i++]).then(function(ok){if(!ok)bad++;next()})})()});
})();</script>';
}

/* Speichert BPM und/oder Tonart (vom Browser erkannt). Ohne Werte (fail=1): nur „offen“-Markierung löschen. */
add_action(
	'wp_ajax_kjm_save_bpm',
	function () {
		check_ajax_referer( 'kjm_bpm' );
		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! kjm_is_audio( get_post( $id ) ) ) {
			wp_send_json_error( null, 400 );
		}
		$out = array();
		$bpm = isset( $_POST['bpm'] ) ? (int) $_POST['bpm'] : 0;
		if ( $bpm >= 40 && $bpm <= 220 ) {
			update_post_meta( $id, 'kjm_bpm', $bpm );
			$out['bpm'] = $bpm;
		}
		$key = isset( $_POST['key'] ) ? kjm_key_clean( wp_unslash( $_POST['key'] ) ) : '';
		if ( '' !== $key ) {
			update_post_meta( $id, 'kjm_key', $key );
			$out['key'] = $key;
		}
		delete_post_meta( $id, 'kjm_pending' );
		wp_send_json_success( $out );
	}
);

/* Neue Audio-Uploads vormerken; ein Browser mit offener Mediathek analysiert sie automatisch. */
add_action(
	'add_attachment',
	function ( $id ) {
		if ( kjm_is_audio( get_post( $id ) ) ) {
			update_post_meta( $id, 'kjm_pending', '1' );
		}
	}
);
/* Einmalig nach dem Update: vorhandene Songs ohne Tonart zur automatischen Erkennung vormerken. */
add_action(
	'admin_init',
	function () {
		if ( '1' === get_option( 'kjm_key_mig' ) || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'audio',
				'post_status'    => 'inherit',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			if ( '' === kjm_key( $id ) ) {
				update_post_meta( $id, 'kjm_pending', '1' );
			}
		}
		update_option( 'kjm_key_mig', '1', false );
	}
);
add_action(
	'wp_ajax_kjm_pending',
	function () {
		check_ajax_referer( 'kjm_bpm' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( null, 403 );
		}
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'audio',
				'post_status'    => 'inherit',
				'meta_key'       => 'kjm_pending', // phpcs:ignore
				'meta_value'     => '1', // phpcs:ignore
				'posts_per_page' => 5,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$out = array();
		foreach ( $ids as $id ) {
			$out[] = array(
				'id'    => (int) $id,
				'url'   => wp_make_link_relative( (string) wp_get_attachment_url( $id ) ),
				'titel' => get_the_title( $id ),
			);
		}
		wp_send_json_success( $out );
	}
);
add_action(
	'admin_footer',
	function () {
		global $pagenow;
		if ( ! current_user_can( 'upload_files' ) || ! in_array( $pagenow, array( 'upload.php', 'media-new.php', 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( 'upload.php' === $pagenow && isset( $_GET['page'] ) && 'kjm-plattenkiste' === $_GET['page'] ) { // phpcs:ignore
			return; // Dort gibt es den Knopf „erkennen“.
		}
		echo '<script>' . file_get_contents( __DIR__ . '/assets/kjm-bpm.js' ) . file_get_contents( __DIR__ . '/assets/kjm-admin.js' ) . // phpcs:ignore
			'(function(){var AJ=' . wp_json_encode( admin_url( 'admin-ajax.php', 'relative' ) ) . ',N=' . wp_json_encode( wp_create_nonce( 'kjm_bpm' ) ) . ',busy=false,box=null,tried={};
function toast(t){if(!box){box=document.createElement("div");box.style.cssText="position:fixed;right:20px;bottom:20px;z-index:200000;background:#1d2327;color:#fff;padding:10px 14px;border-radius:4px;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.3)";document.body.appendChild(box)}box.textContent=t;box.style.display=t?"block":"none"}
function check(){if(busy)return;busy=true;fetch(AJ+"?action=kjm_pending&_wpnonce="+encodeURIComponent(N),{credentials:"same-origin"}).then(function(r){return r.json()}).then(function(j){
  var l=((j&&j.success&&j.data)||[]).filter(function(x){return !tried[x.id]});if(!l.length){busy=false;if(box)setTimeout(function(){toast("")},4000);return}
  var i=0;(function next(){if(i>=l.length){busy=false;toast("Plattenkiste: BPM und Tonart erkannt ✓");setTimeout(check,500);return}var s=l[i++];tried[s.id]=1;toast("Plattenkiste: erkenne BPM und Tonart – "+s.titel+" …");
   kjmAdmin.analyse(s.url).then(function(res){return kjmAdmin.save(AJ,N,s.id,res)}).catch(function(){return kjmAdmin.save(AJ,N,s.id,{})}).then(next,next)})()}).catch(function(){busy=false})}
check();setInterval(check,15000);
})();</script>';
	}
);

/* Musik-Pult: Das Plugin liefert immer die aktuelle Version des Pults aus,
 * egal welcher Stand im HTML-Widget der Seite „Meine Musik“ gespeichert ist. */
function kjm_deck_html() {
	$file = __DIR__ . '/assets/musikpult.html';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	$api = wp_make_link_relative( rest_url( 'kjm/v1' ) );
	return '<script>window.KJM_API=' . wp_json_encode( untrailingslashit( $api ) ) . ';</script>' . file_get_contents( $file ); // phpcs:ignore
}
add_filter(
	'elementor/widget/render_content',
	function ( $content, $widget ) {
		if ( is_object( $widget ) && method_exists( $widget, 'get_name' ) && 'html' === $widget->get_name() && false !== strpos( (string) $content, 'id="kjm"' ) ) {
			$deck = kjm_deck_html();
			if ( '' !== $deck ) {
				return $deck;
			}
		}
		return $content;
	},
	10,
	2
);
add_shortcode( 'kjo_musikpult', 'kjm_deck_html' );


/* ------------------------------------------------------------------
 * Bild-/Video-Platzhalter (seit 1.2.0)
 * Dateien in der Mediathek, die start_*, event_*, ueber_*, kolja_* oder
 * region_* heissen, werden automatisch dem gleichnamigen Platzhalter
 * zugeordnet. Bild = Hintergrund, Video = Player mit Start/Stop-Knopf.
 * ------------------------------------------------------------------ */
function kjo_media_map() {
	$map = get_transient( 'kjo_media_map' );
	if ( is_array( $map ) ) {
		return $map;
	}
	$map = array();
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => array( 'image', 'video' ),
			'posts_per_page' => 800,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	foreach ( $ids as $id ) {
		$file = get_post_meta( $id, '_wp_attached_file', true );
		if ( ! $file ) {
			continue;
		}
		$base = strtolower( wp_basename( $file ) );
		if ( ! preg_match( '/^((?:start|event|ueber|kolja|region)_[a-z0-9_]+?)(?:-scaled|-rotated|-\d+)*\.(jpe?g|png|webp|gif|mp4|m4v|webm|mov)$/', $base, $m ) ) {
			continue;
		}
		$kind = in_array( $m[2], array( 'mp4', 'm4v', 'webm', 'mov' ), true ) ? 'vid' : 'img';
		$url  = wp_get_attachment_url( $id );
		if ( $url ) {
			$map[ $m[1] ][ $kind ] = set_url_scheme( $url );
		}
	}
	set_transient( 'kjo_media_map', $map, DAY_IN_SECONDS );
	return $map;
}
function kjo_media_flush() {
	delete_transient( 'kjo_media_map' );
}
add_action( 'add_attachment', 'kjo_media_flush' );
add_action( 'edit_attachment', 'kjo_media_flush' );
add_action( 'delete_attachment', 'kjo_media_flush' );
add_filter(
	'wp_update_attachment_metadata',
	function ( $data ) {
		kjo_media_flush();
		return $data;
	}
);

add_action(
	'wp_footer',
	function () {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="kjo-media-css">' . KJO_MEDIA_CSS . '</style>';
		echo '<script id="kjo-media-js">window.KJO_MEDIA=' . wp_json_encode( (object) kjo_media_map() ) . ';' . KJO_MEDIA_JS . '</script>';
	},
	50
);


/* ------------------------------------------------------------------
 * Download-Schutz (seit 1.3.0)
 * 1) Das Pult bekommt nur zeitlich begrenzte, signierte Links.
 * 2) Der direkte Aufruf der Song-Dateien wird per .htaccess gesperrt
 *    (angemeldete Admins können sie in der Mediathek weiter anhören).
 * ------------------------------------------------------------------ */
function kjm_quality( $id ) {
	$m = wp_get_attachment_metadata( $id );
	if ( empty( $m['bitrate'] ) ) {
		return '–';
	}
	$k = (int) round( $m['bitrate'] / 1000 );
	return $k . ' kbps';
}

function kjm_url_to_path( $url ) {
	$up = wp_get_upload_dir();
	$a  = preg_replace( '#^https?:#i', '', $up['baseurl'] );
	$b  = preg_replace( '#^https?:#i', '', (string) $url );
	if ( '' === $b || 0 !== strpos( $b, $a . '/' ) ) {
		return '';
	}
	$rel = ltrim( substr( $b, strlen( $a ) ), '/' );
	if ( false !== strpos( $rel, '..' ) ) {
		return '';
	}
	return trailingslashit( $up['basedir'] ) . $rel;
}

function kjm_stream_path( $id, $kind ) {
	if ( 'v' === $kind ) {
		return kjm_url_to_path( kjm_video( $id ) );
	}
	return (string) get_attached_file( $id );
}

function kjm_sig( $id, $kind, $exp ) {
	return substr( hash_hmac( 'sha256', $id . '|' . $kind . '|' . $exp, wp_salt( 'auth' ) ), 0, 32 );
}

function kjm_stream_url( $id, $kind ) {
	if ( 'v' === $kind ) {
		$v = kjm_video( $id );
		if ( '' === $v ) {
			return '';
		}
		if ( '' === kjm_url_to_path( $v ) ) {
			return $v; // externes Video: unverändert.
		}
	}
	$exp = (int) ( ceil( ( time() + 3 * HOUR_IN_SECONDS ) / HOUR_IN_SECONDS ) * HOUR_IN_SECONDS );
	return add_query_arg(
		array(
			'kjm_stream' => (int) $id,
			'k'          => $kind,
			'e'          => $exp,
			'sig'        => kjm_sig( (int) $id, $kind, $exp ),
		),
		wp_make_link_relative( home_url( '/' ) )
	);
}

add_action(
	'init',
	function () {
		if ( ! isset( $_GET['kjm_stream'] ) ) { // phpcs:ignore
			return;
		}
		$id   = (int) $_GET['kjm_stream']; // phpcs:ignore
		$kind = isset( $_GET['k'] ) && 'v' === $_GET['k'] ? 'v' : 'a'; // phpcs:ignore
		$exp  = isset( $_GET['e'] ) ? (int) $_GET['e'] : 0; // phpcs:ignore
		$sig  = isset( $_GET['sig'] ) ? (string) wp_unslash( $_GET['sig'] ) : ( isset( $_GET['s'] ) ? (string) wp_unslash( $_GET['s'] ) : '' ); // phpcs:ignore
		$deny = function ( $code ) {
			status_header( $code );
			nocache_headers();
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 403 === $code ? 'Nicht erlaubt.' : 'Nicht gefunden.';
			exit;
		};
		if ( $exp < time() || ! hash_equals( kjm_sig( $id, $kind, $exp ), $sig ) ) {
			$deny( 403 );
		}
		// Direkter Aufruf in der Adresszeile / fremde Seiten.
		// Nur echte Seitenaufrufe sperren (Adresszeile). Audio-/Video-Player senden nie "navigate".
		$mode = isset( $_SERVER['HTTP_SEC_FETCH_MODE'] ) ? strtolower( (string) $_SERVER['HTTP_SEC_FETCH_MODE'] ) : '';
		if ( 'navigate' === $mode ) {
			$deny( 403 );
		}
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';
		if ( '' !== $ref ) {
			$rh = strtolower( (string) wp_parse_url( $ref, PHP_URL_HOST ) );
			$hh = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
			$qh = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', (string) $_SERVER['HTTP_HOST'] ) ) : '';
			$rh = preg_replace( '/^www\./', '', $rh );
			if ( $rh !== preg_replace( '/^www\./', '', $hh ) && $rh !== preg_replace( '/^www\./', '', $qh ) ) {
				$deny( 403 );
			}
		}
		$post = get_post( $id );
		if ( ! kjm_is_audio( $post ) || ! kjm_visible( $id ) ) {
			$deny( 404 );
		}
		$path = kjm_stream_path( $id, $kind );
		if ( '' === $path || ! is_file( $path ) || ! is_readable( $path ) ) {
			$deny( 404 );
		}
		$size  = filesize( $path );
		$start = 0;
		$end   = $size - 1;
		$code  = 200;
		if ( isset( $_SERVER['HTTP_RANGE'] ) && preg_match( '/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $m ) ) {
			if ( '' === $m[1] && '' !== $m[2] ) {
				$start = max( 0, $size - (int) $m[2] );
			} else {
				$start = (int) $m[1];
				if ( '' !== $m[2] ) {
					$end = min( $end, (int) $m[2] );
				}
			}
			if ( $start > $end || $start >= $size ) {
				status_header( 416 );
				header( 'Content-Range: bytes */' . $size );
				exit;
			}
			$code = 206;
		}
		$type = wp_check_filetype( $path );
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore
		}
		status_header( $code );
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Accept-Ranges: bytes' );
		header( 'Content-Length: ' . ( $end - $start + 1 ) );
		if ( 206 === $code ) {
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $size );
		}
		header( 'Content-Disposition: inline' );
		header( 'Cache-Control: private, max-age=3600' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );
		if ( 'HEAD' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			exit;
		}
		$fh = fopen( $path, 'rb' ); // phpcs:ignore
		fseek( $fh, $start );
		$left = $end - $start + 1;
		while ( $left > 0 && ! feof( $fh ) && ! connection_aborted() ) {
			$chunk = fread( $fh, (int) min( 262144, $left ) ); // phpcs:ignore
			if ( false === $chunk ) {
				break;
			}
			echo $chunk; // phpcs:ignore
			$left -= strlen( $chunk );
			flush();
		}
		fclose( $fh ); // phpcs:ignore
		exit;
	},
	1
);

/** Sperrliste in wp-content/uploads/.htaccess schreiben. Rückgabe: Anzahl gesperrter Dateien oder false. */
function kjm_protect_sync( $remove = false ) {
	$up    = wp_get_upload_dir();
	$base  = trailingslashit( $up['basedir'] );
	$files = array();
	if ( ! $remove ) {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'audio',
				'post_status'    => 'inherit',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			foreach ( array( (string) get_attached_file( $id ), kjm_url_to_path( kjm_video( $id ) ) ) as $f ) {
				if ( '' !== $f && 0 === strpos( $f, $base ) ) {
					$files[ substr( $f, strlen( $base ) ) ] = 1;
				}
			}
		}
	}
	$lines = array();
	if ( $files ) {
		$lines[] = '<IfModule mod_rewrite.c>';
		$lines[] = 'RewriteEngine On';
		foreach ( array_keys( $files ) as $rel ) {
			$lines[] = 'RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_ [NC]';
			$lines[] = 'RewriteRule ^' . str_replace( ' ', '\\ ', preg_quote( $rel, '#' ) ) . '$ - [F,L]';
		}
		$lines[] = '</IfModule>';
	}
	$sum = md5( implode( "\n", $lines ) );
	if ( ! $remove && get_option( 'kjm_protect_sum' ) === $sum && file_exists( $base . '.htaccess' ) ) {
		return count( $files );
	}
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	if ( ! is_dir( $base ) || ! insert_with_markers( $base . '.htaccess', 'DJ KOLJA ONE Plattenkiste', $lines ) ) {
		return false;
	}
	update_option( 'kjm_protect_sum', $sum, false );
	return count( $files );
}
function kjm_protect_later() {
	if ( ! has_action( 'shutdown', 'kjm_protect_run' ) ) {
		add_action( 'shutdown', 'kjm_protect_run' );
	}
}
function kjm_protect_run() {
	kjm_protect_sync();
}
add_action( 'add_attachment', 'kjm_protect_later' );
add_action( 'edit_attachment', 'kjm_protect_later' );
add_action( 'deleted_post', 'kjm_protect_later' );
add_action(
	'admin_init',
	function () {
		if ( '1.5.1' !== get_option( 'kjm_ver' ) ) {
			update_option( 'kjm_ver', '1.5.1', false );
			kjm_protect_later();
		}
	}
);
register_activation_hook( __FILE__, 'kjm_protect_run' );
register_deactivation_hook(
	__FILE__,
	function () {
		kjm_protect_sync( true );
		delete_option( 'kjm_protect_sum' );
	}
);


/* ------------------------------------------------------------------
 * Icons, Teilen-Vorschau und Suchmaschinen-Angaben (seit 1.6.0)
 * ------------------------------------------------------------------ */
function kjo_asset( $file ) {
	return plugins_url( 'assets/' . $file, __FILE__ );
}

function kjo_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
}

/** Kurzbeschreibung je Seite (für Google und für die Vorschau beim Teilen). */
function kjo_description() {
	$map = array(
		'start'          => 'DJ KOLJA ONE – Premium DJ mit professioneller Moderation für Hochzeiten, Geburtstage, Firmenfeiern und Events in Oberschwaben, Ulm und dem Allgäu. Jetzt Wunschtermin prüfen.',
		'hochzeits-dj'   => 'Hochzeits-DJ für Memmingen, Ulm und das Allgäu: persönliche Musikplanung, professionelle Moderation und eine volle Tanzfläche – vom ersten Song bis zum letzten.',
		'geburtstags-dj' => 'Geburtstags-DJ für runde Geburtstage und private Feiern: Musik für alle Generationen, geprüfte Technik und Moderation auf Wunsch.',
		'firmenfeier-dj' => 'DJ für Firmenfeiern, Weihnachtsfeiern, Sommerfeste und Galas: Musik und Moderation, abgestimmt auf euren Ablauf und eure Gäste.',
		'event-dj'       => 'Event-DJ für Stadtfeste, Vereinsfeiern, Open Airs und Silvesterpartys – mit skalierbarer Technik und professioneller Moderation.',
		'meine-musik'    => 'Legt selbst auf: Am interaktiven DJ-Pult könnt ihr eigene Songs von DJ KOLJA ONE anhören, mixen und mit Sternen bewerten.',
		'ueber-mich'     => 'Über DJ KOLJA ONE: seit über 10 Jahren DJ und Moderator aus Fellheim bei Memmingen. Jedes Event findet nur einmal statt.',
		'faq'            => 'Häufige Fragen zu Buchung, Kosten, Musikwünschen, Technik und Ablauf – kurz und ehrlich beantwortet von DJ KOLJA ONE.',
		'einsatzgebiete' => 'Mobiler DJ für Memmingen, Ulm, Biberach, Ravensburg, Kempten, Füssen, Kaufbeuren, Landsberg und Umgebung.',
		'kontakt'        => 'Wunschtermin unverbindlich anfragen: per Formular, Telefon oder WhatsApp. Ich melde mich innerhalb von 24 Stunden.',
	);
	if ( is_front_page() ) {
		return $map['start'];
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post && isset( $map[ $post->post_name ] ) ) {
			return $map[ $post->post_name ];
		}
		if ( $post && 0 === strpos( $post->post_name, 'dj-' ) ) {
			return get_the_title( $post ) . ' – DJ KOLJA ONE für Hochzeit, Geburtstag, Firmenfeier und Event in der Region. Persönliche Planung, professionelle Moderation, geprüfte Technik.';
		}
		if ( $post && has_excerpt( $post ) ) {
			return wp_strip_all_tags( get_the_excerpt( $post ) );
		}
	}
	return 'DJ KOLJA ONE – Premium DJ für Hochzeiten, Firmenevents und besondere Feste. Jedes Event findet nur einmal statt.';
}

// Eigene Icons statt des WordPress-Standards.
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'wp_site_icon', 99 );
	}
);
add_action(
	'do_faviconico',
	function () {
		wp_safe_redirect( kjo_asset( 'favicon.ico' ), 301 );
		exit;
	},
	1
);

add_action(
	'wp_head',
	function () {
		echo "\n" . '<link rel="icon" href="' . esc_url( kjo_asset( 'favicon.ico' ) ) . '" sizes="48x48">' . "\n";
		echo '<link rel="icon" href="' . esc_url( kjo_asset( 'icon.svg' ) ) . '" type="image/svg+xml">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( kjo_asset( 'apple-touch-icon.png' ) ) . '">' . "\n";
		echo '<link rel="manifest" href="' . esc_url( kjo_asset( 'site.webmanifest' ) ) . '">' . "\n";
		echo '<meta name="theme-color" content="#0F0C07">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="DJ KOLJA ONE">' . "\n";
		echo '<meta name="application-name" content="DJ KOLJA ONE">' . "\n";
		echo '<meta name="format-detection" content="telephone=no">' . "\n";
		if ( kjo_has_seo_plugin() || is_404() || is_search() ) {
			return;
		}
		$desc  = kjo_description();
		$title = wp_get_document_title();
		$url   = is_front_page() ? home_url( '/' ) : ( is_singular() ? get_permalink() : home_url( '/' ) );
		$img   = kjo_asset( 'teilen.jpg' );
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:locale" content="de_DE">' . "\n";
		echo '<meta property="og:site_name" content="DJ KOLJA ONE">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		echo '<meta property="og:image:width" content="1200">' . "\n";
		echo '<meta property="og:image:height" content="630">' . "\n";
		echo '<meta property="og:image:alt" content="DJ KOLJA ONE – Jedes Event findet nur einmal statt.">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		if ( is_front_page() ) {
			$ld = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'EntertainmentBusiness',
				'name'        => 'DJ KOLJA ONE',
				'description' => $desc,
				'url'         => home_url( '/' ),
				'image'       => $img,
				'logo'        => kjo_asset( 'icon-512.png' ),
				'telephone'   => '+491727273707',
				'slogan'      => 'Jedes Event findet nur einmal statt.',
				'address'     => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => 'Fellheim',
					'addressRegion'   => 'Bayern',
					'addressCountry'  => 'DE',
				),
				'areaServed'  => array( 'Memmingen', 'Ulm', 'Biberach', 'Ravensburg', 'Kempten', 'Füssen', 'Kaufbeuren', 'Landsberg am Lech', 'Allgäu', 'Oberschwaben' ),
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	},
	2
);
// Icon auch im WordPress-Adminbereich und auf der Login-Seite.
foreach ( array( 'admin_head', 'login_head' ) as $kjo_hook ) {
	add_action(
		$kjo_hook,
		function () {
			echo '<link rel="icon" href="' . esc_url( kjo_asset( 'icon.svg' ) ) . '" type="image/svg+xml"><link rel="apple-touch-icon" href="' . esc_url( kjo_asset( 'apple-touch-icon.png' ) ) . '">';
		}
	);
}

/* ------------------------------------------------------------------
 * Eigener Update-Kanal (seit 1.8.0)
 * Das Plugin liest eine kleine Beschreibungsdatei (JSON) von der unter
 * Medien → Plattenkiste hinterlegten Adresse. Steht dort eine neuere
 * Version, bietet WordPress das Update wie bei jedem anderen Plugin an.
 * ------------------------------------------------------------------ */
function kjo_update_url() {
	$u = trim( (string) get_option( 'kjo_update_url', '' ) );
	if ( '' === $u ) {
		return '';
	}
	$host = (string) wp_parse_url( $u, PHP_URL_HOST );
	$ok   = 0 === strpos( $u, 'https://' ) || in_array( $host, array( 'localhost', '127.0.0.1' ), true );
	return $ok ? $u : '';
}

/** Beschreibungsdatei holen (6 Stunden zwischengespeichert). */
function kjo_update_info( $force = false ) {
	$url = kjo_update_url();
	if ( '' === $url ) {
		return null;
	}
	$key = 'kjo_upd_' . md5( $url );
	if ( ! $force ) {
		$c = get_site_transient( $key );
		if ( is_array( $c ) ) {
			return empty( $c['version'] ) ? null : $c;
		}
	}
	$res  = wp_remote_get( add_query_arg( 't', time(), $url ), array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/json' ) ) );
	$info = array( 'error' => '' );
	if ( is_wp_error( $res ) ) {
		$info['error'] = $res->get_error_message();
	} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		$info['error'] = 'Antwort ' . (int) wp_remote_retrieve_response_code( $res );
	} else {
		$j = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $j ) || empty( $j['version'] ) || empty( $j['download_url'] ) ) {
			$info['error'] = 'Beschreibungsdatei unvollständig';
		} else {
			$dl = (string) $j['download_url'];
			// Relative Angabe (nur Dateiname) → gleicher Ordner wie die Beschreibungsdatei.
			if ( ! preg_match( '#^https?://#i', $dl ) ) {
				$dl = trailingslashit( preg_replace( '#/[^/]*$#', '', strtok( $url, '?' ) ) ) . ltrim( $dl, '/' );
			}
			if ( wp_parse_url( $dl, PHP_URL_HOST ) !== wp_parse_url( $url, PHP_URL_HOST ) && empty( $j['allow_other_host'] ) ) {
				$info['error'] = 'Download liegt auf einem anderen Server als die Beschreibungsdatei';
			} elseif ( ! preg_match( '/^\d+(\.\d+){1,3}$/', (string) $j['version'] ) ) {
				$info['error'] = 'Versionsnummer ungültig';
			} else {
				$info = array(
					'error'        => '',
					'version'      => (string) $j['version'],
					'download_url' => esc_url_raw( $dl ),
					'sha256'       => isset( $j['sha256'] ) ? strtolower( preg_replace( '/[^a-f0-9]/i', '', (string) $j['sha256'] ) ) : '',
					'requires'     => isset( $j['requires'] ) ? (string) $j['requires'] : '6.0',
					'tested'       => isset( $j['tested'] ) ? (string) $j['tested'] : '',
					'requires_php' => isset( $j['requires_php'] ) ? (string) $j['requires_php'] : '7.4',
					'changelog'    => isset( $j['changelog'] ) ? wp_kses_post( (string) $j['changelog'] ) : '',
				);
			}
		}
	}
	$info['checked'] = time();
	set_site_transient( $key, $info, empty( $info['version'] ) ? HOUR_IN_SECONDS : 6 * HOUR_IN_SECONDS );
	update_option( 'kjo_update_last', $info, false );
	return empty( $info['version'] ) ? null : $info;
}

add_filter(
	'pre_set_site_transient_update_plugins',
	function ( $tr ) {
		if ( ! is_object( $tr ) ) {
			return $tr;
		}
		$info = kjo_update_info();
		$base = plugin_basename( __FILE__ );
		$item = (object) array(
			'id'           => 'kjo/' . $base,
			'slug'         => dirname( $base ),
			'plugin'       => $base,
			'new_version'  => $info ? $info['version'] : KJO_VERSION,
			'url'          => home_url( '/' ),
			'package'      => $info ? $info['download_url'] : '',
			'tested'       => $info ? $info['tested'] : '',
			'requires'     => $info ? $info['requires'] : '',
			'requires_php' => $info ? $info['requires_php'] : '',
			'icons'        => array( '1x' => kjo_asset( 'icon-192.png' ), 'svg' => kjo_asset( 'icon.svg' ) ),
		);
		if ( $info && version_compare( $info['version'], KJO_VERSION, '>' ) ) {
			$tr->response[ $base ] = $item;
			unset( $tr->no_update[ $base ] );
		} else {
			$tr->no_update[ $base ] = $item;
			unset( $tr->response[ $base ] );
		}
		return $tr;
	}
);

// Fenster „Details ansehen“.
add_filter(
	'plugins_api',
	function ( $res, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( plugin_basename( __FILE__ ) ) !== $args->slug ) {
			return $res;
		}
		$info = kjo_update_info();
		return (object) array(
			'name'          => 'DJ KOLJA ONE Plattenkiste',
			'slug'          => $args->slug,
			'version'       => $info ? $info['version'] : KJO_VERSION,
			'author'        => 'DJ KOLJA ONE',
			'homepage'      => home_url( '/' ),
			'requires'      => $info ? $info['requires'] : '6.0',
			'tested'        => $info ? $info['tested'] : '',
			'requires_php'  => $info ? $info['requires_php'] : '7.4',
			'download_link' => $info ? $info['download_url'] : '',
			'sections'      => array(
				'changelog' => $info && $info['changelog'] ? $info['changelog'] : '<p>Keine Angaben.</p>',
			),
		);
	},
	10,
	3
);

// Prüfsumme der heruntergeladenen Datei kontrollieren, wenn die Beschreibungsdatei eine nennt.
add_filter(
	'upgrader_pre_download',
	function ( $reply, $package ) {
		$info = kjo_update_info();
		if ( ! $info || $package !== $info['download_url'] || '' === $info['sha256'] ) {
			return $reply;
		}
		$tmp = download_url( $package );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		if ( ! hash_equals( $info['sha256'], hash_file( 'sha256', $tmp ) ) ) {
			wp_delete_file( $tmp );
			return new WP_Error( 'kjo_checksum', 'Die heruntergeladene Datei stimmt nicht mit der Prüfsumme überein. Update abgebrochen.' );
		}
		return $tmp;
	},
	10,
	2
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'upload.php?page=kjm-plattenkiste#kjo-update' ) ) . '">Updates</a>';
		return $links;
	}
);

function kjo_update_box() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['kjo_update_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_update_nonce'] ) ), 'kjo_update' ) ) {
		if ( isset( $_POST['kjo_update_url'] ) ) {
			update_option( 'kjo_update_url', esc_url_raw( trim( (string) wp_unslash( $_POST['kjo_update_url'] ) ) ), false );
		}
		kjo_update_info( true );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		$msg = 'Geprüft.';
	}
	$raw  = (string) get_option( 'kjo_update_url', '' );
	$url  = kjo_update_url();
	$last = get_option( 'kjo_update_last' );
	echo '<div id="kjo-update" class="card" style="max-width:none;margin:14px 0"><h2 style="margin-top:0">Plugin-Updates</h2>';
	echo '<p>Installiert: <strong>Version ' . esc_html( KJO_VERSION ) . '</strong>. ';
	if ( '' === $raw ) {
		echo 'Es ist noch keine Update-Adresse hinterlegt – Updates kommen weiter per ZIP-Upload.';
	} elseif ( '' === $url ) {
		echo '<span style="color:#b32d2e">Die Update-Adresse muss mit https:// beginnen.</span>';
	} elseif ( is_array( $last ) && ! empty( $last['error'] ) ) {
		echo '<span style="color:#b32d2e">Letzte Prüfung fehlgeschlagen: ' . esc_html( $last['error'] ) . '</span>';
	} elseif ( is_array( $last ) && ! empty( $last['version'] ) ) {
		if ( version_compare( $last['version'], KJO_VERSION, '>' ) ) {
			echo '<strong>Version ' . esc_html( $last['version'] ) . ' ist verfügbar.</strong> <a class="button button-primary" href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Zur Plugin-Liste und aktualisieren</a>';
		} else {
			echo 'Du hast die aktuelle Version.';
		}
		echo ' <span class="description">(geprüft ' . esc_html( wp_date( 'd.m.Y H:i', (int) $last['checked'] ) ) . ')</span>';
	}
	echo '</p><form method="post">';
	wp_nonce_field( 'kjo_update', 'kjo_update_nonce' );
	echo '<label for="kjo_update_url"><strong>Update-Adresse</strong></label><br><input type="url" class="regular-text code" style="width:100%;max-width:720px" id="kjo_update_url" name="kjo_update_url" value="' . esc_attr( $raw ) . '" placeholder="https://…/plattenkiste.json"> ';
	echo '<button class="button">Speichern und jetzt prüfen</button> ' . esc_html( $msg );
	echo '</form></div>';
}

define( 'KJO_MEDIA_CSS', <<<'KJOCSS'
.kjo-vid{position:relative!important;overflow:hidden!important}.kjo-vid>video{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;object-position:center;cursor:pointer;background:#1A1712;z-index:1}.kjo-vid>.kjo-pb{position:absolute;left:5%;bottom:4%;width:clamp(32px,20%,54px);aspect-ratio:1;height:auto;min-height:0;border-radius:50%!important;background:#161310!important;border:1.5px solid #B29D75!important;color:#B29D75!important;box-shadow:0 0 0 5px rgba(178,157,117,.16),0 8px 20px rgba(0,0,0,.55);padding:0!important;margin:0;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:2;transition:background .2s,color .2s,box-shadow .2s;-webkit-appearance:none;appearance:none}.kjo-vid>.kjo-pb:hover,.kjo-vid>.kjo-pb:focus-visible{box-shadow:0 0 0 7px rgba(178,157,117,.28),0 8px 20px rgba(0,0,0,.55);outline:none}.kjo-vid>.kjo-pb svg{width:46%;height:46%;display:block;fill:currentColor}.kjo-vid>.kjo-pb .kjo-i2{display:none}.kjo-vid.kjo-on>.kjo-pb{background:#B29D75!important;color:#0F0C07!important}.kjo-vid.kjo-on>.kjo-pb .kjo-i1{display:none}.kjo-vid.kjo-on>.kjo-pb .kjo-i2{display:block}
KJOCSS
);
define( 'KJO_MEDIA_JS', <<<'KJOJS'
(function(){
var M=window.KJO_MEDIA||{};
function one(el){
  if(el.getAttribute("data-kjo"))return;
  var m=(String(el.className).match(/kjo-m-([a-z0-9_]+)/i)||[])[1]; if(!m)return;
  el.setAttribute("data-kjo","1");
  var e=M[m.toLowerCase()]||{};
  if(e.img){el.style.backgroundImage='url("'+e.img+'")';el.style.backgroundSize="cover";el.style.backgroundPosition="center";}
  if(!e.vid)return;
  el.classList.add("kjo-vid");
  var v=document.createElement("video");
  v.setAttribute("playsinline","");v.playsInline=true;v.preload="metadata";
  if(e.img){v.poster=e.img;v.src=e.vid;}else{v.src=e.vid+"#t=0.1";}
  var b=document.createElement("button");b.type="button";b.className="kjo-pb";b.setAttribute("aria-label","Video abspielen");
  b.innerHTML='<svg class="kjo-i1" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg><svg class="kjo-i2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.5h4.2v15H6zM13.8 4.5H18v15h-4.2z"/></svg>';
  el.appendChild(v);el.appendChild(b);
  function set(on){el.classList.toggle("kjo-on",on);b.setAttribute("aria-label",on?"Video anhalten":"Video abspielen");}
  function tog(ev){ev.preventDefault();ev.stopPropagation();
    if(v.paused){var o=document.querySelectorAll(".kjo-vid>video");for(var i=0;i<o.length;i++){if(o[i]!==v)o[i].pause();}
      var p=v.play();if(p&&p.catch)p.catch(function(){});}
    else v.pause();}
  b.addEventListener("click",tog);v.addEventListener("click",tog);
  v.addEventListener("play",function(){set(true);});
  function rst(){set(false);if(e.img){v.load();}else{try{v.currentTime=0.1;}catch(x){}}}
  v.addEventListener("pause",rst);
  v.addEventListener("ended",rst);
}
function init(){var l=document.querySelectorAll('[class*="kjo-m-"]');for(var i=0;i<l.length;i++)one(l[i]);}
window.KJO_MEDIA_INIT=init;
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();
})();

KJOJS
);

/* ------------------------------------------------------------------
 * Kopfzeile (seit 1.4.0)
 * 1) Navigation bleibt beim Scrollen oben stehen.
 * 2) Anrufen + WhatsApp als zwei Icons rechts in der Navigation.
 * 3) Cookie-Banner (Complianz) im DJ-KOLJA-ONE-Design.
 * 4) Link „Cookie-Richtlinie“ in der Fußzeile.
 * Nummer ändern: die zwei Zeilen direkt hier drunter anpassen.
 * ------------------------------------------------------------------ */
if ( ! defined( 'KJO_TEL' ) ) {
	define( 'KJO_TEL', '+491727273707' );
}
if ( ! defined( 'KJO_WA' ) ) {
	define( 'KJO_WA', '491727273707' );
}

define( 'KJO_HEAD_CSS', <<<'KJOCSS'
#kjm,#kjo,#kjm svg,#kjo svg,#kjm svg text,#kjo svg text{-webkit-user-select:none;-moz-user-select:none;user-select:none;-webkit-touch-callout:none}
#kjm ::selection,#kjo ::selection{background:transparent}
#kjm ::-moz-selection,#kjo ::-moz-selection{background:transparent}
.kjo-head{position:-webkit-sticky!important;position:sticky!important;top:0;z-index:9990}
.admin-bar .kjo-head{top:32px}
@media(max-width:782px){.admin-bar .kjo-head{top:46px}}
@media(max-width:600px){.admin-bar .kjo-head{top:0}}
.kjo-right{display:flex;align-items:center;gap:18px;flex:none}
.kjo-right>.elementor-widget-button{width:auto;flex:none}
.kjo-cta{display:flex;align-items:center;gap:10px;flex:none}
.kjo-cta a{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(178,157,117,.55);border-radius:50%;color:#B29D75;background:transparent;text-decoration:none;font-size:18px;line-height:1;transition:background .2s,color .2s,border-color .2s}
.kjo-cta a:hover,.kjo-cta a:focus-visible{background:#B29D75;border-color:#B29D75;color:#0F0C07;outline:none}
.kjo-cta svg{width:18px;height:18px;fill:currentColor;display:block}
@media(max-width:767px){.kjo-cta a,.kjo-cta .kjo-burger{width:44px;height:44px;font-size:20px}.kjo-cta svg{width:20px;height:20px}.kjo-head.kjo-small>.kjo-nav2{display:none!important}}
.kjo-cta .kjo-burger{display:none;align-items:center;justify-content:center;border:1px solid rgba(178,157,117,.55);border-radius:50%;color:#B29D75;background:transparent;padding:0;cursor:pointer;-webkit-appearance:none;appearance:none;transition:background .2s,color .2s}
.kjo-cta .kjo-burger:hover,.kjo-cta .kjo-burger:focus-visible,.kjo-head.kjo-open .kjo-burger{background:#B29D75;border-color:#B29D75;color:#0F0C07;outline:none}
.kjo-burger .kjo-x{display:none}.kjo-head.kjo-open .kjo-burger .kjo-b{display:none}.kjo-head.kjo-open .kjo-burger .kjo-x{display:block}
@media(max-width:767px){.kjo-head.kjo-small .kjo-burger{display:flex}
.kjo-head.kjo-small.kjo-open>.kjo-nav2{display:flex!important;flex-wrap:wrap;justify-content:center;gap:6px 22px;position:absolute;left:0;right:0;top:100%;padding:16px 20px 20px;background:#0F0C07;border-top:1px solid rgba(178,157,117,.3);border-bottom:1px solid rgba(178,157,117,.45);box-shadow:0 18px 30px rgba(0,0,0,.55)}
.kjo-head.kjo-small.kjo-open>.kjo-nav2 a{padding:8px 2px}}
.cmplz-cookiebanner{--cmplz_banner_background_color:#1A1712;--cmplz_banner_border_color:#B29D75;--cmplz_text_color:#F3F1E9;--cmplz_hyperlink_color:#B29D75;--cmplz_button_accept_background_color:#B29D75;--cmplz_button_accept_border_color:#B29D75;--cmplz_button_accept_text_color:#0F0C07;--cmplz_button_deny_background_color:#B29D75;--cmplz_button_deny_border_color:#B29D75;--cmplz_button_deny_text_color:#0F0C07;--cmplz_button_settings_background_color:#1A1712;--cmplz_button_settings_border_color:#A39E93;--cmplz_button_settings_text_color:#F3F1E9;--cmplz_slider_active_color:#B29D75;--cmplz_slider_inactive_color:#A39E93;--cmplz_slider_bullet_color:#0F0C07;background:#1A1712!important;border:1px solid rgba(178,157,117,.6)!important;border-radius:4px!important;color:#F3F1E9!important;font-family:"Fira Sans",sans-serif!important}
.cmplz-cookiebanner .cmplz-title,.cmplz-cookiebanner .cmplz-message,.cmplz-cookiebanner .cmplz-message p,.cmplz-cookiebanner .cmplz-category-title,.cmplz-cookiebanner .cmplz-description,.cmplz-cookiebanner .cmplz-always-active{color:#F3F1E9!important;font-family:"Fira Sans",sans-serif!important}
.cmplz-cookiebanner .cmplz-title{font-weight:300!important;letter-spacing:.02em}
.cmplz-cookiebanner .cmplz-categories .cmplz-category{background:#0F0C07!important}
.cmplz-cookiebanner .cmplz-divider{background:rgba(178,157,117,.3)!important;border-color:rgba(178,157,117,.3)!important}
.cmplz-cookiebanner .cmplz-btn{font-family:"Fira Sans",sans-serif!important;font-weight:500!important;border-radius:2px!important}
.cmplz-cookiebanner .cmplz-btn.cmplz-accept,.cmplz-cookiebanner .cmplz-btn.cmplz-deny{background:#B29D75!important;border:1px solid #B29D75!important;color:#0F0C07!important}
.cmplz-cookiebanner .cmplz-btn.cmplz-accept:hover,.cmplz-cookiebanner .cmplz-btn.cmplz-deny:hover{background:#C4B08A!important;border-color:#C4B08A!important}
.cmplz-cookiebanner .cmplz-btn.cmplz-view-preferences,.cmplz-cookiebanner .cmplz-btn.cmplz-save-preferences,.cmplz-cookiebanner .cmplz-btn.cmplz-manage-options{background:#1A1712!important;border:1px solid #A39E93!important;color:#F3F1E9!important}
.cmplz-cookiebanner .cmplz-links a,.cmplz-cookiebanner .cmplz-link,.cmplz-cookiebanner .cmplz-message a{color:#B29D75!important}
.cmplz-cookiebanner .cmplz-close,.cmplz-cookiebanner .cmplz-icon{color:#F3F1E9!important;fill:#F3F1E9!important}
#cmplz-manage-consent .cmplz-manage-consent{background:#1A1712!important;color:#F3F1E9!important;border:1px solid rgba(178,157,117,.6)!important;font-family:"Fira Sans",sans-serif!important}
KJOCSS
);

define( 'KJO_HEAD_JS', <<<'KJOJS'
(function(){
var C=window.KJO_HEAD||{};
function top(el){
  var p=el.closest?el.closest(".e-con.e-parent"):null; if(p)return p;
  p=el; while(p.parentNode&&p.parentNode.classList&&!p.parentNode.classList.contains("elementor"))p=p.parentNode;
  return p;
}
function stick(h){
  var small=false;
  function sc(){var y=window.pageYOffset||document.documentElement.scrollTop||0;
    if(!small&&y>220){small=true;h.classList.add("kjo-small");}
    else if(small&&y<40){small=false;h.classList.remove("kjo-small");h.classList.remove("kjo-open");}}
  window.addEventListener("scroll",sc,{passive:true}); sc();
  /* Handy: Beim Scrollen wird die zweite Menüzeile ausgeblendet – ein Menü-Knopf (Hamburger) holt sie bei Bedarf zurück. */
  var cta=h.querySelector(".kjo-cta"),nav=h.querySelector(".kjo-nav2");
  if(cta&&nav&&!cta.querySelector(".kjo-burger")){
    var b=document.createElement("button"); b.type="button"; b.className="kjo-burger"; b.setAttribute("aria-label","Menü öffnen"); b.setAttribute("aria-expanded","false");
    b.innerHTML='<svg class="kjo-b" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg><svg class="kjo-x" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
    cta.insertBefore(b,cta.firstChild);
    function set(o){h.classList.toggle("kjo-open",o);b.setAttribute("aria-expanded",o?"true":"false");b.setAttribute("aria-label",o?"Menü schließen":"Menü öffnen");}
    b.addEventListener("click",function(e){e.stopPropagation();set(!h.classList.contains("kjo-open"));});
    nav.addEventListener("click",function(e){if(e.target.closest&&e.target.closest("a"))set(false);});
    document.addEventListener("click",function(e){if(h.classList.contains("kjo-open")&&!h.contains(e.target))set(false);});
    document.addEventListener("keydown",function(e){if(e.key==="Escape")set(false);});
    window.addEventListener("scroll",function(){if(h.classList.contains("kjo-open")&&Math.abs((window.pageYOffset||0)-(window._kjoY||0))>120)set(false);},{passive:true});
    h.addEventListener("click",function(){window._kjoY=window.pageYOffset||0;},true);
  }
}
function head(){
  var st=document.querySelector(".kjo-head[data-static]");
  if(st){stick(st);return;}
  if(document.querySelector(".kjo-cta"))return;
  var logo=document.querySelector('.elementor img[src*="dj-kolja-one-logo-ohne-claim"]')||document.querySelector('.elementor img[src*="dj-kolja-one-logo"]');
  if(!logo)return;
  var w=logo.closest(".elementor-widget")||logo.parentNode, row=w.parentNode, h=top(row);
  if(!h||!row)return;
  h.classList.add("kjo-head");
  var n2=h.querySelector(":scope > .elementor-hidden-desktop")||h.querySelector(".elementor-hidden-desktop"); if(n2)n2.classList.add("kjo-nav2");
  var c=document.createElement("div"); c.className="kjo-cta";
  c.innerHTML='<a class="kjo-tel" href="tel:'+C.tel+'" aria-label="Anrufen" title="Anrufen">'+C.icoTel+'</a>'+
              '<a class="kjo-wa" href="https://wa.me/'+C.wa+'" target="_blank" rel="noopener" aria-label="WhatsApp schreiben" title="WhatsApp schreiben">'+C.icoWa+'</a>';
  var r=document.createElement("div"); r.className="kjo-right"; r.appendChild(c);
  var btn=null,k=row.children;
  for(var i=0;i<k.length;i++){if(k[i].classList&&k[i].classList.contains("elementor-widget-button")){btn=k[i];break;}}
  if(btn){row.insertBefore(r,btn);r.appendChild(btn);}else{row.appendChild(r);}
  stick(h);
}
function musikHero(){
  var a=document.querySelector('.elementor a[href$="#auflegen"]'); if(!a)return;
  var w=a.closest(".elementor-widget-button"); if(!w||!w.parentNode)return;
  var p=w.parentNode, k=p.children, only=true;
  for(var i=0;i<k.length;i++){if(!(k[i].classList&&k[i].classList.contains("elementor-widget-button")))only=false;}
  if(only)p.style.display="none"; else for(var j=0;j<k.length;j++){if(k[j].classList.contains("elementor-widget-button"))k[j].style.display="none";}
}
/* „Meine Musik“: schwarze Lücke zwischen Einleitungstext und DJ-Pult entfernen */
function musikGap(){
  var k=document.getElementById("kjm"); if(!k)return;
  var r=k.closest("[data-elementor-type]")||k.closest(".elementor"); if(!r)return;
  function top(el){while(el&&el.parentNode&&el.parentNode!==r)el=el.parentNode;return el&&el.parentNode===r?el:null}
  var t=top(k),h1=r.querySelector("h1"),h=h1?top(h1):null; if(!t||!h||t===h)return;
  t.style.setProperty("padding-top","0","important"); h.style.setProperty("padding-bottom","0","important");
}
function foot(){
  if(!C.cookie||document.querySelector(".kjo-cookie-link"))return;
  var a=document.querySelectorAll('.elementor a[href$="/datenschutz/"],.elementor a[href$="/datenschutz"]'); if(!a.length)return;
  var l=a[a.length-1], w=l.closest(".elementor-widget"); if(!w||!w.parentNode)return;
  var n=w.cloneNode(true), x=n.querySelector("a"); if(!x)return;
  n.classList.add("kjo-cookie-link"); x.setAttribute("href",C.cookie); x.textContent="Cookie-Richtlinie";
  w.parentNode.insertBefore(n,w.nextSibling);
}
function init(){try{head();}catch(e){}try{foot();}catch(e){}try{musikHero();}catch(e){}try{musikGap();}catch(e){}}
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();
})();
KJOJS
);

function kjo_icons() {
	$tel = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>';
	$wa  = kjo_fa_dir()
		? '<i class="fab fa-whatsapp" aria-hidden="true"></i>'
		: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3C6.9 3 3 6.7 3 11.2c0 2 .8 3.8 2.1 5.2L4 21l4.8-1.5c1 .3 2.1.5 3.2.5 5.1 0 9-3.7 9-8.2S17.1 3 12 3z"/></svg>';
	return array( $tel, $wa );
}

function kjo_fa_dir() {
	$d = WP_PLUGIN_DIR . '/elementor/assets/lib/font-awesome/css/';
	return ( file_exists( $d . 'fontawesome.min.css' ) && file_exists( $d . 'brands.min.css' ) ) ? $d : '';
}

function kjo_cookie_url() {
	foreach ( array( 'cookie-richtlinie-eu', 'cookie-richtlinie', 'cookie-policy-eu' ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			return get_permalink( $p );
		}
	}
	return '';
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( kjo_fa_dir() ) {
			$u = plugins_url( 'elementor/assets/lib/font-awesome/css/' );
			wp_enqueue_style( 'kjo-fa', $u . 'fontawesome.min.css', array(), '5' );
			wp_enqueue_style( 'kjo-fa-brands', $u . 'brands.min.css', array( 'kjo-fa' ), '5' );
		}
	},
	20
);

add_action(
	'wp_head',
	function () {
		echo '<style id="kjo-head-css">' . KJO_HEAD_CSS . '</style>';
	},
	99
);

add_action(
	'wp_footer',
	function () {
		if ( is_admin() ) {
			return;
		}
		list( $tel, $wa ) = kjo_icons();
		$cfg = array(
			'tel'    => KJO_TEL,
			'wa'     => KJO_WA,
			'icoTel' => $tel,
			'icoWa'  => $wa,
			'cookie' => kjo_cookie_url(),
		);
		echo '<script id="kjo-head-js">window.KJO_HEAD=' . wp_json_encode( $cfg ) . ';' . KJO_HEAD_JS . '</script>';
	},
	60
);


/* ------------------------------------------------------------------
 * Seiten ohne Elementor im DJ-KOLJA-ONE-Design (seit 1.5.0)
 * Betrifft z. B. die Cookie-Richtlinie von Complianz: gleiche Kopf-
 * und Fußzeile, dunkler Hintergrund, Fira Sans.
 * ------------------------------------------------------------------ */
function kjo_is_plain_page() {
	if ( ! is_page() || is_front_page() ) {
		return false;
	}
	return 'builder' !== get_post_meta( get_queried_object_id(), '_elementor_edit_mode', true );
}

add_filter(
	'template_include',
	function ( $t ) {
		return kjo_is_plain_page() ? __DIR__ . '/kjo-page.php' : $t;
	},
	99
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! kjo_is_plain_page() ) {
			return;
		}
		$up = wp_upload_dir();
		foreach ( array( 'firasans.css', 'fira-sans.css' ) as $f ) {
			if ( file_exists( $up['basedir'] . '/elementor/google-fonts/css/' . $f ) ) {
				wp_enqueue_style( 'kjo-firasans', $up['baseurl'] . '/elementor/google-fonts/css/' . $f, array(), '1' );
				break;
			}
		}
	},
	30
);

define( 'KJO_PLAIN_CSS', <<<'KJOCSS'
html{scroll-behavior:smooth}
body.kjo-plain{margin:0!important;padding:0!important;background:#0F0C07!important;color:#F3F1E9!important;font-family:"Fira Sans",-apple-system,"Segoe UI",Roboto,sans-serif!important;font-weight:400;font-size:17px;line-height:1.7;-webkit-font-smoothing:antialiased}
body.kjo-plain *{box-sizing:border-box}
.kjo-sh{display:flex;flex-direction:column;gap:8px;padding:16px 40px;background:#0F0C07;border-bottom:1px solid rgba(178,157,117,.25)}
.kjo-sh-row{display:flex;align-items:center;justify-content:space-between;gap:18px}
.kjo-sh-logo{flex:none;display:block;line-height:0}
.kjo-sh-logo img{width:210px;height:auto;display:block}
.kjo-sh-nav{display:flex;align-items:center;justify-content:center;gap:20px;flex-wrap:wrap}
.kjo-sh a.kjo-l{color:#F3F1E9;text-decoration:none;font-size:15px;line-height:1.15;transition:color .2s}
.kjo-sh a.kjo-l:hover{color:#B29D75}
.kjo-sh-btn{display:inline-block;flex:none;background:#B29D75;color:#0F0C07!important;text-decoration:none;font-size:13px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;padding:12px 18px;border-radius:2px;line-height:1;transition:background .2s}
.kjo-sh-btn:hover{background:#C4B08A}
.kjo-sh .kjo-nav2{display:none;flex-wrap:wrap;justify-content:center;gap:14px;padding-top:12px;border-top:1px solid rgba(178,157,117,.3)}
.kjo-sh .kjo-nav2 a.kjo-l{font-size:13px}
@media(max-width:1024px){.kjo-sh-nav{display:none}.kjo-sh .kjo-nav2{display:flex}}
@media(max-width:767px){.kjo-sh{padding:14px 20px}.kjo-sh-logo img{width:170px}.kjo-sh-btn{display:none}}
.kjo-doc{max-width:900px;margin:0 auto;padding:72px 24px 96px}
.kjo-doc h1{font-weight:300;font-size:clamp(34px,5vw,54px);line-height:1.15;margin:0 0 32px;color:#F3F1E9}
.kjo-doc h2{font-weight:300;font-size:26px;line-height:1.25;margin:44px 0 12px;color:#F3F1E9}
.kjo-doc h3,.kjo-doc h4,.kjo-doc h5{font-weight:500;font-size:17px;margin:20px 0 8px;color:#F3F1E9}
.kjo-doc p,.kjo-doc li,.kjo-doc span,.kjo-doc div,.kjo-doc i,.kjo-doc em,.kjo-doc label{color:inherit}
.kjo-doc p{margin:0 0 14px;color:#D9D5CA}
.kjo-doc a{color:#B29D75!important;text-decoration:underline;text-underline-offset:3px}
.kjo-doc a:hover{color:#C4B08A!important}
.kjo-doc #cmplz-document,.kjo-doc .cmplz-document{max-width:none!important;margin:0!important;color:#D9D5CA!important;font-size:17px!important;font-family:inherit!important}
.kjo-doc #cmplz-document h2,.kjo-doc #cmplz-document h3,.kjo-doc #cmplz-document h4,.kjo-doc #cmplz-document p,.kjo-doc #cmplz-document li{font-family:inherit!important}
.kjo-doc #cmplz-document p,.kjo-doc #cmplz-document li{font-size:17px!important;color:#D9D5CA!important}
.kjo-doc details,.kjo-doc .cmplz-dropdown{background:#1A1712!important;border:1px solid rgba(178,157,117,.25)!important;border-radius:3px;margin:10px 0!important;color:#F3F1E9!important}
.kjo-doc details summary,.kjo-doc .cmplz-dropdown summary{background:transparent!important;color:#F3F1E9!important;cursor:pointer}
.kjo-doc details summary h3,.kjo-doc details summary h4,.kjo-doc details summary p,.kjo-doc details summary div{color:#F3F1E9!important;background:transparent!important}
.kjo-doc .cookies-per-purpose{background:rgba(178,157,117,.25)!important;border:1px solid rgba(178,157,117,.25)!important}
.kjo-doc .cookies-per-purpose>div,.kjo-doc .cookies-per-purpose div{background:#0F0C07!important;color:#D9D5CA!important}
.kjo-doc .cookies-per-purpose .purpose,.kjo-doc .cookies-per-purpose h4{color:#F3F1E9!important}
.kjo-doc table{width:100%;border-collapse:collapse;margin:14px 0}
.kjo-doc th,.kjo-doc td{border:1px solid rgba(178,157,117,.25);padding:10px 12px;text-align:left;color:#D9D5CA;background:transparent}
.kjo-doc .cmplz-btn,.kjo-doc button,.kjo-doc input[type=submit]{background:#B29D75!important;border:1px solid #B29D75!important;color:#0F0C07!important;font-family:inherit!important;font-weight:500;border-radius:2px;padding:10px 16px;cursor:pointer}
.kjo-doc .cmplz-categories .cmplz-category,.kjo-doc .cmplz-category{background:#1A1712!important;color:#F3F1E9!important}
.kjo-doc .cmplz-category-title,.kjo-doc .cmplz-description,.kjo-doc .cmplz-always-active{color:#F3F1E9!important}
.kjo-sf{border-top:1px solid rgba(178,157,117,.2);padding:28px 40px;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;color:#A39E93;font-size:13px}
.kjo-sf nav{display:flex;gap:20px;flex-wrap:wrap}
.kjo-sf a{color:#A39E93;text-decoration:none;font-weight:300}
.kjo-sf a:hover{color:#B29D75}
@media(max-width:767px){.kjo-sf{padding:24px 20px;flex-direction:column;align-items:flex-start}.kjo-doc{padding:48px 20px 72px}}
KJOCSS
);
