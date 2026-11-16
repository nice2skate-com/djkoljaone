<?php
/**
 * Auftragsbestätigung (seit 1.85.0).
 *
 * Aus jedem Angebot entsteht mit einem Klick eine Auftragsbestätigung als PDF (gleiche Optik, Zweig A.ab in
 * assets/pdf/angebot-pdf.js): Nummer „AB-<Angebotsnummer>“, feste Positionen und Auftragssumme, optionaler Zahlungshinweis,
 * Vertragsbedingungen bzw. Mietvertrag wie im Angebot. Versand per E-Mail (PDF im Anhang) oder WhatsApp (Link).
 * Angebot → „Auftrag bestätigt“, DJ-Anfrage → „Gebucht“.
 *
 * Auch über Telegram/n8n (Schlüssel wie bei den Angeboten, Kopfzeile X-KJO-Key):
 *   POST /wp-json/kjo/v1/auto/ab                 {nr | id, datum?, zahlung?}  → {id, nr, data, cfg, to, mail}
 *   POST /wp-json/kjo/v1/auto/ab/{id}/pdf        {pdf}                        → {link, datei}
 *   POST /wp-json/kjo/v1/auto/ab/{id}/versendet  {to}                         → Status, Verlauf
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Daten ---------- */
function kjo_ab_get( $id ) {
	$b = get_post_meta( $id, '_kjo_ab', true );
	return is_array( $b ) ? $b : array();
}
function kjo_ab_ihr( $a ) {
	return isset( $a['sie'] ) && false === $a['sie'];
}
/** Auftragsbestätigung anlegen bzw. neu fassen. $in: datum, zahlung, text, schluss (leer = Standard aus den Einstellungen). */
function kjo_ab_anlegen( $id, $in = array() ) {
	$a   = kjo_ang_get( $id );
	$o   = kjo_ang_opts();
	$sfx = kjo_ab_ihr( $a ) ? '_ihr' : '';
	if ( empty( $a['nr'] ) || ! array_filter( (array) ( $a['pos'] ?? array() ), fn( $p ) => empty( $p['o'] ) ) ) {
		return new WP_Error( 'kjo_ab', 'Das Angebot braucht eine Nummer und mindestens eine feste Position.', array( 'status' => 400 ) );
	}
	$alt   = kjo_ab_get( $id );
	$datum = isset( $in['datum'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $in['datum'] ) ? (string) $in['datum'] : wp_date( 'Y-m-d' );
	$t     = function ( $k, $std ) use ( $in ) {
		return isset( $in[ $k ] ) && '' !== trim( (string) $in[ $k ] ) ? sanitize_textarea_field( (string) $in[ $k ] ) : $std;
	};
	$b = array(
		'nr'      => 'AB-' . $a['nr'],
		'datum'   => $datum,
		'titel'   => 'Auftragsbestätigung' . ( $a['anlass'] ? ' – ' . $a['anlass'] : '' ),
		'text'    => $t( 'text', (string) $o[ 'ab_text' . $sfx ] ),
		'schluss' => $t( 'schluss', (string) $o[ 'ab_schluss' . $sfx ] ),
		'zahlung' => $t( 'zahlung', (string) ( $alt['zahlung'] ?? '' ) ),
		'summe'   => kjo_ang_summe( $a ),
	);
	update_post_meta( $id, '_kjo_ab', $b );
	kjo_ang_log( $id, ( $alt ? 'Auftragsbestätigung neu gefasst' : 'Auftragsbestätigung erstellt' ) . ' (' . kjo_vl_eur( $b['summe'] ) . ')' );
	$st = (string) get_post_meta( $id, '_kjo_ang_status', true );
	if ( in_array( $st, array( '', 'entwurf', 'versendet', 'angenommen' ), true ) ) {
		update_post_meta( $id, '_kjo_ang_status', 'bestaetigt' );
		kjo_ang_lead( $id, 'gebucht', 'Auftrag bestätigt: ' . $b['nr'] . ' (' . kjo_vl_eur( $b['summe'] ) . ')' );
	}
	return $b;
}
/** Daten für das PDF: Angebot + Zweig „ab“. */
function kjo_ab_pdf_daten( $id ) {
	$a       = kjo_ang_get( $id );
	$a['ab'] = kjo_ab_get( $id );
	return $a;
}
function kjo_ab_dateiname( $id ) {
	return 'Auftragsbestaetigung-' . preg_replace( '/[^A-Za-z0-9-]/', '', kjo_ab_get( $id )['nr'] ?? '' ) . '-DJ-KOLJA-ONE.pdf';
}
function kjo_ab_link( $id ) {
	$t = (string) get_post_meta( $id, '_kjo_ab_token', true );
	return $t ? rest_url( 'kjo/v1/ab/' . $t ) : '';
}
function kjo_ab_pdf_ablegen( $id, $pdf ) {
	$bin = base64_decode( preg_replace( '#^data:application/pdf;[^,]*base64,#', '', (string) $pdf ), true );
	if ( ! $bin || 0 !== strpos( $bin, '%PDF' ) || strlen( $bin ) > 15 * MB_IN_BYTES ) {
		return new WP_Error( 'kjo_ab', 'Das PDF ist ungültig.', array( 'status' => 400 ) );
	}
	if ( ! kjo_ab_get( $id ) ) {
		return new WP_Error( 'kjo_ab', 'Erst die Auftragsbestätigung anlegen.', array( 'status' => 400 ) );
	}
	$dir = kjo_ang_dir();
	$alt = (string) get_post_meta( $id, '_kjo_ab_pdf', true );
	$neu = 'auftrag-' . $id . '-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
	if ( false === file_put_contents( $dir . $neu, $bin ) ) { // phpcs:ignore
		return new WP_Error( 'kjo_ab', 'Das PDF konnte nicht gespeichert werden (Schreibrechte im Upload-Ordner?).', array( 'status' => 500 ) );
	}
	if ( $alt && $alt !== $neu ) {
		@unlink( $dir . basename( $alt ) ); // phpcs:ignore
	}
	update_post_meta( $id, '_kjo_ab_pdf', $neu );
	if ( ! get_post_meta( $id, '_kjo_ab_token', true ) ) {
		update_post_meta( $id, '_kjo_ab_token', bin2hex( random_bytes( 16 ) ) );
	}
	kjo_ang_log( $id, 'PDF Auftragsbestätigung erzeugt' );
	return kjo_ab_link( $id );
}
/** Platzhalter in E-Mail- und WhatsApp-Texten. */
function kjo_ab_platzhalter( $txt, $id ) {
	$a = kjo_ang_get( $id );
	$b = kjo_ab_get( $id );
	$d = ! empty( $a['von'] ) ? wp_date( 'd.m.Y', strtotime( $a['von'] ) ) : '';
	return strtr(
		(string) $txt,
		array(
			'{name}'        => (string) $a['name'],
			'{nr}'          => (string) ( $b['nr'] ?? '' ),
			'{termin}'      => $d,
			'{termin_satz}' => $d ? ' für den ' . $d : '',
			'{betrag}'      => kjo_vl_eur( (float) ( $b['summe'] ?? kjo_ang_summe( $a ) ) ),
			'{link}'        => kjo_ab_link( $id ),
		)
	);
}
function kjo_ab_mail_vorlage( $id ) {
	$o   = kjo_ang_opts();
	$a   = kjo_ang_get( $id );
	$sfx = kjo_ab_ihr( $a ) ? '_ihr' : '';
	return array(
		'to'  => (string) $a['email'],
		'tel' => (string) ( $a['tel'] ?? '' ),
		'sub' => kjo_ab_platzhalter( $o[ 'ab_mail_betreff' . $sfx ], $id ),
		'txt' => kjo_ab_platzhalter( $o[ 'ab_mail_text' . $sfx ], $id ),
		'wa'  => kjo_ab_platzhalter( $o[ 'ab_wa_text' . $sfx ], $id ),
	);
}
function kjo_ab_mail_senden( $id, $to, $sub, $txt ) {
	$pdf = (string) get_post_meta( $id, '_kjo_ab_pdf', true );
	if ( ! is_email( $to ) || ! $sub || ! $txt ) {
		return new WP_Error( 'kjo_ab', 'Bitte Empfänger, Betreff und Text ausfüllen.', array( 'status' => 400 ) );
	}
	if ( ! $pdf || ! is_readable( kjo_ang_dir() . basename( $pdf ) ) ) {
		return new WP_Error( 'kjo_ab', 'Bitte zuerst das PDF erzeugen.', array( 'status' => 400 ) );
	}
	$tmp = trailingslashit( get_temp_dir() ) . 'kjo-' . wp_generate_password( 8, false ) . '/';
	wp_mkdir_p( $tmp );
	$att = $tmp . kjo_ab_dateiname( $id );
	copy( kjo_ang_dir() . basename( $pdf ), $att );
	$from = function_exists( 'kjo_vl_mail_to' ) ? kjo_vl_mail_to() : get_option( 'admin_email' );
	$ok   = wp_mail( $to, $sub, $txt, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: DJ KOLJA ONE <' . $from . '>', 'Bcc: ' . $from ), array( $att ) );
	@unlink( $att ); // phpcs:ignore
	@rmdir( $tmp ); // phpcs:ignore
	if ( ! $ok ) {
		return new WP_Error( 'kjo_ab', 'Die E-Mail konnte nicht gesendet werden. Bitte später erneut versuchen oder per WhatsApp senden.', array( 'status' => 500 ) );
	}
	kjo_ab_versendet( $id, 'Per E-Mail an ' . $to );
	return true;
}
function kjo_ab_versendet( $id, $wie ) {
	update_post_meta( $id, '_kjo_ab_versendet', time() );
	kjo_ang_log( $id, 'Auftragsbestätigung ' . $wie . ' gesendet' );
	kjo_ang_lead( $id, '', 'Auftragsbestätigung ' . ( kjo_ab_get( $id )['nr'] ?? '' ) . ' ' . $wie . ' gesendet' );
}

/* ---------- Kunden-Link ---------- */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/ab/(?P<t>[a-f0-9]{32})',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) {
					$l = get_posts( array( 'post_type' => 'kjo_angebot', 'post_status' => 'private', 'numberposts' => 1, 'meta_key' => '_kjo_ab_token', 'meta_value' => $r['t'] ) ); // phpcs:ignore
					$f = $l ? kjo_ang_dir() . basename( (string) get_post_meta( $l[0]->ID, '_kjo_ab_pdf', true ) ) : '';
					if ( ! $l || ! get_post_meta( $l[0]->ID, '_kjo_ab_pdf', true ) || ! is_readable( $f ) ) {
						return new WP_REST_Response( array( 'message' => 'Diese Auftragsbestätigung ist nicht mehr verfügbar.' ), 404 );
					}
					nocache_headers();
					header( 'Content-Type: application/pdf' );
					header( 'Content-Disposition: inline; filename="' . kjo_ab_dateiname( $l[0]->ID ) . '"' );
					header( 'Content-Length: ' . filesize( $f ) );
					header( 'X-Robots-Tag: noindex, nofollow' );
					readfile( $f ); // phpcs:ignore
					exit;
				},
			)
		);
		/* Telegram/n8n */
		if ( ! function_exists( 'kjo_auto_key_ok' ) ) {
			return;
		}
		register_rest_route(
			'kjo/v1',
			'/auto/ab',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function ( WP_REST_Request $r ) {
					$id = kjo_ab_auto_finden( $r );
					if ( is_wp_error( $id ) ) {
						return $id;
					}
					$b = kjo_ab_anlegen( $id, array( 'datum' => (string) $r->get_param( 'datum' ), 'zahlung' => (string) $r->get_param( 'zahlung' ) ) );
					if ( is_wp_error( $b ) ) {
						return $b;
					}
					$m = kjo_ab_mail_vorlage( $id );
					return array(
						'id'   => $id,
						'nr'   => $b['nr'],
						'summe' => (float) $b['summe'],
						'name' => (string) kjo_ang_get( $id )['name'],
						'data' => kjo_ab_pdf_daten( $id ), // für den PDF-Dienst (/render mit {data, cfg})
						'cfg'  => kjo_ang_pdf_cfg(),
						'to'   => $m['to'],
						'mail' => array( 'sub' => $m['sub'], 'txt' => $m['txt'] ),
						'datei' => kjo_ab_dateiname( $id ),
						'edit' => admin_url( 'post.php?post=' . (int) $id . '&action=edit' ),
					);
				},
			)
		);
		register_rest_route(
			'kjo/v1',
			'/auto/ab/(?P<id>\d+)/pdf',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function ( WP_REST_Request $r ) {
					$id = absint( $r['id'] );
					if ( 'kjo_angebot' !== get_post_type( $id ) ) {
						return new WP_Error( 'kjo_ab', 'Angebot nicht gefunden.', array( 'status' => 404 ) );
					}
					$link = kjo_ab_pdf_ablegen( $id, (string) $r->get_param( 'pdf' ) );
					if ( is_wp_error( $link ) ) {
						return $link;
					}
					$m = kjo_ab_mail_vorlage( $id ); // jetzt mit Link
					return array( 'id' => $id, 'link' => $link, 'datei' => kjo_ab_dateiname( $id ), 'wa' => $m['wa'] );
				},
			)
		);
		// Vorhandene Auftragsbestätigung abfragen (seit 1.85.1) – für den „Senden“-Knopf in Telegram
		register_rest_route(
			'kjo/v1',
			'/auto/ab/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function ( WP_REST_Request $r ) {
					$id = absint( $r['id'] );
					$b  = 'kjo_angebot' === get_post_type( $id ) ? kjo_ab_get( $id ) : array();
					if ( ! $b || ! get_post_meta( $id, '_kjo_ab_pdf', true ) ) {
						return new WP_Error( 'kjo_ab', 'Auftragsbestätigung bzw. PDF nicht gefunden.', array( 'status' => 404 ) );
					}
					$m = kjo_ab_mail_vorlage( $id );
					return array(
						'id'    => $id,
						'nr'    => $b['nr'],
						'summe' => (float) $b['summe'],
						'name'  => (string) kjo_ang_get( $id )['name'],
						'to'    => $m['to'],
						'mail'  => array( 'sub' => $m['sub'], 'txt' => $m['txt'] ),
						'wa'    => $m['wa'],
						'link'  => kjo_ab_link( $id ),
						'datei' => kjo_ab_dateiname( $id ),
						'versendet' => (int) get_post_meta( $id, '_kjo_ab_versendet', true ),
					);
				},
			)
		);
		register_rest_route(
			'kjo/v1',
			'/auto/ab/(?P<id>\d+)/versendet',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function ( WP_REST_Request $r ) {
					$id = absint( $r['id'] );
					if ( 'kjo_angebot' !== get_post_type( $id ) || ! kjo_ab_get( $id ) ) {
						return new WP_Error( 'kjo_ab', 'Auftragsbestätigung nicht gefunden.', array( 'status' => 404 ) );
					}
					$to = sanitize_email( (string) $r->get_param( 'to' ) );
					kjo_ab_versendet( $id, 'per E-Mail' . ( $to ? ' an ' . $to : '' ) . ' (Telegram)' );
					return array( 'id' => $id, 'status' => 'versendet' );
				},
			)
		);
	}
);
/** Angebot für Telegram finden: per id oder Angebotsnummer (z. B. „2026-207“). */
function kjo_ab_auto_finden( WP_REST_Request $r ) {
	$id = absint( $r->get_param( 'id' ) );
	$nr = preg_replace( '/[^0-9A-Za-z-]/', '', preg_replace( '/^AB-/i', '', (string) $r->get_param( 'nr' ) ) );
	if ( ! $id && $nr ) {
		foreach ( get_posts( array( 'post_type' => 'kjo_angebot', 'post_status' => 'any', 'numberposts' => 50, 's' => $nr, 'fields' => 'ids' ) ) as $p ) {
			if ( ( kjo_ang_get( $p )['nr'] ?? '' ) === $nr ) {
				$id = $p;
				break;
			}
		}
	}
	if ( ! $id || 'kjo_angebot' !== get_post_type( $id ) || 'trash' === get_post_status( $id ) ) {
		return new WP_Error( 'kjo_ab', 'Angebot ' . ( $nr ? $nr . ' ' : '' ) . 'nicht gefunden.', array( 'status' => 404 ) );
	}
	return $id;
}

/* ---------- Kasten im Angebot ---------- */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_ab_box', 'Auftragsbestätigung', 'kjo_ab_box', 'kjo_angebot', 'normal', 'default' );
	},
	9
);
function kjo_ab_box( $post ) {
	$b   = kjo_ab_get( $post->ID );
	$pdf = (string) get_post_meta( $post->ID, '_kjo_ab_pdf', true );
	$o   = kjo_ang_opts();
	$sfx = kjo_ab_ihr( kjo_ang_get( $post->ID ) ) ? '_ihr' : '';
	echo '<div id="kjo-ab" data-mail="' . esc_attr( wp_json_encode( $b ? kjo_ab_mail_vorlage( $post->ID ) : array() ) ) . '">';
	if ( $b ) {
		$vs = (int) get_post_meta( $post->ID, '_kjo_ab_versendet', true );
		echo '<p><strong>' . esc_html( $b['nr'] ) . '</strong> vom ' . esc_html( wp_date( 'd.m.Y', strtotime( $b['datum'] ) ) ) . ' · ' . esc_html( kjo_vl_eur( (float) $b['summe'] ) ) . ( $vs ? ' · gesendet ' . esc_html( wp_date( 'd.m.Y', $vs ) ) : '' ) . '</p>';
		echo '<p>' . ( $pdf ? '<a class="button" href="' . esc_url( kjo_ab_link( $post->ID ) ) . '" target="_blank" rel="noopener">PDF ansehen</a> ' : '' ) . '<button type="button" class="button" data-ab="mail">Per E-Mail senden …</button> <button type="button" class="button" data-ab="wa">Per WhatsApp senden</button></p>';
		echo '<div id="kjo-ab-mail" class="kjo-re-panel" hidden><p><label>An<br><input type="email" id="kjo-ab-mail-to" class="regular-text"></label></p><p><label>Betreff<br><input id="kjo-ab-mail-sub" class="large-text"></label></p><p><label>Text<br><textarea id="kjo-ab-mail-txt" rows="9" class="large-text"></textarea></label></p><p><button type="button" class="button button-primary" id="kjo-ab-mail-send">Jetzt senden (PDF im Anhang)</button> <button type="button" class="button-link" data-ab="zu">abbrechen</button></p></div>';
		echo '<details style="margin-top:10px"><summary>Neu fassen (z. B. nach Änderungen am Angebot)</summary>';
	} else {
		echo '<p class="description">Bestätigt die Buchung verbindlich: feste Positionen und Summe wie oben im Angebot, dazu Vertragsbedingungen bzw. Mietvertrag wie im Angebot. Das Angebot wird auf „Auftrag bestätigt“ gesetzt, die DJ-Anfrage auf „Gebucht“.</p>';
	}
	echo '<p><label>Datum <input type="date" id="kjo-ab-datum" value="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></label></p>';
	echo '<p><label>Text<br><textarea id="kjo-ab-text" rows="2" class="large-text">' . esc_textarea( $b['text'] ?? $o[ 'ab_text' . $sfx ] ) . '</textarea></label></p>';
	echo '<p><label>Zahlungshinweis (optional, z. B. Anzahlung)<br><textarea id="kjo-ab-zahlung" rows="2" class="large-text" placeholder="leer = kein Zahlungshinweis">' . esc_textarea( $b['zahlung'] ?? '' ) . '</textarea></label></p>';
	echo '<p><label>Schlusstext<br><textarea id="kjo-ab-schluss" rows="2" class="large-text">' . esc_textarea( $b['schluss'] ?? $o[ 'ab_schluss' . $sfx ] ) . '</textarea></label></p>';
	echo '<p><button type="button" class="button button-primary" id="kjo-ab-neu">' . ( $b ? 'Neu fassen und PDF erzeugen' : 'Auftragsbestätigung erstellen (PDF)' ) . '</button></p>';
	if ( $b ) {
		echo '</details>';
	}
	echo '<p class="description" id="kjo-ab-msg" role="status"></p></div>';
}

/* ---------- AJAX ---------- */
add_action(
	'wp_ajax_kjo_ab_neu',
	function () {
		$id = kjo_ang_ajax_check();
		$d  = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : null; // phpcs:ignore
		if ( is_array( $d ) ) { // Angebot so übernehmen, wie es gerade im Editor steht
			$a = kjo_ang_speichern( $id, $d );
			wp_update_post( array( 'ID' => $id, 'post_title' => kjo_ang_titel( $a ), 'post_status' => 'private' ) );
		}
		$in = array();
		foreach ( array( 'datum', 'text', 'zahlung', 'schluss' ) as $k ) {
			$in[ $k ] = isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) : ''; // phpcs:ignore
		}
		$b = kjo_ab_anlegen( $id, $in );
		if ( is_wp_error( $b ) ) {
			wp_send_json_error( array( 'message' => $b->get_error_message() ), 400 );
		}
		wp_send_json_success( array( 'data' => kjo_ab_pdf_daten( $id ) ) );
	}
);
add_action(
	'wp_ajax_kjo_ab_pdf',
	function () {
		$id = kjo_ang_ajax_check();
		$r  = kjo_ab_pdf_ablegen( $id, isset( $_POST['pdf'] ) ? (string) wp_unslash( $_POST['pdf'] ) : '' ); // phpcs:ignore
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'message' => $r->get_error_message() ), 400 );
		}
		wp_send_json_success( array( 'link' => $r ) );
	}
);
add_action(
	'wp_ajax_kjo_ab_mail',
	function () {
		$id = kjo_ang_ajax_check();
		$r  = kjo_ab_mail_senden(
			$id,
			isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '',
			isset( $_POST['sub'] ) ? sanitize_text_field( wp_unslash( $_POST['sub'] ) ) : '',
			isset( $_POST['txt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['txt'] ) ) : ''
		);
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( array( 'message' => $r->get_error_message() ), 400 );
		}
		wp_send_json_success( array( 'message' => 'Gesendet (Kopie an dich).' ) );
	}
);
add_action(
	'wp_ajax_kjo_ab_wa',
	function () {
		$id = kjo_ang_ajax_check();
		kjo_ab_versendet( $id, 'per WhatsApp' );
		wp_send_json_success();
	}
);
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$s = get_current_screen();
		if ( ! $s || 'kjo_angebot' !== $s->post_type || 'post.php' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'kjo-auftrag', plugins_url( 'assets/pdf/auftrag.js', __FILE__ ), array( 'kjo-angebot' ), KJO_VERSION, true );
	},
	20
);
