<?php
/**
 * Rechnungen (seit 1.77.0): aus jedem Angebot eine komplette Rechnung oder Anzahlungs- und Schlussrechnung erzeugen,
 * als PDF ablegen und per E-Mail oder WhatsApp senden.
 *
 * - Nummern: Jahr-Nummer (2026-137, 2026-138 …), fortlaufend und lückenlos vergeben, zählt jedes Jahr neu
 *   (Startwert unter Angebote → Einstellungen änderbar).
 * - Jede Rechnung ist ein eigener Eintrag (Angebote → Rechnungen) mit einer festen Kopie der Angebotsdaten:
 *   spätere Änderungen am Angebot verändern eine gestellte Rechnung nicht. Korrektur nur über „Stornieren“
 *   (Stornorechnung mit eigener Nummer), nie durch Überschreiben oder Löschen.
 * - Zahlungsziel: 15 Tage privat, 30 Tage beruflich (Firma im Angebot = beruflich, im Formular änderbar).
 * - „Bereits bezahlt“ (bar oder Überweisung) beim Erstellen: Rechnung zeigt den Zahlungseingang (Quittung).
 * - Status: Angebot und DJ-Anfrage bekommen „Rechnung gestellt“ und – wenn alles bezahlt ist – „Rechnung bezahlt“.
 * - PDF entsteht im Browser mit demselben Code wie das Angebot (assets/pdf/angebot-pdf.js, Zweig A.rechnung).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_RE_TYPEN = array(
	'komplett'  => 'Rechnung',
	'anzahlung' => 'Anzahlungsrechnung',
	'rest'      => 'Schlussrechnung',
	'storno'    => 'Stornorechnung',
);
const KJO_RE_STATI = array(
	'offen'     => 'Offen',
	'bezahlt'   => 'Bezahlt',
	'storniert' => 'Storniert',
	'storno'    => 'Storno',
);

/* ---------- Nummer ---------- */
/** Nächste Rechnungsnummer (2026-137 …). 2026 startet bei 137, jedes weitere Jahr bei 001 (in den Einstellungen änderbar). */
function kjo_re_nr( $vergeben = true ) {
	$y = (int) wp_date( 'Y' );
	$c = get_option( 'kjo_re_nr', array() );
	$n = is_array( $c ) && isset( $c['y'], $c['n'] ) && (int) $c['y'] === $y ? (int) $c['n'] : ( 2026 === $y ? 137 : 1 );
	if ( $vergeben ) {
		update_option( 'kjo_re_nr', array( 'y' => $y, 'n' => $n + 1 ), false );
	}
	return $y . '-' . str_pad( (string) $n, 3, '0', STR_PAD_LEFT );
}

/* ---------- Datentyp ---------- */
add_action(
	'init',
	function () {
		register_post_type(
			'kjo_rechnung',
			array(
				'labels'          => array(
					'name'          => 'Rechnungen',
					'singular_name' => 'Rechnung',
					'menu_name'     => 'Rechnungen',
					'all_items'     => 'Rechnungen',
					'edit_item'     => 'Rechnung',
					'not_found'     => 'Noch keine Rechnungen',
					'search_items'  => 'Rechnungen durchsuchen',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=kjo_angebot',
				'capability_type' => 'page',
				'capabilities'    => array( 'create_posts' => 'do_not_allow', 'delete_posts' => 'do_not_allow', 'delete_published_posts' => 'do_not_allow', 'delete_private_posts' => 'do_not_allow', 'delete_others_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
			)
		);
	}
);

function kjo_re_get( $id ) {
	$r = get_post_meta( $id, '_kjo_re', true );
	return is_array( $r ) ? $r : array();
}
/** Alle Rechnungen zu einem Angebot (älteste zuerst). */
function kjo_re_liste( $ang_id ) {
	return get_posts( array( 'post_type' => 'kjo_rechnung', 'post_status' => 'any', 'numberposts' => 50, 'orderby' => 'ID', 'order' => 'ASC', 'meta_key' => '_kjo_re_angebot', 'meta_value' => (int) $ang_id ) ); // phpcs:ignore
}
function kjo_re_status( $id ) {
	$s = (string) get_post_meta( $id, '_kjo_re_status', true );
	return isset( KJO_RE_STATI[ $s ] ) ? $s : 'offen';
}
function kjo_re_dateiname( $r ) {
	return ( 'komplett' === $r['typ'] ? 'Rechnung' : str_replace( 'rechnung', '-Rechnung', KJO_RE_TYPEN[ $r['typ'] ] ) ) . '-' . preg_replace( '/[^A-Za-z0-9-]/', '', $r['nr'] ) . '-DJ-KOLJA-ONE.pdf';
}
function kjo_re_dir() {
	$u = wp_upload_dir();
	$d = trailingslashit( $u['basedir'] ) . 'kjo-rechnungen/';
	if ( wp_mkdir_p( $d ) && ! file_exists( $d . '.htaccess' ) ) {
		@file_put_contents( $d . '.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore
		@file_put_contents( $d . 'index.php', "<?php\n// Stille.\n" ); // phpcs:ignore
	}
	return $d;
}
function kjo_re_link( $id ) {
	$t = (string) get_post_meta( $id, '_kjo_re_token', true );
	return $t ? rest_url( 'kjo/v1/rechnung/' . $t ) : '';
}
function kjo_re_log( $id, $text ) {
	$l   = get_post_meta( $id, '_kjo_re_log', true );
	$l   = is_array( $l ) ? $l : array();
	$l[] = array( time(), $text );
	update_post_meta( $id, '_kjo_re_log', array_slice( $l, -50 ) );
}
/** Platzhalter in Mail-/WhatsApp-Texten. */
function kjo_re_platzhalter( $t, $id ) {
	$r = kjo_re_get( $id );
	$a = isset( $r['a'] ) ? $r['a'] : array();
	if ( 'storno' === $r['typ'] ) {
		$z = 'Die Rechnung ' . $r['ref']['nr'] . ' ist damit aufgehoben.';
	} elseif ( ! empty( $r['bezahlt'] ) ) {
		$z = 'Den Betrag haben Sie bereits am ' . wp_date( 'd.m.Y', strtotime( $r['bezahlt']['datum'] ) ) . ( 'bar' === $r['bezahlt']['art'] ? ' bar' : '' ) . ' bezahlt – vielen Dank!';
	} else {
		$z = 'Bitte überweisen Sie den Betrag bis zum ' . wp_date( 'd.m.Y', strtotime( $r['faellig'] ) ) . ' auf das in der Rechnung genannte Konto.';
	}
	return strtr(
		(string) $t,
		array(
			'{name}'    => isset( $a['name'] ) ? $a['name'] : '',
			'{nr}'      => $r['nr'],
			'{art}'     => KJO_RE_TYPEN[ $r['typ'] ],
			'{betrag}'  => kjo_vl_eur( (float) $r['betrag'] ),
			'{faellig}' => wp_date( 'd.m.Y', strtotime( $r['faellig'] ) ),
			'{zahlung}' => $z,
			'{link}'    => kjo_re_link( $id ),
		)
	);
}

/* ---------- Beträge ---------- */
/** Aktive (nicht stornierte) Rechnungen eines Angebots, ohne Stornorechnungen. */
function kjo_re_aktiv( $ang_id ) {
	return array_values(
		array_filter(
			kjo_re_liste( $ang_id ),
			function ( $p ) {
				return 'offen' === kjo_re_status( $p->ID ) || 'bezahlt' === kjo_re_status( $p->ID );
			}
		)
	);
}
/** Was ist für dieses Angebot noch möglich? */
function kjo_re_moeglich( $ang_id ) {
	$schluss = false;
	$anz     = 0;
	foreach ( kjo_re_aktiv( $ang_id ) as $p ) {
		$t = kjo_re_get( $p->ID )['typ'];
		if ( 'komplett' === $t || 'rest' === $t ) {
			$schluss = true;
		} elseif ( 'anzahlung' === $t ) {
			$anz++;
		}
	}
	return array( 'komplett' => ! $schluss && ! $anz, 'anzahlung' => ! $schluss, 'rest' => ! $schluss && $anz > 0 );
}
/** Angebot und DJ-Anfrage auf „Rechnung gestellt“ / „Rechnung bezahlt“ setzen (oder zurück auf „Angenommen“). */
function kjo_re_angebot_status( $ang_id ) {
	$akt     = kjo_re_aktiv( $ang_id );
	$alt     = (string) get_post_meta( $ang_id, '_kjo_ang_status', true );
	$schluss = false;
	$offen   = false;
	$faellig = '';
	foreach ( $akt as $p ) {
		$r = kjo_re_get( $p->ID );
		if ( in_array( $r['typ'], array( 'komplett', 'rest' ), true ) ) {
			$schluss = true;
		}
		if ( 'offen' === kjo_re_status( $p->ID ) ) {
			$offen   = true;
			$faellig = ! $faellig || $r['faellig'] < $faellig ? $r['faellig'] : $faellig;
		}
	}
	$neu = ! $akt ? ( in_array( $alt, array( 'rechnung', 'bezahlt' ), true ) ? 'angenommen' : $alt ) : ( $schluss && ! $offen ? 'bezahlt' : 'rechnung' );
	if ( $neu !== $alt ) {
		update_post_meta( $ang_id, '_kjo_ang_status', $neu );
		kjo_ang_log( $ang_id, 'Status: ' . KJO_ANG_STATI[ $neu ] );
		kjo_ang_lead( $ang_id, 'rechnung' === $neu ? 'rechnung' : ( 'bezahlt' === $neu ? 're_bezahlt' : ( 'angenommen' === $neu ? 'gebucht' : '' ) ), '' );
	}
	// Wiedervorlage in der DJ-Anfrage: Fälligkeit der ältesten offenen Rechnung
	$q = (int) get_post_meta( $ang_id, '_kjo_ang_quelle', true );
	if ( $q && 'kjo_anfrage' === get_post_type( $q ) ) {
		if ( $faellig ) {
			update_post_meta( $q, '_kjo_lead_wv', $faellig );
		} elseif ( 'bezahlt' === $neu ) {
			delete_post_meta( $q, '_kjo_lead_wv' );
		}
	}
}

/* ---------- Anlegen ---------- */
/**
 * Neue Rechnung zu einem Angebot. $in: typ, datum, ziel (Tage), leistung, betrag (nur Anzahlung), bezahlt (''|bar|Überweisung), bezahlt_am.
 * Gibt die Rechnungs-ID oder WP_Error zurück.
 */
function kjo_re_anlegen( $ang_id, $in ) {
	$a   = kjo_ang_get( $ang_id );
	$o   = kjo_ang_opts();
	$typ = isset( $in['typ'] ) && in_array( $in['typ'], array( 'komplett', 'anzahlung', 'rest' ), true ) ? $in['typ'] : '';
	$mog = kjo_re_moeglich( $ang_id );
	if ( ! $typ || empty( $mog[ $typ ] ) ) {
		$txt = 'Für dieses Angebot gibt es schon eine Schluss- bzw. komplette Rechnung. Zum Korrigieren zuerst stornieren.';
		if ( 'rest' === $typ && $mog['komplett'] ) {
			$txt = 'Eine Schlussrechnung geht erst nach einer Anzahlungsrechnung.';
		} elseif ( 'komplett' === $typ && $mog['rest'] ) {
			$txt = 'Es gibt schon eine Anzahlungsrechnung – bitte stattdessen die Schlussrechnung erstellen (die Anzahlung wird abgezogen).';
		}
		return new WP_Error( 'kjo_re', $txt, 400 );
	}
	if ( ! $a['name'] ) {
		return new WP_Error( 'kjo_re', 'Bitte im Angebot den Namen des Kunden eintragen.', 400 );
	}
	$offen = 0;
	foreach ( $a['pos'] as $p ) {
		if ( ! $p['o'] && '' === $p['p'] ) {
			$offen++;
		}
	}
	if ( $offen && 'anzahlung' !== $typ ) {
		return new WP_Error( 'kjo_re', $offen . ' Position(en) im Angebot haben noch keinen Preis. Bitte zuerst eintragen.', 400 );
	}
	$gesamt = kjo_ang_summe( $a );
	$datum  = isset( $in['datum'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $in['datum'] ) ? (string) $in['datum'] : wp_date( 'Y-m-d' );
	$ziel   = isset( $in['ziel'] ) ? max( 0, min( 90, (int) $in['ziel'] ) ) : ( $a['firma'] ? (int) $o['re_ziel_firma'] : (int) $o['re_ziel_privat'] );
	$abz    = array();
	if ( 'anzahlung' === $typ ) {
		$betrag = isset( $in['betrag'] ) && function_exists( 'kjo_vl_num' ) ? kjo_vl_num( $in['betrag'] ) : null;
		if ( null === $betrag || $betrag <= 0 ) {
			return new WP_Error( 'kjo_re', 'Bitte den Betrag der Anzahlung eintragen.', 400 );
		}
		$betrag = round( $betrag, 2 );
		$schon  = 0;
		foreach ( kjo_re_aktiv( $ang_id ) as $p ) {
			$schon += (float) kjo_re_get( $p->ID )['betrag'];
		}
		if ( $gesamt > 0 && $schon + $betrag > $gesamt ) {
			return new WP_Error( 'kjo_re', 'Die Anzahlungen wären höher als der Gesamtbetrag (' . kjo_vl_eur( $gesamt ) . ').', 400 );
		}
	} else {
		$betrag = $gesamt;
		if ( 'rest' === $typ ) {
			foreach ( kjo_re_aktiv( $ang_id ) as $p ) {
				$r = kjo_re_get( $p->ID );
				if ( 'anzahlung' === $r['typ'] ) {
					$abz[]   = array( 'nr' => $r['nr'], 'datum' => $r['datum'], 'betrag' => (float) $r['betrag'] );
					$betrag -= (float) $r['betrag'];
				}
			}
			$betrag = round( $betrag, 2 );
		}
		if ( $betrag <= 0 ) {
			return new WP_Error( 'kjo_re', 'Der Rechnungsbetrag wäre 0 € oder weniger – bitte die Positionen im Angebot prüfen.', 400 );
		}
	}
	$bez = isset( $in['bezahlt'] ) && in_array( $in['bezahlt'], array( 'bar', 'Überweisung' ), true ) ? $in['bezahlt'] : '';
	$bam = isset( $in['bezahlt_am'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $in['bezahlt_am'] ) ? (string) $in['bezahlt_am'] : $datum;
	$lst = isset( $in['leistung'] ) ? mb_substr( sanitize_text_field( (string) $in['leistung'] ), 0, 80 ) : '';
	if ( '' === $lst && $a['von'] ) {
		$lst = wp_date( 'd.m.Y', strtotime( $a['von'] ) ) . ( $a['bis'] && $a['bis'] !== $a['von'] ? ' – ' . wp_date( 'd.m.Y', strtotime( $a['bis'] ) ) : '' );
	}
	$nr = kjo_re_nr();
	$r  = array(
		'nr'       => $nr,
		'typ'      => $typ,
		'datum'    => $datum,
		'ziel'     => $ziel,
		'faellig'  => $bez ? $bam : wp_date( 'Y-m-d', strtotime( $datum . ' +' . $ziel . ' days' ) ),
		'leistung' => $lst,
		'betrag'   => $betrag,
		'gesamt'   => $gesamt,
		'abzuege'  => $abz,
		'bezahlt'  => $bez ? array( 'datum' => $bam, 'art' => $bez ) : null, // nur „bei Erstellung bezahlt“ steht im PDF
		'titel'    => KJO_RE_TYPEN[ $typ ] . ( $a['anlass'] ? ' – ' . $a['anlass'] : '' ),
		'text'     => (string) $o[ 're_text_' . $typ ],
		'schluss'  => (string) $o['re_schluss'],
		'a'        => $a, // feste Kopie der Angebotsdaten
	);
	$id = wp_insert_post( array( 'post_type' => 'kjo_rechnung', 'post_status' => 'private', 'post_title' => $nr . ' · ' . $a['name'] . ' · ' . KJO_RE_TYPEN[ $typ ] ) );
	if ( ! $id || is_wp_error( $id ) ) {
		return new WP_Error( 'kjo_re', 'Die Rechnung konnte nicht angelegt werden.', 500 );
	}
	update_post_meta( $id, '_kjo_re', $r );
	update_post_meta( $id, '_kjo_re_angebot', (int) $ang_id );
	update_post_meta( $id, '_kjo_re_status', $bez ? 'bezahlt' : 'offen' );
	update_post_meta( $id, '_kjo_re_betrag', $betrag );
	update_post_meta( $id, '_kjo_re_faellig', $r['faellig'] );
	if ( $bez ) {
		update_post_meta( $id, '_kjo_re_zahlung', array( 'datum' => $bam, 'art' => $bez ) );
	}
	update_post_meta( $id, '_kjo_re_token', bin2hex( random_bytes( 16 ) ) );
	kjo_re_log( $id, 'Angelegt (' . kjo_vl_eur( $betrag ) . ( $bez ? ', bereits ' . $bez . ' bezahlt' : ', fällig ' . wp_date( 'd.m.Y', strtotime( $r['faellig'] ) ) ) . ')' );
	kjo_ang_log( $ang_id, KJO_RE_TYPEN[ $typ ] . ' ' . $nr . ' erstellt (' . kjo_vl_eur( $betrag ) . ')' );
	kjo_re_angebot_status( $ang_id );
	return $id;
}
/** Stornorechnung: hebt eine Rechnung auf (eigene Nummer, negativer Betrag). */
function kjo_re_stornieren( $re_id ) {
	$r = kjo_re_get( $re_id );
	if ( ! $r || 'storno' === $r['typ'] || ! in_array( kjo_re_status( $re_id ), array( 'offen', 'bezahlt' ), true ) ) {
		return new WP_Error( 'kjo_re', 'Diese Rechnung kann nicht storniert werden.', 400 );
	}
	$ang = (int) get_post_meta( $re_id, '_kjo_re_angebot', true );
	if ( 'anzahlung' === $r['typ'] ) {
		foreach ( kjo_re_aktiv( $ang ) as $p ) {
			if ( 'rest' === kjo_re_get( $p->ID )['typ'] ) {
				return new WP_Error( 'kjo_re', 'Diese Anzahlung ist in der Schlussrechnung ' . kjo_re_get( $p->ID )['nr'] . ' verrechnet – bitte zuerst die Schlussrechnung stornieren.', 400 );
			}
		}
	}
	$o  = kjo_ang_opts();
	$nr = kjo_re_nr();
	$s  = array(
		'nr'       => $nr,
		'typ'      => 'storno',
		'datum'    => wp_date( 'Y-m-d' ),
		'ziel'     => 0,
		'faellig'  => wp_date( 'Y-m-d' ),
		'leistung' => $r['leistung'],
		'betrag'   => -1 * (float) $r['betrag'],
		'gesamt'   => -1 * (float) $r['betrag'],
		'abzuege'  => array(),
		'bezahlt'  => null,
		'ref'      => array( 'nr' => $r['nr'], 'datum' => $r['datum'], 'typ' => KJO_RE_TYPEN[ $r['typ'] ], 'bezahlt' => 'bezahlt' === kjo_re_status( $re_id ) ),
		'titel'    => 'Stornorechnung zu ' . KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'],
		'text'     => (string) $o['re_text_storno'],
		'schluss'  => '',
		'a'        => $r['a'],
	);
	$id = wp_insert_post( array( 'post_type' => 'kjo_rechnung', 'post_status' => 'private', 'post_title' => $nr . ' · ' . $r['a']['name'] . ' · Storno zu ' . $r['nr'] ) );
	if ( ! $id || is_wp_error( $id ) ) {
		return new WP_Error( 'kjo_re', 'Die Stornorechnung konnte nicht angelegt werden.', 500 );
	}
	update_post_meta( $id, '_kjo_re', $s );
	update_post_meta( $id, '_kjo_re_angebot', $ang );
	update_post_meta( $id, '_kjo_re_status', 'storno' );
	update_post_meta( $id, '_kjo_re_betrag', $s['betrag'] );
	update_post_meta( $id, '_kjo_re_faellig', $s['faellig'] );
	update_post_meta( $id, '_kjo_re_token', bin2hex( random_bytes( 16 ) ) );
	update_post_meta( $re_id, '_kjo_re_status', 'storniert' );
	update_post_meta( $re_id, '_kjo_re_storno', $id );
	kjo_re_log( $re_id, 'Storniert durch ' . $nr );
	kjo_re_log( $id, 'Storno zu ' . $r['nr'] );
	kjo_ang_log( $ang, KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'] . ' storniert (' . $nr . ')' );
	kjo_re_angebot_status( $ang );
	return $id;
}

/* ---------- Kunden-Link zum PDF ---------- */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/rechnung/(?P<t>[a-f0-9]{32})',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $q ) {
					$l = get_posts( array( 'post_type' => 'kjo_rechnung', 'post_status' => 'private', 'numberposts' => 1, 'meta_key' => '_kjo_re_token', 'meta_value' => $q['t'] ) ); // phpcs:ignore
					$f = $l ? kjo_re_dir() . basename( (string) get_post_meta( $l[0]->ID, '_kjo_re_pdf', true ) ) : '';
					if ( ! $l || ! get_post_meta( $l[0]->ID, '_kjo_re_pdf', true ) || ! is_readable( $f ) ) {
						return new WP_REST_Response( array( 'message' => 'Diese Rechnung ist nicht verfügbar.' ), 404 );
					}
					if ( ! get_post_meta( $l[0]->ID, '_kjo_re_gesehen', true ) ) {
						update_post_meta( $l[0]->ID, '_kjo_re_gesehen', time() );
						kjo_re_log( $l[0]->ID, 'PDF vom Kunden geöffnet' );
					}
					nocache_headers();
					header( 'Content-Type: application/pdf' );
					header( 'Content-Disposition: inline; filename="' . kjo_re_dateiname( kjo_re_get( $l[0]->ID ) ) . '"' );
					header( 'Content-Length: ' . filesize( $f ) );
					header( 'X-Robots-Tag: noindex, nofollow' );
					readfile( $f ); // phpcs:ignore
					exit;
				},
			)
		);
	}
);

/* ---------- AJAX (im Angebot) ---------- */
/** Prüft Nonce/Rechte über das Angebot und – falls übergeben – ob die Rechnung zu diesem Angebot gehört. */
function kjo_re_ajax_check( $mit_re = true ) {
	$ang = kjo_ang_ajax_check();
	if ( ! $mit_re ) {
		return array( $ang, 0 );
	}
	$re = isset( $_POST['re'] ) ? absint( $_POST['re'] ) : 0; // phpcs:ignore
	if ( ! $re || 'kjo_rechnung' !== get_post_type( $re ) || (int) get_post_meta( $re, '_kjo_re_angebot', true ) !== $ang ) {
		wp_send_json_error( array( 'message' => 'Rechnung nicht gefunden.' ), 404 );
	}
	return array( $ang, $re );
}
/** Daten, die der Browser für das PDF braucht. */
function kjo_re_pdf_daten( $re ) {
	$r              = kjo_re_get( $re );
	$a              = $r['a'];
	$a['rechnung']  = array_diff_key( $r, array( 'a' => 1 ) );
	return $a;
}
add_action(
	'wp_ajax_kjo_re_neu',
	function () {
		list( $ang ) = kjo_re_ajax_check( false );
		// Erst das Angebot so speichern, wie es gerade im Editor steht – die Rechnung nimmt genau diesen Stand.
		$d = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : null; // phpcs:ignore
		if ( is_array( $d ) ) {
			$a = kjo_ang_speichern( $ang, $d );
			wp_update_post( array( 'ID' => $ang, 'post_title' => kjo_ang_titel( $a ), 'post_status' => 'private' ) );
		}
		$in = array();
		foreach ( array( 'typ', 'datum', 'ziel', 'leistung', 'betrag', 'bezahlt', 'bezahlt_am' ) as $k ) {
			if ( isset( $_POST[ $k ] ) ) { // phpcs:ignore
				$in[ $k ] = sanitize_text_field( wp_unslash( $_POST[ $k ] ) ); // phpcs:ignore
			}
		}
		$id = kjo_re_anlegen( $ang, $in );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => $id->get_error_message() ), (int) $id->get_error_data() );
		}
		wp_send_json_success( array( 're' => $id, 'data' => kjo_re_pdf_daten( $id ) ) );
	}
);
add_action(
	'wp_ajax_kjo_re_daten',
	function () {
		list( , $re ) = kjo_re_ajax_check();
		wp_send_json_success( array( 're' => $re, 'data' => kjo_re_pdf_daten( $re ) ) );
	}
);
add_action(
	'wp_ajax_kjo_re_pdf',
	function () {
		list( , $re ) = kjo_re_ajax_check();
		$bin = base64_decode( preg_replace( '#^data:application/pdf;[^,]*base64,#', '', isset( $_POST['pdf'] ) ? (string) wp_unslash( $_POST['pdf'] ) : '' ), true ); // phpcs:ignore
		if ( ! $bin || 0 !== strpos( $bin, '%PDF' ) || strlen( $bin ) > 15 * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => 'Das PDF ist ungültig.' ), 400 );
		}
		$dir = kjo_re_dir();
		$alt = (string) get_post_meta( $re, '_kjo_re_pdf', true );
		$neu = 'rechnung-' . $re . '-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
		if ( false === file_put_contents( $dir . $neu, $bin ) ) { // phpcs:ignore
			wp_send_json_error( array( 'message' => 'Das PDF konnte nicht gespeichert werden (Schreibrechte im Upload-Ordner?).' ), 500 );
		}
		if ( $alt && $alt !== $neu ) { // altes PDF bleibt liegen (Aufbewahrung) – nur der Verweis wechselt
			$fr   = get_post_meta( $re, '_kjo_re_pdf_alt', true );
			$fr   = is_array( $fr ) ? $fr : array();
			$fr[] = $alt;
			update_post_meta( $re, '_kjo_re_pdf_alt', $fr );
		}
		update_post_meta( $re, '_kjo_re_pdf', $neu );
		kjo_re_log( $re, 'PDF erzeugt' );
		wp_send_json_success( array( 'link' => kjo_re_link( $re ) ) );
	}
);
add_action(
	'wp_ajax_kjo_re_mail',
	function () {
		list( $ang, $re ) = kjo_re_ajax_check();
		$r   = kjo_re_get( $re );
		$to  = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$sub = isset( $_POST['sub'] ) ? sanitize_text_field( wp_unslash( $_POST['sub'] ) ) : '';
		$txt = isset( $_POST['txt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['txt'] ) ) : '';
		$pdf = (string) get_post_meta( $re, '_kjo_re_pdf', true );
		if ( ! is_email( $to ) || ! $sub || ! $txt ) {
			wp_send_json_error( array( 'message' => 'Bitte Empfänger, Betreff und Text ausfüllen.' ), 400 );
		}
		if ( ! $pdf || ! is_readable( kjo_re_dir() . basename( $pdf ) ) ) {
			wp_send_json_error( array( 'message' => 'Das PDF fehlt – bitte „PDF neu erzeugen“.' ), 400 );
		}
		$tmp = trailingslashit( get_temp_dir() ) . 'kjo-' . wp_generate_password( 8, false ) . '/';
		wp_mkdir_p( $tmp );
		$att = $tmp . kjo_re_dateiname( $r );
		copy( kjo_re_dir() . basename( $pdf ), $att );
		$from = function_exists( 'kjo_vl_mail_to' ) ? kjo_vl_mail_to() : get_option( 'admin_email' );
		$ok   = wp_mail( $to, $sub, $txt, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: DJ KOLJA ONE <' . $from . '>', 'Bcc: ' . $from ), array( $att ) );
		@unlink( $att ); // phpcs:ignore
		@rmdir( $tmp ); // phpcs:ignore
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => 'Die E-Mail konnte nicht gesendet werden. Bitte später erneut versuchen oder per WhatsApp senden.' ), 500 );
		}
		update_post_meta( $re, '_kjo_re_versendet', time() );
		kjo_re_log( $re, 'Per E-Mail an ' . $to . ' gesendet' );
		kjo_ang_log( $ang, KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'] . ' per E-Mail gesendet' );
		kjo_ang_lead( $ang, '', KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'] . ' per E-Mail gesendet (' . kjo_vl_eur( (float) $r['betrag'] ) . ')' );
		wp_send_json_success( array( 'message' => 'Gesendet an ' . $to . ' (Kopie an dich).' ) );
	}
);
add_action(
	'wp_ajax_kjo_re_wa',
	function () {
		list( $ang, $re ) = kjo_re_ajax_check();
		$r = kjo_re_get( $re );
		update_post_meta( $re, '_kjo_re_versendet', time() );
		kjo_re_log( $re, 'Per WhatsApp gesendet' );
		kjo_ang_log( $ang, KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'] . ' per WhatsApp gesendet' );
		kjo_ang_lead( $ang, '', KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'] . ' per WhatsApp gesendet (' . kjo_vl_eur( (float) $r['betrag'] ) . ')' );
		wp_send_json_success();
	}
);
add_action(
	'wp_ajax_kjo_re_bezahlt',
	function () {
		list( $ang, $re ) = kjo_re_ajax_check();
		$r  = kjo_re_get( $re );
		$st = kjo_re_status( $re );
		if ( ! in_array( $st, array( 'offen', 'bezahlt' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Stornierte Rechnungen können nicht bezahlt werden.' ), 400 );
		}
		if ( ! empty( $_POST['zurueck'] ) ) { // phpcs:ignore
			update_post_meta( $re, '_kjo_re_status', 'offen' );
			delete_post_meta( $re, '_kjo_re_zahlung' );
			kjo_re_log( $re, 'Zahlung zurückgenommen – wieder offen' );
		} else {
			$art = isset( $_POST['art'] ) && 'bar' === $_POST['art'] ? 'bar' : 'Überweisung'; // phpcs:ignore
			$am  = isset( $_POST['am'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['am'] ) ? (string) $_POST['am'] : wp_date( 'Y-m-d' ); // phpcs:ignore
			update_post_meta( $re, '_kjo_re_status', 'bezahlt' );
			update_post_meta( $re, '_kjo_re_zahlung', array( 'datum' => $am, 'art' => $art ) );
			kjo_re_log( $re, 'Bezahlt am ' . wp_date( 'd.m.Y', strtotime( $am ) ) . ' (' . $art . ')' );
			kjo_ang_log( $ang, KJO_RE_TYPEN[ $r['typ'] ] . ' ' . $r['nr'] . ' bezahlt (' . $art . ')' );
		}
		kjo_re_angebot_status( $ang );
		wp_send_json_success();
	}
);
add_action(
	'wp_ajax_kjo_re_storno',
	function () {
		list( , $re ) = kjo_re_ajax_check();
		$id = kjo_re_stornieren( $re );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => $id->get_error_message() ), (int) $id->get_error_data() );
		}
		wp_send_json_success( array( 're' => $id, 'data' => kjo_re_pdf_daten( $id ) ) );
	}
);

/* ---------- Kasten „Rechnungen“ im Angebot ---------- */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_re_box', 'Rechnungen', 'kjo_re_box', 'kjo_angebot', 'normal', 'default' );
	}
);
function kjo_re_box( $post ) {
	$a   = kjo_ang_get( $post->ID );
	$o   = kjo_ang_opts();
	$mog = kjo_re_moeglich( $post->ID );
	$l   = kjo_re_liste( $post->ID );
	$bk  = kjo_ang_pdf_cfg()['bank'];
	echo '<div id="kjo-re" class="kjo-re">';
	if ( empty( $bk['IBAN'] ) ) {
		echo '<p class="notice notice-warning inline" style="padding:8px 12px">Unter <a href="' . esc_url( admin_url( 'edit.php?post_type=kjo_angebot&page=kjo-angebot-optionen' ) ) . '">Angebote → Einstellungen</a> ist noch keine IBAN eingetragen – ohne Bankverbindung steht auf der Rechnung keine Überweisungsangabe.</p>';
	}
	if ( ! $a['strasse'] || ! $a['plzort'] ) {
		echo '<p class="notice notice-warning inline" style="padding:8px 12px">Für eine Rechnung braucht es die vollständige Anschrift des Kunden (Straße, PLZ und Ort) – bitte oben im Angebot ergänzen.</p>';
	}
	if ( $l ) {
		echo '<table class="widefat striped kjo-re-t"><thead><tr><th>Nr.</th><th>Art</th><th>Datum</th><th style="text-align:right">Betrag</th><th>Status</th><th></th></tr></thead><tbody>';
		foreach ( $l as $p ) {
			$r  = kjo_re_get( $p->ID );
			$st = kjo_re_status( $p->ID );
			$z  = get_post_meta( $p->ID, '_kjo_re_zahlung', true );
			$tx = KJO_RE_STATI[ $st ];
			if ( 'bezahlt' === $st && is_array( $z ) ) {
				$tx = 'Bezahlt ' . wp_date( 'd.m.Y', strtotime( $z['datum'] ) ) . ' (' . $z['art'] . ')';
			} elseif ( 'offen' === $st ) {
				$tx = ( $r['faellig'] < wp_date( 'Y-m-d' ) ? '<span style="color:#b32d2e">Überfällig seit ' : 'Offen, fällig ' ) . wp_date( 'd.m.Y', strtotime( $r['faellig'] ) ) . ( $r['faellig'] < wp_date( 'Y-m-d' ) ? '</span>' : '' );
			}
			$pdf = (string) get_post_meta( $p->ID, '_kjo_re_pdf', true );
			$mail = array(
				'to'  => isset( $r['a']['email'] ) ? $r['a']['email'] : '',
				'sub' => kjo_re_platzhalter( $o['re_mail_betreff'], $p->ID ),
				'txt' => kjo_re_platzhalter( $o['re_mail_text'], $p->ID ),
				'wa'  => kjo_re_platzhalter( $o['re_wa_text'], $p->ID ),
				'tel' => isset( $r['a']['tel'] ) ? $r['a']['tel'] : '',
			);
			echo '<tr data-re="' . (int) $p->ID . '" data-mail="' . esc_attr( wp_json_encode( $mail ) ) . '"><td><strong>' . esc_html( $r['nr'] ) . '</strong></td><td>' . esc_html( KJO_RE_TYPEN[ $r['typ'] ] ) . '</td><td>' . esc_html( wp_date( 'd.m.Y', strtotime( $r['datum'] ) ) ) . '</td><td style="text-align:right">' . esc_html( kjo_vl_eur( (float) $r['betrag'] ) ) . '</td><td>' . wp_kses_post( $tx ) . ( get_post_meta( $p->ID, '_kjo_re_versendet', true ) ? '<br><span class="description">versendet</span>' : '' ) . '</td><td class="kjo-re-btns">';
			echo $pdf ? '<a class="button button-small" href="' . esc_url( kjo_re_link( $p->ID ) ) . '" target="_blank" rel="noopener">PDF</a> ' : '';
			echo '<button type="button" class="button button-small" data-re-a="pdf">' . ( $pdf ? 'PDF neu' : 'PDF erzeugen' ) . '</button> ';
			if ( in_array( $st, array( 'offen', 'bezahlt' ), true ) || 'storno' === $st ) {
				echo '<button type="button" class="button button-small" data-re-a="mail">E-Mail</button> <button type="button" class="button button-small" data-re-a="wa">WhatsApp</button> ';
			}
			if ( 'offen' === $st ) {
				echo '<button type="button" class="button button-small button-primary" data-re-a="bezahlt">Bezahlt …</button> ';
			} elseif ( 'bezahlt' === $st ) {
				echo '<button type="button" class="button-link" data-re-a="offen">wieder offen</button> ';
			}
			if ( in_array( $st, array( 'offen', 'bezahlt' ), true ) ) {
				echo '<button type="button" class="button-link kjo-ang-del" data-re-a="storno">stornieren</button>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<div id="kjo-re-mail" class="kjo-re-panel" hidden><p><label>An<br><input type="email" id="kjo-re-mail-to" class="regular-text"></label></p><p><label>Betreff<br><input id="kjo-re-mail-sub" class="large-text"></label></p><p><label>Text<br><textarea id="kjo-re-mail-txt" rows="9" class="large-text"></textarea></label></p><p><button type="button" class="button button-primary" id="kjo-re-mail-send">Jetzt senden (PDF im Anhang)</button> <button type="button" class="button-link" data-re-zu>abbrechen</button></p></div>';
		echo '<div id="kjo-re-bez" class="kjo-re-panel" hidden><p><strong>Zahlungseingang</strong></p><p><label>Am <input type="date" id="kjo-re-bez-am" value="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></label> <label><select id="kjo-re-bez-art"><option>Überweisung</option><option value="bar">bar</option></select></label> <button type="button" class="button button-primary" id="kjo-re-bez-ok">Als bezahlt speichern</button> <button type="button" class="button-link" data-re-zu>abbrechen</button></p></div>';
	} else {
		echo '<p class="description">Noch keine Rechnung zu diesem Angebot.</p>';
	}
	// Neue Rechnung
	$ziel = $a['firma'] ? (int) $o['re_ziel_firma'] : (int) $o['re_ziel_privat'];
	$von  = $a['von'] ? wp_date( 'd.m.Y', strtotime( $a['von'] ) ) . ( $a['bis'] && $a['bis'] !== $a['von'] ? ' – ' . wp_date( 'd.m.Y', strtotime( $a['bis'] ) ) : '' ) : '';
	echo '<h3 style="margin:18px 0 8px">Neue Rechnung</h3>';
	if ( ! $mog['komplett'] && ! $mog['anzahlung'] && ! $mog['rest'] ) {
		echo '<p class="description">Zu diesem Angebot gibt es bereits die komplette bzw. Schlussrechnung. Zum Korrigieren: stornieren und neu erstellen.</p>';
	} else {
		echo '<div class="kjo-re-neu" data-ziel-privat="' . (int) $o['re_ziel_privat'] . '" data-ziel-firma="' . (int) $o['re_ziel_firma'] . '">';
		echo '<p><label><input type="radio" name="kjo_re_typ" value="komplett"' . disabled( ! $mog['komplett'], true, false ) . checked( $mog['komplett'], true, false ) . '> Komplette Rechnung</label> &nbsp; ';
		echo '<label><input type="radio" name="kjo_re_typ" value="anzahlung"' . disabled( ! $mog['anzahlung'], true, false ) . checked( ! $mog['komplett'] && ! $mog['rest'] && $mog['anzahlung'], true, false ) . '> Anzahlungsrechnung</label> &nbsp; ';
		echo '<label><input type="radio" name="kjo_re_typ" value="rest"' . disabled( ! $mog['rest'], true, false ) . checked( $mog['rest'], true, false ) . '> Schlussrechnung (Rest nach Anzahlung)</label></p>';
		echo '<p class="kjo-re-anz"><label>Anzahlung <input id="kjo-re-betrag" inputmode="decimal" size="9" placeholder="z. B. 250"> €</label> <span class="description">oder</span> <button type="button" class="button button-small" data-re-proz="25">25 %</button> <button type="button" class="button button-small" data-re-proz="30">30 %</button> <button type="button" class="button button-small" data-re-proz="50">50 %</button> <span class="description" id="kjo-re-ges"></span></p>';
		echo '<p><label>Rechnungsdatum <input type="date" id="kjo-re-datum" value="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></label> &nbsp; <label>Leistungsdatum <input id="kjo-re-leistung" size="24" value="' . esc_attr( $von ) . '" placeholder="TT.MM.JJJJ"></label></p>';
		echo '<p><label><select id="kjo-re-kunde"><option value="privat"' . selected( ! $a['firma'], true, false ) . '>Privatkunde</option><option value="firma"' . selected( (bool) $a['firma'], true, false ) . '>Geschäftskunde</option></select></label> <label>Zahlungsziel <input type="number" id="kjo-re-ziel" value="' . (int) $ziel . '" min="0" max="90" style="width:64px"> Tage</label></p>';
		echo '<p><label><input type="checkbox" id="kjo-re-schon"> Bereits bezahlt</label> <span id="kjo-re-schon-w" hidden><select id="kjo-re-schon-art"><option value="bar">bar</option><option>Überweisung</option></select> am <input type="date" id="kjo-re-schon-am" value="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></span></p>';
		echo '<p><button type="button" class="button button-primary" id="kjo-re-erstellen">Rechnung erstellen</button> <span class="description">Nummer ' . esc_html( kjo_re_nr( false ) ) . ' · nimmt die Positionen und Preise, wie sie oben im Angebot stehen.</span></p>';
		echo '</div>';
	}
	echo '<p class="description" id="kjo-re-msg" role="status"></p></div>';
}

/* ---------- Liste „Rechnungen“ ---------- */
add_filter(
	'manage_kjo_rechnung_posts_columns',
	function () {
		return array( 'cb' => '<input type="checkbox">', 'title' => 'Rechnung', 're_typ' => 'Art', 're_datum' => 'Datum', 're_betrag' => 'Betrag', 're_status' => 'Status', 're_ang' => 'Angebot' );
	}
);
add_action(
	'manage_kjo_rechnung_posts_custom_column',
	function ( $c, $id ) {
		$r = kjo_re_get( $id );
		if ( ! $r ) {
			return;
		}
		$st = kjo_re_status( $id );
		if ( 're_typ' === $c ) {
			echo esc_html( KJO_RE_TYPEN[ $r['typ'] ] );
		} elseif ( 're_datum' === $c ) {
			echo esc_html( wp_date( 'd.m.Y', strtotime( $r['datum'] ) ) );
		} elseif ( 're_betrag' === $c ) {
			echo esc_html( kjo_vl_eur( (float) $r['betrag'] ) );
		} elseif ( 're_status' === $c ) {
			$z = get_post_meta( $id, '_kjo_re_zahlung', true );
			if ( 'offen' === $st && $r['faellig'] < wp_date( 'Y-m-d' ) ) {
				echo '<strong style="color:#b32d2e">Überfällig seit ' . esc_html( wp_date( 'd.m.Y', strtotime( $r['faellig'] ) ) ) . '</strong>';
			} elseif ( 'offen' === $st ) {
				echo 'Offen, fällig ' . esc_html( wp_date( 'd.m.Y', strtotime( $r['faellig'] ) ) );
			} elseif ( 'bezahlt' === $st && is_array( $z ) ) {
				echo esc_html( 'Bezahlt ' . wp_date( 'd.m.Y', strtotime( $z['datum'] ) ) . ' (' . $z['art'] . ')' );
			} else {
				echo esc_html( KJO_RE_STATI[ $st ] );
			}
		} elseif ( 're_ang' === $c ) {
			$a = (int) get_post_meta( $id, '_kjo_re_angebot', true );
			echo $a ? '<a href="' . esc_url( get_edit_post_link( $a ) ) . '">' . esc_html( kjo_ang_get( $a )['nr'] ) . '</a>' : '–';
		}
	},
	10,
	2
);
/* Klick auf eine Rechnung in der Liste führt zum Angebot (dort ist der Kasten „Rechnungen“). */
add_filter(
	'get_edit_post_link',
	function ( $link, $id ) {
		if ( 'kjo_rechnung' === get_post_type( $id ) ) {
			$a = (int) get_post_meta( $id, '_kjo_re_angebot', true );
			return $a ? admin_url( 'post.php?post=' . $a . '&action=edit#kjo_re_box' ) : $link;
		}
		return $link;
	},
	10,
	2
);
add_filter(
	'post_row_actions',
	function ( $acts, $post ) {
		if ( 'kjo_rechnung' !== $post->post_type ) {
			return $acts;
		}
		$out = array();
		if ( get_post_meta( $post->ID, '_kjo_re_pdf', true ) ) {
			$out['pdf'] = '<a href="' . esc_url( kjo_re_link( $post->ID ) ) . '" target="_blank" rel="noopener">PDF</a>';
		}
		$out['ang'] = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">zum Angebot</a>';
		return $out;
	},
	10,
	2
);
/* Summen über der Liste: offen, überfällig, bezahlt im Jahr. */
add_action(
	'admin_notices',
	function () {
		$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $s || 'edit-kjo_rechnung' !== $s->id ) {
			return;
		}
		$y      = wp_date( 'Y' );
		$offen  = 0;
		$ueber  = 0;
		$bez    = 0;
		$heute  = wp_date( 'Y-m-d' );
		foreach ( get_posts( array( 'post_type' => 'kjo_rechnung', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			$r  = kjo_re_get( $id );
			$st = kjo_re_status( $id );
			if ( 'offen' === $st ) {
				$offen += (float) $r['betrag'];
				if ( $r['faellig'] < $heute ) {
					$ueber += (float) $r['betrag'];
				}
			} elseif ( 'bezahlt' === $st ) {
				$z = get_post_meta( $id, '_kjo_re_zahlung', true );
				if ( is_array( $z ) && 0 === strpos( $z['datum'], $y ) ) {
					$bez += (float) $r['betrag'];
				}
			}
		}
		echo '<div class="notice notice-info"><p>Offen: <strong>' . esc_html( kjo_vl_eur( $offen ) ) . '</strong>' . ( $ueber ? ' · davon überfällig: <strong style="color:#b32d2e">' . esc_html( kjo_vl_eur( $ueber ) ) . '</strong>' : '' ) . ' · Bezahlt ' . esc_html( $y ) . ': <strong>' . esc_html( kjo_vl_eur( $bez ) ) . '</strong> · Nächste Nummer: ' . esc_html( kjo_re_nr( false ) ) . '</p></div>';
	}
);

/* ---------- Skript im Angebot ---------- */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$s = get_current_screen();
		if ( ! $s || 'kjo_angebot' !== $s->post_type || 'post.php' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'kjo-rechnung', plugins_url( 'assets/pdf/rechnung.js', __FILE__ ), array( 'kjo-angebot' ), KJO_VERSION, true );
	},
	20
);
