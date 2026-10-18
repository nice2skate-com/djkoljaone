<?php
/**
 * Angebote (seit 1.56.0): Miet- und DJ-Angebote im WordPress zusammenklicken, als PDF erzeugen und per E-Mail oder WhatsApp senden.
 *
 * - „Angebot erstellen“ in einer Mietanfrage bzw. DJ-Anfrage übernimmt Kunde, Termin, Ort, Gäste und (Miete) den Mietkorb
 *   mit den Preisen aus dem Verleih-Katalog. DJ-Positionen kommen aus der Vorlage unter „Angebote → Einstellungen“ –
 *   Preise, die dort nicht stehen, bleiben leer und werden im Angebot eingetragen (nichts wird geschätzt).
 * - Das PDF entsteht im Browser (jsPDF, Fira Sans), wird in wp-content/uploads/kjo-angebote/ gespeichert (von außen gesperrt)
 *   und ist für Kunden nur über einen zufälligen Link abrufbar (für WhatsApp).
 * - Versand: E-Mail mit PDF im Anhang oder WhatsApp mit Link. Die DJ-Anfrage bekommt Status „Angebot versendet“ und den Betrag.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_ANG_STATI = array(
	'entwurf'    => 'Entwurf',
	'versendet'  => 'Versendet',
	'angenommen' => 'Angenommen',
	'abgelehnt'  => 'Abgelehnt',
);
const KJO_ANG_TYPEN = array(
	'dj'    => 'DJ-Angebot',
	'miete' => 'Mietangebot',
);

/* ---------- Einstellungen ---------- */
function kjo_ang_opts() {
	$d = array(
		'gueltig'      => 14,
		'inhaber'      => '',
		'iban'         => '',
		'bic'          => '',
		'bank'         => '',
		'intro_dj'     => "vielen Dank für eure Anfrage. Sehr gern begleite ich euer Event als DJ – hier ist mein Angebot für euch.",
		'intro_miete'  => "vielen Dank für eure Mietanfrage. Hier ist mein Angebot für euren Zeitraum.",
		'bed_dj'       => '',
		'bed_miete'    => '',
		'schluss'      => "Ich freue mich auf eure Rückmeldung. Bei Fragen ruft mich gern an oder schreibt mir per WhatsApp.",
		'vorlage_dj'   => "DJ-Gage | pauschal | | \nAnfahrt | pauschal | | \nEquipment-Paket | pauschal | | \nVerlängerungsstunde | Std. | | optional",
		'mail_betreff' => 'Euer Angebot {nr} von DJ KOLJA ONE',
		'mail_text'    => "Hallo {name},\n\nvielen Dank für eure Anfrage! Im Anhang findet ihr mein Angebot {nr}.\n\nIch freue mich auf eure Rückmeldung – bei Fragen ruft mich gern an oder schreibt mir per WhatsApp.\n\nViele Grüße\nKolja – DJ KOLJA ONE\n+49 172 7273707\nhttps://dj-kolja-one.de",
		'wa_text'      => "Hallo {name}, hier ist euer Angebot {nr} von DJ KOLJA ONE als PDF: {link}\nBei Fragen meldet euch gern. Viele Grüße, Kolja",
	);
	$o = get_option( 'kjo_angebot', array() );
	return is_array( $o ) ? array_merge( $d, array_intersect_key( $o, $d ) ) : $d;
}
/** Vorlage „Titel | Einheit | Preis | optional“ → Positionen. Leerer Preis bleibt leer. */
function kjo_ang_vorlage( $txt ) {
	$out = array();
	foreach ( preg_split( '/\r?\n/', (string) $txt ) as $l ) {
		$f = array_map( 'trim', explode( '|', $l ) );
		if ( '' === $f[0] ) {
			continue;
		}
		$p     = isset( $f[2] ) && function_exists( 'kjo_vl_num' ) ? kjo_vl_num( $f[2] ) : null;
		$out[] = array( 't' => $f[0], 'i' => '', 'q' => 1, 'e' => isset( $f[1] ) ? $f[1] : '', 'p' => null === $p ? '' : $p, 'o' => isset( $f[3] ) && '' !== $f[3], 'k' => '' );
	}
	return $out;
}
/** Nächste Angebotsnummer (2026-001, 2026-002 …), zählt pro Jahr neu. */
function kjo_ang_nr( $vergeben = true ) {
	$y = (int) wp_date( 'Y' );
	$c = get_option( 'kjo_ang_nr', array() );
	$n = is_array( $c ) && isset( $c['y'], $c['n'] ) && (int) $c['y'] === $y ? (int) $c['n'] : 1;
	if ( $vergeben ) {
		update_option( 'kjo_ang_nr', array( 'y' => $y, 'n' => $n + 1 ), false );
	}
	return $y . '-' . str_pad( (string) $n, 3, '0', STR_PAD_LEFT );
}

/* ---------- Datentyp ---------- */
add_action(
	'init',
	function () {
		register_post_type(
			'kjo_angebot',
			array(
				'labels'          => array(
					'name'          => 'Angebote',
					'singular_name' => 'Angebot',
					'menu_name'     => 'Angebote',
					'all_items'     => 'Alle Angebote',
					'edit_item'     => 'Angebot bearbeiten',
					'not_found'     => 'Noch keine Angebote',
					'search_items'  => 'Angebote durchsuchen',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 27,
				'menu_icon'       => 'dashicons-media-document',
				'capability_type' => 'page',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
			)
		);
	}
);

/** Gespeicherte Angebotsdaten (immer vollständig). */
function kjo_ang_get( $id ) {
	$d = get_post_meta( $id, '_kjo_ang', true );
	return kjo_ang_clean( is_array( $d ) ? $d : array() );
}
function kjo_ang_clean( $d ) {
	$t   = function ( $k, $len = 200 ) use ( $d ) {
		return isset( $d[ $k ] ) && is_scalar( $d[ $k ] ) ? mb_substr( trim( sanitize_text_field( (string) $d[ $k ] ) ), 0, $len ) : '';
	};
	$ta  = function ( $k ) use ( $d ) {
		return isset( $d[ $k ] ) && is_scalar( $d[ $k ] ) ? mb_substr( trim( sanitize_textarea_field( (string) $d[ $k ] ) ), 0, 4000 ) : '';
	};
	$dat = function ( $k ) use ( $t ) {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $t( $k ) ) ? $t( $k ) : '';
	};
	$num = function ( $v ) {
		$n = function_exists( 'kjo_vl_num' ) ? kjo_vl_num( $v ) : ( is_numeric( $v ) ? (float) $v : null );
		return null === $n ? '' : round( $n, 2 );
	};
	$o = array(
		'typ'    => isset( $d['typ'] ) && isset( KJO_ANG_TYPEN[ $d['typ'] ] ) ? $d['typ'] : 'dj',
		'nr'     => $t( 'nr', 30 ),
		'datum'  => $dat( 'datum' ),
		'gueltig' => $dat( 'gueltig' ),
		'titel'  => $t( 'titel' ),
		'name'   => $t( 'name' ),
		'firma'  => $t( 'firma' ),
		'strasse' => $t( 'strasse' ),
		'plzort' => $t( 'plzort' ),
		'email'  => sanitize_email( $t( 'email' ) ),
		'tel'    => $t( 'tel', 40 ),
		'anlass' => $t( 'anlass' ),
		'von'    => $dat( 'von' ),
		'bis'    => $dat( 'bis' ),
		'zeit'   => $t( 'zeit', 60 ),
		'ort'    => $t( 'ort' ),
		'gaeste' => $t( 'gaeste', 40 ),
		'uebergabe' => $t( 'uebergabe' ),
		'intro'  => $ta( 'intro' ),
		'bed'    => $ta( 'bed' ),
		'schluss' => $ta( 'schluss' ),
		'rabatt' => isset( $d['rabatt'] ) ? $num( $d['rabatt'] ) : '',
		'rabatt_txt' => $t( 'rabatt_txt' ),
		'kaution' => isset( $d['kaution'] ) ? $num( $d['kaution'] ) : '',
		'pos'    => array(),
	);
	foreach ( isset( $d['pos'] ) && is_array( $d['pos'] ) ? array_slice( $d['pos'], 0, 60 ) : array() as $p ) {
		if ( ! is_array( $p ) ) {
			continue;
		}
		$p        = wp_parse_args( $p, array( 't' => '', 'i' => '', 'q' => 1, 'e' => '', 'p' => '', 'o' => false, 'k' => '' ) );
		$o['pos'][] = array(
			't' => mb_substr( sanitize_text_field( (string) $p['t'] ), 0, 200 ),
			'i' => mb_substr( sanitize_textarea_field( (string) $p['i'] ), 0, 600 ),
			'q' => max( 0, min( 9999, (float) str_replace( ',', '.', (string) $p['q'] ) ) ),
			'e' => mb_substr( sanitize_text_field( (string) $p['e'] ), 0, 30 ),
			'p' => $num( $p['p'] ),
			'o' => ! empty( $p['o'] ) && 'false' !== $p['o'],
			'k' => preg_match( '/^[gp]\d+$/', (string) $p['k'] ) ? (string) $p['k'] : '',
		);
	}
	return $o;
}
/** Summe der nicht optionalen Positionen abzüglich Rabatt. */
function kjo_ang_summe( $a ) {
	$s = 0;
	foreach ( $a['pos'] as $p ) {
		if ( ! $p['o'] && '' !== $p['p'] ) {
			$s += $p['q'] * $p['p'];
		}
	}
	return round( max( 0, $s - (float) $a['rabatt'] ), 2 );
}

/* ---------- Katalog (Pakete und Geräte aus dem Verleih) ---------- */
function kjo_ang_katalog() {
	$out = array();
	if ( ! function_exists( 'kjo_vl_items' ) ) {
		return $out;
	}
	foreach ( array( 'kjo_paket' => 'p', 'kjo_geraet' => 'g' ) as $type => $pre ) {
		foreach ( kjo_vl_items( $type ) as $p ) {
			$inh = array();
			if ( 'p' === $pre ) {
				foreach ( kjo_vl_paket_geraete( $p->ID ) as $gid => $n ) {
					$inh[] = $n . '× ' . get_the_title( $gid );
				}
			}
			$out[] = array(
				'k'  => $pre . $p->ID,
				't'  => $p->post_title,
				'p'  => kjo_vl_num( kjo_vl_get( $p->ID, 'preis' ) ),
				'we' => kjo_vl_we( $p->ID ),
				'ka' => kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) ),
				'i'  => $inh ? 'Enthält: ' . implode( ', ', $inh ) : '',
			);
		}
	}
	return $out;
}
/** Mietpreis eines Katalogeintrags für den Zeitraum (null = kein Preis eingetragen). */
function kjo_ang_mietpreis( $k, $von, $bis ) {
	$id = (int) substr( $k, 1 );
	$pr = kjo_vl_num( kjo_vl_get( $id, 'preis' ) );
	if ( null === $pr ) {
		return null;
	}
	$tf = $von ? kjo_vl_tarif( $von, $bis ) : null;
	return $tf ? $tf['we'] * kjo_vl_we( $id ) + $tf['d'] * $pr : $pr;
}

/* ---------- Anlegen aus einer Anfrage ---------- */
function kjo_ang_aus_text( $text, $label ) {
	return preg_match( '/^' . preg_quote( $label, '/' ) . ':\s*(.+)$/mu', (string) $text, $m ) ? trim( $m[1] ) : '';
}
function kjo_ang_prefill( $typ, $quelle ) {
	$o = kjo_ang_opts();
	$a = array(
		'typ'     => $typ,
		'nr'      => kjo_ang_nr(),
		'datum'   => wp_date( 'Y-m-d' ),
		'gueltig' => wp_date( 'Y-m-d', time() + (int) $o['gueltig'] * DAY_IN_SECONDS ),
		'intro'   => 'miete' === $typ ? $o['intro_miete'] : $o['intro_dj'],
		'bed'     => 'miete' === $typ ? $o['bed_miete'] : $o['bed_dj'],
		'schluss' => $o['schluss'],
		'pos'     => 'dj' === $typ ? kjo_ang_vorlage( $o['vorlage_dj'] ) : array(),
	);
	$q = $quelle ? get_post( $quelle ) : null;
	if ( $q && in_array( $q->post_type, array( 'kjo_anfrage', 'kjo_mietanfrage' ), true ) ) {
		$m    = function ( $k ) use ( $q ) {
			return trim( (string) get_post_meta( $q->ID, '_kjo_' . $k, true ) );
		};
		$text = $m( 'text' );
		$a['name']   = $m( 'name' );
		$a['email']  = $m( 'email' ) ? $m( 'email' ) : kjo_ang_aus_text( $text, 'E-Mail' );
		$a['tel']    = $m( 'tel' ) ? $m( 'tel' ) : kjo_ang_aus_text( $text, 'Telefon' );
		$a['anlass'] = $m( 'anlass' ) ? $m( 'anlass' ) : kjo_ang_aus_text( $text, 'Anlass' );
		$a['ort']    = $m( 'ort' ) ? $m( 'ort' ) : kjo_ang_aus_text( $text, 'kjo_anfrage' === $q->post_type ? 'Ort / Location' : 'Ort' );
		$a['gaeste'] = $m( 'gaeste' ) ? $m( 'gaeste' ) : kjo_ang_aus_text( $text, 'kjo_anfrage' === $q->post_type ? 'Gästezahl' : 'Gäste' );
		$a['von']    = $m( 'von' );
		$a['bis']    = 'kjo_mietanfrage' === $q->post_type ? $m( 'bis' ) : '';
		if ( 'kjo_mietanfrage' === $q->post_type ) {
			$a['uebergabe'] = $m( 'uebergabe' ) ? $m( 'uebergabe' ) : kjo_ang_aus_text( $text, 'Übergabe' );
		}
	}
	if ( $q && 'kjo_mietanfrage' === $q->post_type && 'miete' === $typ ) {
		$cart = get_post_meta( $q->ID, '_kjo_cart', true );
		if ( ! is_array( $cart ) || ! $cart ) { // ältere Anfragen: Positionen + Mengen aus dem Text
			$cart = array();
			foreach ( (array) get_post_meta( $q->ID, '_kjo_items', true ) as $k ) {
				$p          = get_post( (int) substr( (string) $k, 1 ) );
				$cart[ $k ] = $p && preg_match( '/^(?:- )?(\d+) × ' . preg_quote( $p->post_title, '/' ) . '/mu', $text, $mm ) ? (int) $mm[1] : 1;
			}
		}
		$kat = array();
		foreach ( kjo_ang_katalog() as $e ) {
			$kat[ $e['k'] ] = $e;
		}
		$kau = 0;
		foreach ( $cart as $k => $n ) {
			$id = (int) substr( (string) $k, 1 );
			if ( ! $id || ! get_post( $id ) ) {
				continue;
			}
			$pr         = kjo_ang_mietpreis( $k, $a['von'], $a['bis'] );
			$a['pos'][] = array( 't' => get_the_title( $id ), 'i' => isset( $kat[ $k ] ) ? $kat[ $k ]['i'] : '', 'q' => (int) $n, 'e' => 'Stk.', 'p' => null === $pr ? '' : $pr, 'o' => false, 'k' => $k );
			$ka         = kjo_vl_num( kjo_vl_get( $id, 'kaution' ) );
			$kau       += null === $ka ? 0 : $ka * (int) $n;
		}
		$a['kaution'] = $kau ? $kau : '';
		$u            = isset( $a['uebergabe'] ) ? $a['uebergabe'] : '';
		if ( false !== stripos( $u, 'Lieferung' ) ) {
			$a['pos'][] = array( 't' => 'Lieferung und Abholung', 'i' => '', 'q' => 1, 'e' => 'pauschal', 'p' => '', 'o' => false, 'k' => '' );
		}
		if ( false !== stripos( $u, 'Aufbau' ) ) {
			$a['pos'][] = array( 't' => 'Aufbau, Verkabelung und Abbau', 'i' => '', 'q' => 1, 'e' => 'pauschal', 'p' => '', 'o' => false, 'k' => '' );
		}
		$a['titel'] = 'Angebot Mietequipment';
	} else {
		$a['titel'] = 'dj' === $typ ? 'Angebot DJ' . ( ! empty( $a['anlass'] ) ? ' – ' . $a['anlass'] : '' ) : 'Angebot Mietequipment';
	}
	return kjo_ang_clean( $a );
}
add_action(
	'admin_post_kjo_ang_neu',
	function () {
		check_admin_referer( 'kjo_ang_neu' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'Keine Berechtigung.' );
		}
		$typ    = isset( $_GET['typ'] ) && 'miete' === $_GET['typ'] ? 'miete' : 'dj'; // phpcs:ignore
		$quelle = isset( $_GET['quelle'] ) ? absint( $_GET['quelle'] ) : 0; // phpcs:ignore
		$a      = kjo_ang_prefill( $typ, $quelle );
		$id     = wp_insert_post( array( 'post_type' => 'kjo_angebot', 'post_status' => 'private', 'post_title' => kjo_ang_titel( $a ) ) );
		if ( ! $id || is_wp_error( $id ) ) {
			wp_die( 'Das Angebot konnte nicht angelegt werden.' );
		}
		update_post_meta( $id, '_kjo_ang', $a );
		update_post_meta( $id, '_kjo_ang_status', 'entwurf' );
		update_post_meta( $id, '_kjo_ang_summe', kjo_ang_summe( $a ) );
		if ( $quelle ) {
			update_post_meta( $id, '_kjo_ang_quelle', $quelle );
			if ( 'kjo_anfrage' === get_post_type( $quelle ) && function_exists( 'kjo_lead_set' ) && in_array( kjo_lead_get( $quelle ), array( 'neu', 'klaerung' ), true ) ) {
				kjo_lead_set( $quelle, 'angebot' );
			}
		}
		kjo_ang_log( $id, 'Angelegt' . ( $quelle ? ' aus „' . get_the_title( $quelle ) . '“' : '' ) );
		wp_safe_redirect( admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) );
		exit;
	}
);
function kjo_ang_neu_url( $typ, $quelle = 0 ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=kjo_ang_neu&typ=' . $typ . ( $quelle ? '&quelle=' . (int) $quelle : '' ) ), 'kjo_ang_neu' );
}
function kjo_ang_titel( $a ) {
	return trim( $a['nr'] . ' · ' . ( $a['name'] ? $a['name'] : 'ohne Namen' ) . ' · ' . KJO_ANG_TYPEN[ $a['typ'] ] );
}
function kjo_ang_log( $id, $text ) {
	$l   = get_post_meta( $id, '_kjo_ang_log', true );
	$l   = is_array( $l ) ? $l : array();
	$l[] = array( time(), $text );
	update_post_meta( $id, '_kjo_ang_log', array_slice( $l, -50 ) );
}

/* ---------- Menü ---------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=kjo_angebot', 'Neues DJ-Angebot', 'Neues DJ-Angebot', 'edit_pages', 'kjo-ang-neu-dj', '__return_null' );
		add_submenu_page( 'edit.php?post_type=kjo_angebot', 'Neues Mietangebot', 'Neues Mietangebot', 'edit_pages', 'kjo-ang-neu-miete', '__return_null' );
		add_submenu_page( 'edit.php?post_type=kjo_angebot', 'Angebote – Einstellungen', 'Einstellungen', 'manage_options', 'kjo-angebot-optionen', 'kjo_ang_options_page' );
	}
);
/* Die beiden „Neu“-Einträge führen direkt zum Anlegen (mit Sicherheitsschlüssel). */
add_action(
	'admin_init',
	function () {
		$p = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore
		if ( in_array( $p, array( 'kjo-ang-neu-dj', 'kjo-ang-neu-miete' ), true ) && current_user_can( 'edit_pages' ) ) {
			wp_safe_redirect( html_entity_decode( kjo_ang_neu_url( 'kjo-ang-neu-miete' === $p ? 'miete' : 'dj' ) ) );
			exit;
		}
	}
);

function kjo_ang_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = kjo_ang_opts();
	if ( isset( $_POST['kjo_ang_opt_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_ang_opt_nonce'] ) ), 'kjo_ang_opt' ) ) {
		$v = isset( $_POST['o'] ) && is_array( $_POST['o'] ) ? wp_unslash( $_POST['o'] ) : array(); // phpcs:ignore
		foreach ( $o as $k => $alt ) {
			if ( ! isset( $v[ $k ] ) ) {
				continue;
			}
			$o[ $k ] = 'gueltig' === $k ? max( 1, min( 365, (int) $v[ $k ] ) ) : ( in_array( $k, array( 'intro_dj', 'intro_miete', 'bed_dj', 'bed_miete', 'schluss', 'vorlage_dj', 'mail_text', 'wa_text' ), true ) ? sanitize_textarea_field( $v[ $k ] ) : sanitize_text_field( $v[ $k ] ) );
		}
		update_option( 'kjo_angebot', $o, false );
		if ( isset( $_POST['naechste'] ) && preg_match( '/^\d{1,5}$/', sanitize_text_field( wp_unslash( $_POST['naechste'] ) ) ) ) {
			update_option( 'kjo_ang_nr', array( 'y' => (int) wp_date( 'Y' ), 'n' => max( 1, (int) $_POST['naechste'] ) ), false );
		}
		echo '<div class="notice notice-success"><p>Gespeichert.</p></div>';
	}
	$f = function ( $k, $label, $desc = '', $area = 0 ) use ( $o ) {
		echo '<tr><th><label for="kjo-ao-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( $area ) {
			echo '<textarea id="kjo-ao-' . esc_attr( $k ) . '" name="o[' . esc_attr( $k ) . ']" rows="' . (int) $area . '" class="large-text">' . esc_textarea( $o[ $k ] ) . '</textarea>';
		} else {
			echo '<input id="kjo-ao-' . esc_attr( $k ) . '" name="o[' . esc_attr( $k ) . ']" value="' . esc_attr( $o[ $k ] ) . '" class="regular-text">';
		}
		echo $desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : '';
		echo '</td></tr>';
	};
	echo '<div class="wrap"><h1>Angebote – Einstellungen</h1><form method="post">';
	wp_nonce_field( 'kjo_ang_opt', 'kjo_ang_opt_nonce' );
	echo '<h2>Nummer und Gültigkeit</h2><table class="form-table">';
	echo '<tr><th>Nächste Nummer</th><td>' . esc_html( wp_date( 'Y' ) ) . '-<input name="naechste" value="' . esc_attr( (int) substr( kjo_ang_nr( false ), 5 ) ) . '" size="5"><p class="description">Zählt jedes Jahr neu ab 001. Nur ändern, wenn du an eine bestehende Nummerierung anschließen willst.</p></td></tr>';
	echo '<tr><th>Gültig für</th><td><input type="number" name="o[gueltig]" value="' . (int) $o['gueltig'] . '" min="1" max="365"> Tage</td></tr></table>';
	echo '<h2>Bankverbindung (Fußzeile im PDF)</h2><p>Bleibt leer, bis du sie einträgst – dann steht sie unten auf jedem Angebot.</p><table class="form-table">';
	$f( 'inhaber', 'Kontoinhaber' );
	$f( 'bank', 'Bank' );
	$f( 'iban', 'IBAN' );
	$f( 'bic', 'BIC' );
	echo '</table><h2>Texte</h2><table class="form-table">';
	$f( 'intro_dj', 'Einleitung DJ-Angebot', 'Steht nach „Hallo …,“. Im Angebot änderbar.', 3 );
	$f( 'intro_miete', 'Einleitung Mietangebot', '', 3 );
	$f( 'bed_dj', 'Bedingungen DJ (Zahlung, Reservierung …)', 'Deine Zahlungsbedingungen und Reservierungsdauer – stehen nur im Angebot, nicht auf der Website.', 4 );
	$f( 'bed_miete', 'Bedingungen Miete (Zahlung, Kaution, Rückgabe …)', '', 4 );
	$f( 'schluss', 'Schlusstext', '', 2 );
	$f( 'vorlage_dj', 'Positionen DJ-Angebot', 'Eine Zeile je Position: Titel | Einheit | Preis | optional. Preis leer = trägst du im Angebot ein. „optional“ in der 4. Spalte = zubuchbar, zählt nicht zur Summe.', 5 );
	echo '</table><h2>Versand</h2><table class="form-table">';
	$f( 'mail_betreff', 'E-Mail-Betreff', 'Platzhalter: {name}, {nr}' );
	$f( 'mail_text', 'E-Mail-Text', 'Platzhalter: {name}, {nr}, {link}. Das PDF hängt an.', 8 );
	$f( 'wa_text', 'WhatsApp-Text', 'Platzhalter: {name}, {nr}, {link} (Link zum PDF).', 3 );
	echo '</table><p><button class="button button-primary">Speichern</button></p></form></div>';
}

/* ---------- Liste ---------- */
add_filter(
	'manage_kjo_angebot_posts_columns',
	function () {
		return array( 'cb' => '<input type="checkbox">', 'title' => 'Angebot', 'ang_termin' => 'Termin', 'ang_summe' => 'Summe', 'ang_status' => 'Status', 'date' => 'Erstellt' );
	}
);
add_action(
	'manage_kjo_angebot_posts_custom_column',
	function ( $c, $id ) {
		$a = kjo_ang_get( $id );
		if ( 'ang_termin' === $c ) {
			echo esc_html( $a['von'] ? wp_date( 'd.m.Y', strtotime( $a['von'] ) ) . ( $a['bis'] && $a['bis'] !== $a['von'] ? ' – ' . wp_date( 'd.m.Y', strtotime( $a['bis'] ) ) : '' ) : '–' );
		} elseif ( 'ang_summe' === $c ) {
			echo esc_html( kjo_vl_eur( (float) get_post_meta( $id, '_kjo_ang_summe', true ) ) );
		} elseif ( 'ang_status' === $c ) {
			$s = (string) get_post_meta( $id, '_kjo_ang_status', true );
			echo esc_html( KJO_ANG_STATI[ $s ] ?? 'Entwurf' );
		}
	},
	10,
	2
);

/* ---------- Bearbeiten ---------- */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_ang_editor', 'Angebot', 'kjo_ang_box', 'kjo_angebot', 'normal', 'high' );
		add_meta_box( 'kjo_ang_send', 'PDF und Versand', 'kjo_ang_box_send', 'kjo_angebot', 'side', 'high' );
		add_meta_box( 'kjo_ang_quelle', 'Angebote', 'kjo_ang_box_quelle', array( 'kjo_anfrage', 'kjo_mietanfrage' ), 'side', 'high' );
	}
);
add_action(
	'admin_head',
	function () {
		$s = get_current_screen();
		if ( $s && 'kjo_angebot' === $s->post_type && 'post' === $s->base ) {
			echo '<style>#post-body-content,#submitdiv .misc-pub-post-status,#submitdiv .misc-pub-visibility,#submitdiv #minor-publishing-actions{display:none}</style>';
		}
	}
);
function kjo_ang_box_quelle( $post ) {
	$l = get_posts( array( 'post_type' => 'kjo_angebot', 'post_status' => 'any', 'numberposts' => 20, 'meta_key' => '_kjo_ang_quelle', 'meta_value' => $post->ID ) ); // phpcs:ignore
	foreach ( $l as $p ) {
		$st = (string) get_post_meta( $p->ID, '_kjo_ang_status', true );
		echo '<p style="margin:0 0 6px"><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( kjo_ang_get( $p->ID )['nr'] ) . '</a> · ' . esc_html( kjo_vl_eur( (float) get_post_meta( $p->ID, '_kjo_ang_summe', true ) ) ) . ' · ' . esc_html( KJO_ANG_STATI[ $st ] ?? 'Entwurf' ) . '</p>';
	}
	if ( 'kjo_mietanfrage' === $post->post_type ) {
		echo '<p><a class="button button-primary" href="' . esc_url( kjo_ang_neu_url( 'miete', $post->ID ) ) . '">Mietangebot erstellen</a></p>';
	} else {
		echo '<p><a class="button button-primary" href="' . esc_url( kjo_ang_neu_url( 'dj', $post->ID ) ) . '">DJ-Angebot erstellen</a> <a class="button" href="' . esc_url( kjo_ang_neu_url( 'miete', $post->ID ) ) . '">Mietangebot</a></p>';
	}
	echo '<p class="description">Kunde, Termin, Ort und Gäste werden übernommen' . ( 'kjo_mietanfrage' === $post->post_type ? ', der Mietkorb mit den Katalogpreisen' : '' ) . '.</p>';
}

function kjo_ang_box( $post ) {
	$a = kjo_ang_get( $post->ID );
	wp_nonce_field( 'kjo_ang_save', 'kjo_ang_nonce' );
	echo '<input type="hidden" name="kjo_ang_json" id="kjo-ang-json" value="' . esc_attr( wp_json_encode( $a ) ) . '">';
	echo '<div id="kjo-ang" class="kjo-ang"><p>Wird geladen …</p></div>';
}
function kjo_ang_box_send( $post ) {
	$st  = (string) get_post_meta( $post->ID, '_kjo_ang_status', true );
	$pdf = (string) get_post_meta( $post->ID, '_kjo_ang_pdf', true );
	echo '<p><label for="kjo-ang-status"><strong>Status</strong></label><br><select name="kjo_ang_status" id="kjo-ang-status" style="width:100%">';
	foreach ( KJO_ANG_STATI as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $st ? $st : 'entwurf', $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></p>';
	echo '<p class="kjo-ang-sum" id="kjo-ang-sum"></p>';
	echo '<p><button type="button" class="button button-primary" id="kjo-ang-pdf" style="width:100%">PDF erzeugen und speichern</button></p>';
	echo '<p id="kjo-ang-pdflink">' . ( $pdf ? '<a href="' . esc_url( kjo_ang_link( $post->ID ) ) . '" target="_blank" rel="noopener">PDF ansehen</a>' : '<span class="description">Noch kein PDF erzeugt.</span>' ) . '</p>';
	echo '<hr><p><button type="button" class="button" id="kjo-ang-mail-open" style="width:100%">Per E-Mail senden …</button></p>';
	echo '<div id="kjo-ang-mail" hidden><p><label>An<br><input type="email" id="kjo-ang-mail-to" style="width:100%"></label></p><p><label>Betreff<br><input id="kjo-ang-mail-sub" style="width:100%"></label></p><p><label>Text<br><textarea id="kjo-ang-mail-txt" rows="9" style="width:100%"></textarea></label></p><p><button type="button" class="button button-primary" id="kjo-ang-mail-send">Jetzt senden (PDF im Anhang)</button></p></div>';
	echo '<p><button type="button" class="button" id="kjo-ang-wa" style="width:100%">Per WhatsApp senden</button></p>';
	echo '<p class="description" id="kjo-ang-msg" role="status"></p>';
	$log = get_post_meta( $post->ID, '_kjo_ang_log', true );
	if ( is_array( $log ) && $log ) {
		echo '<hr><p><strong>Verlauf</strong></p><ul style="margin:0;font-size:12px">';
		foreach ( array_reverse( $log ) as $e ) {
			echo '<li>' . esc_html( wp_date( 'd.m.y H:i', (int) $e[0] ) . ' – ' . $e[1] ) . '</li>';
		}
		echo '</ul>';
	}
	$q = (int) get_post_meta( $post->ID, '_kjo_ang_quelle', true );
	if ( $q && get_post( $q ) ) {
		echo '<p style="margin-top:10px"><a href="' . esc_url( get_edit_post_link( $q ) ) . '">← zur Anfrage</a></p>';
	}
}

/* Speichern über „Aktualisieren“: Titel aus Nummer, Name und Typ, immer privat. */
add_filter(
	'wp_insert_post_data',
	function ( $data, $raw ) {
		if ( 'kjo_angebot' !== $data['post_type'] || empty( $raw['kjo_ang_json'] ) || ! isset( $_POST['kjo_ang_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_ang_nonce'] ) ), 'kjo_ang_save' ) ) {
			return $data;
		}
		$a = json_decode( wp_unslash( $raw['kjo_ang_json'] ), true );
		if ( is_array( $a ) ) {
			$a                  = kjo_ang_clean( $a );
			$data['post_title'] = wp_slash( kjo_ang_titel( $a ) );
		}
		if ( 'trash' !== $data['post_status'] && 'auto-draft' !== $data['post_status'] ) {
			$data['post_status'] = 'private';
		}
		return $data;
	},
	10,
	2
);
add_action(
	'save_post_kjo_angebot',
	function ( $id ) {
		if ( ! isset( $_POST['kjo_ang_nonce'], $_POST['kjo_ang_json'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_ang_nonce'] ) ), 'kjo_ang_save' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) {
			return;
		}
		$a = json_decode( wp_unslash( $_POST['kjo_ang_json'] ), true ); // phpcs:ignore
		if ( is_array( $a ) ) {
			kjo_ang_speichern( $id, $a );
		}
		$st = isset( $_POST['kjo_ang_status'] ) ? sanitize_key( $_POST['kjo_ang_status'] ) : '';
		if ( isset( KJO_ANG_STATI[ $st ] ) && get_post_meta( $id, '_kjo_ang_status', true ) !== $st ) {
			update_post_meta( $id, '_kjo_ang_status', $st );
			kjo_ang_log( $id, 'Status: ' . KJO_ANG_STATI[ $st ] );
			kjo_ang_lead( $id, 'angenommen' === $st ? 'gebucht' : ( 'abgelehnt' === $st ? 'abg_kunde' : '' ), '' );
		}
	}
);
function kjo_ang_speichern( $id, $a ) {
	$alt = kjo_ang_get( $id );
	$a   = kjo_ang_clean( $a );
	if ( '' === $a['nr'] ) {
		$a['nr'] = $alt['nr'] ? $alt['nr'] : kjo_ang_nr();
	}
	update_post_meta( $id, '_kjo_ang', $a );
	update_post_meta( $id, '_kjo_ang_summe', kjo_ang_summe( $a ) );
	return $a;
}
/** DJ-Anfrage zum Angebot mitziehen: Status und Betrag. */
function kjo_ang_lead( $id, $status, $log ) {
	$q = (int) get_post_meta( $id, '_kjo_ang_quelle', true );
	if ( ! $q || 'kjo_anfrage' !== get_post_type( $q ) || ! function_exists( 'kjo_lead_set' ) ) {
		return;
	}
	$alt = kjo_lead_get( $q );
	if ( 'versendet' === $status && ! in_array( $alt, array( 'neu', 'klaerung', 'angebot' ), true ) ) {
		$status = '';
	}
	if ( $status ) {
		kjo_lead_set( $q, $status );
	}
	$s = (float) get_post_meta( $id, '_kjo_ang_summe', true );
	if ( $s > 0 ) {
		update_post_meta( $q, '_kjo_lead_betrag', rtrim( rtrim( number_format( $s, 2, '.', '' ), '0' ), '.' ) );
	}
	if ( $log && function_exists( 'kjo_lead_log' ) ) {
		kjo_lead_log( $q, $log );
	}
}

/* ---------- PDF-Ablage ---------- */
function kjo_ang_dir() {
	$u = wp_upload_dir();
	$d = trailingslashit( $u['basedir'] ) . 'kjo-angebote/';
	if ( wp_mkdir_p( $d ) && ! file_exists( $d . '.htaccess' ) ) {
		@file_put_contents( $d . '.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore
		@file_put_contents( $d . 'index.php', "<?php\n// Stille.\n" ); // phpcs:ignore
	}
	return $d;
}
function kjo_ang_link( $id ) {
	$t = (string) get_post_meta( $id, '_kjo_ang_token', true );
	return $t ? rest_url( 'kjo/v1/angebot/' . $t ) : '';
}
function kjo_ang_dateiname( $a ) {
	return 'Angebot-' . preg_replace( '/[^A-Za-z0-9-]/', '', $a['nr'] ) . '-DJ-KOLJA-ONE.pdf';
}
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/angebot/(?P<t>[a-f0-9]{32})',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) {
					$l = get_posts( array( 'post_type' => 'kjo_angebot', 'post_status' => 'private', 'numberposts' => 1, 'meta_key' => '_kjo_ang_token', 'meta_value' => $r['t'] ) ); // phpcs:ignore
					$f = $l ? kjo_ang_dir() . basename( (string) get_post_meta( $l[0]->ID, '_kjo_ang_pdf', true ) ) : '';
					if ( ! $l || ! get_post_meta( $l[0]->ID, '_kjo_ang_pdf', true ) || ! is_readable( $f ) ) {
						return new WP_REST_Response( array( 'message' => 'Dieses Angebot ist nicht mehr verfügbar.' ), 404 );
					}
					if ( ! get_post_meta( $l[0]->ID, '_kjo_ang_gesehen', true ) ) {
						update_post_meta( $l[0]->ID, '_kjo_ang_gesehen', time() );
						kjo_ang_log( $l[0]->ID, 'PDF vom Kunden geöffnet' );
					}
					nocache_headers();
					header( 'Content-Type: application/pdf' );
					header( 'Content-Disposition: inline; filename="' . kjo_ang_dateiname( kjo_ang_get( $l[0]->ID ) ) . '"' );
					header( 'Content-Length: ' . filesize( $f ) );
					header( 'X-Robots-Tag: noindex, nofollow' );
					readfile( $f ); // phpcs:ignore
					exit;
				},
			)
		);
	}
);

/* ---------- AJAX: PDF speichern, E-Mail, WhatsApp ---------- */
function kjo_ang_ajax_check() {
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	if ( ! check_ajax_referer( 'kjo_ang_ajax', 'nonce', false ) || ! $id || 'kjo_angebot' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error( array( 'message' => 'Keine Berechtigung – bitte Seite neu laden.' ), 403 );
	}
	return $id;
}
add_action(
	'wp_ajax_kjo_ang_save',
	function () {
		$id = kjo_ang_ajax_check();
		$a  = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : null; // phpcs:ignore
		if ( ! is_array( $a ) ) {
			wp_send_json_error( array( 'message' => 'Ungültige Daten.' ), 400 );
		}
		$a = kjo_ang_speichern( $id, $a );
		wp_update_post( array( 'ID' => $id, 'post_title' => kjo_ang_titel( $a ), 'post_status' => 'private' ) );
		wp_send_json_success( array( 'data' => $a, 'summe' => kjo_ang_summe( $a ) ) );
	}
);
add_action(
	'wp_ajax_kjo_ang_pdf',
	function () {
		$id  = kjo_ang_ajax_check();
		$pdf = isset( $_POST['pdf'] ) ? (string) wp_unslash( $_POST['pdf'] ) : ''; // phpcs:ignore
		$bin = base64_decode( preg_replace( '#^data:application/pdf;[^,]*base64,#', '', $pdf ), true );
		if ( ! $bin || 0 !== strpos( $bin, '%PDF' ) || strlen( $bin ) > 15 * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => 'Das PDF ist ungültig.' ), 400 );
		}
		$dir = kjo_ang_dir();
		$alt = (string) get_post_meta( $id, '_kjo_ang_pdf', true );
		$neu = 'angebot-' . $id . '-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
		if ( false === file_put_contents( $dir . $neu, $bin ) ) { // phpcs:ignore
			wp_send_json_error( array( 'message' => 'Das PDF konnte nicht gespeichert werden (Schreibrechte im Upload-Ordner?).' ), 500 );
		}
		if ( $alt && $alt !== $neu ) {
			@unlink( $dir . basename( $alt ) ); // phpcs:ignore
		}
		update_post_meta( $id, '_kjo_ang_pdf', $neu );
		update_post_meta( $id, '_kjo_ang_pdf_hash', md5( wp_json_encode( kjo_ang_get( $id ) ) ) );
		if ( ! get_post_meta( $id, '_kjo_ang_token', true ) ) {
			update_post_meta( $id, '_kjo_ang_token', bin2hex( random_bytes( 16 ) ) );
		}
		kjo_ang_log( $id, 'PDF erzeugt (' . kjo_vl_eur( (float) get_post_meta( $id, '_kjo_ang_summe', true ) ) . ')' );
		wp_send_json_success( array( 'link' => kjo_ang_link( $id ) ) );
	}
);
add_action(
	'wp_ajax_kjo_ang_mail',
	function () {
		$id  = kjo_ang_ajax_check();
		$a   = kjo_ang_get( $id );
		$to  = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$sub = isset( $_POST['sub'] ) ? sanitize_text_field( wp_unslash( $_POST['sub'] ) ) : '';
		$txt = isset( $_POST['txt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['txt'] ) ) : '';
		$pdf = (string) get_post_meta( $id, '_kjo_ang_pdf', true );
		if ( ! is_email( $to ) || ! $sub || ! $txt ) {
			wp_send_json_error( array( 'message' => 'Bitte Empfänger, Betreff und Text ausfüllen.' ), 400 );
		}
		if ( ! $pdf || ! is_readable( kjo_ang_dir() . basename( $pdf ) ) ) {
			wp_send_json_error( array( 'message' => 'Bitte zuerst das PDF erzeugen.' ), 400 );
		}
		// Anhang mit sprechendem Namen (der Dateiname im Ordner ist zufällig)
		$tmp = trailingslashit( get_temp_dir() ) . 'kjo-' . wp_generate_password( 8, false ) . '/';
		wp_mkdir_p( $tmp );
		$att = $tmp . kjo_ang_dateiname( $a );
		copy( kjo_ang_dir() . basename( $pdf ), $att );
		$from = function_exists( 'kjo_vl_mail_to' ) ? kjo_vl_mail_to() : get_option( 'admin_email' );
		$ok   = wp_mail( $to, $sub, $txt, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: DJ KOLJA ONE <' . $from . '>', 'Bcc: ' . $from ), array( $att ) );
		@unlink( $att ); // phpcs:ignore
		@rmdir( $tmp ); // phpcs:ignore
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => 'Die E-Mail konnte nicht gesendet werden. Bitte später erneut versuchen oder per WhatsApp senden.' ), 500 );
		}
		update_post_meta( $id, '_kjo_ang_status', 'versendet' );
		kjo_ang_log( $id, 'Per E-Mail an ' . $to . ' gesendet' );
		kjo_ang_lead( $id, 'versendet', 'Angebot ' . $a['nr'] . ' per E-Mail gesendet (' . kjo_vl_eur( kjo_ang_summe( $a ) ) . ')' );
		wp_send_json_success( array( 'message' => 'Gesendet an ' . $to . ' (Kopie an dich).' ) );
	}
);
add_action(
	'wp_ajax_kjo_ang_wa',
	function () {
		$id = kjo_ang_ajax_check();
		$a  = kjo_ang_get( $id );
		update_post_meta( $id, '_kjo_ang_status', 'versendet' );
		kjo_ang_log( $id, 'Per WhatsApp gesendet' );
		kjo_ang_lead( $id, 'versendet', 'Angebot ' . $a['nr'] . ' per WhatsApp gesendet (' . kjo_vl_eur( kjo_ang_summe( $a ) ) . ')' );
		wp_send_json_success();
	}
);

/* ---------- Skripte im Editor ---------- */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$s = get_current_screen();
		if ( ! $s || 'kjo_angebot' !== $s->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$v = KJO_VERSION;
		wp_enqueue_script( 'kjo-jspdf', plugins_url( 'assets/pdf/jspdf.umd.min.js', __FILE__ ), array(), '2.5.2', true );
		wp_enqueue_script( 'kjo-fira400', plugins_url( 'assets/pdf/fira400.js', __FILE__ ), array(), $v, true );
		wp_enqueue_script( 'kjo-fira600', plugins_url( 'assets/pdf/fira600.js', __FILE__ ), array(), $v, true );
		wp_enqueue_script( 'kjo-angebot', plugins_url( 'assets/pdf/angebot.js', __FILE__ ), array( 'kjo-jspdf', 'kjo-fira400', 'kjo-fira600' ), $v, true );
		wp_enqueue_style( 'kjo-angebot', plugins_url( 'assets/pdf/angebot.css', __FILE__ ), array(), $v );
		$o  = kjo_ang_opts();
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore
		wp_localize_script(
			'kjo-angebot',
			'KJO_ANG',
			array(
				'id'       => $id,
				'ajax'     => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'kjo_ang_ajax' ),
				'logo'     => plugins_url( 'assets/pdf/logo-dunkel.jpg', __FILE__ ),
				'katalog'  => kjo_ang_katalog(),
				'typen'    => KJO_ANG_TYPEN,
				'vorlage'  => kjo_ang_vorlage( $o['vorlage_dj'] ),
				'texte'    => array(
					'intro_dj'    => $o['intro_dj'],
					'intro_miete' => $o['intro_miete'],
					'bed_dj'      => $o['bed_dj'],
					'bed_miete'   => $o['bed_miete'],
				),
				'mail'     => array( 'sub' => $o['mail_betreff'], 'txt' => $o['mail_text'], 'wa' => $o['wa_text'] ),
				'bank'     => array_filter( array( 'Kontoinhaber' => $o['inhaber'], 'Bank' => $o['bank'], 'IBAN' => $o['iban'], 'BIC' => $o['bic'] ) ),
				'link'     => $id ? kjo_ang_link( $id ) : '',
				'pdf'      => $id ? (bool) get_post_meta( $id, '_kjo_ang_pdf', true ) : false,
				'aktuell'  => $id && get_post_meta( $id, '_kjo_ang_pdf', true ) && get_post_meta( $id, '_kjo_ang_pdf_hash', true ) === md5( wp_json_encode( kjo_ang_get( $id ) ) ),
				'lieferung' => function_exists( 'kjo_vl_opt' ) ? kjo_vl_opt( 'lieferung' ) : '',
				'aufbau'   => function_exists( 'kjo_vl_opt' ) ? kjo_vl_opt( 'aufbau' ) : '',
				'absender' => array(
					'marke'   => 'DJ KOLJA ONE',
					'name'    => 'Kolja Tönges',
					'firma'   => 'Artificial Sentiments (Einzelunternehmen), Inhaber Kolja Tönges',
					'strasse' => 'Pfarrer-Ritter-Weg 9',
					'ort'     => '87748 Fellheim',
					'tel'     => '+49 172 7273707',
					'mail'    => 'anfrage@dj-kolja-one.de',
					'web'     => 'dj-kolja-one.de',
					'ustid'   => defined( 'KJO_USTID' ) ? 'USt-IdNr. ' . KJO_USTID : '',
				),
			)
		);
	}
);
