<?php
/**
 * Angebote aus Telegram/n8n (seit 1.75.0).
 *
 * Wenn du im Telegram-Bot auf „Senden“ tippst, legt n8n hier ein DJ-Angebot an – mit allen finalen Angaben
 * (km, Änderungen). Ein kleiner PDF-Dienst auf deinem Server baut daraus mit demselben Code wie der Editor
 * (assets/pdf/angebot-pdf.js) das PDF; es wird hier abgelegt und hängt an der E-Mail an den Kunden.
 * Das Angebot steht danach ganz normal unter „Angebote“ – mit Nummer, PDF-Link, Verlauf und Öffnen-Hinweis.
 *
 * Zugang nur mit Schlüssel (Kopfzeile X-KJO-Key). Den Schlüssel erzeugst du unter Angebote → Automatik;
 * gespeichert wird nur sein Fingerabdruck (SHA-256), nicht der Schlüssel selbst.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Schlüssel ---------- */
function kjo_auto_key_ok( WP_REST_Request $r ) {
	$soll = (string) get_option( 'kjo_ang_auto_key', '' );
	$ist  = (string) $r->get_header( 'x_kjo_key' );
	if ( '' === $soll || strlen( $ist ) < 32 || ! hash_equals( $soll, hash( 'sha256', $ist ) ) ) {
		return new WP_Error( 'kjo_auto', 'Kein Zugang.', array( 'status' => 401 ) );
	}
	return true;
}

/* ---------- Schnittstelle ---------- */
add_action(
	'rest_api_init',
	function () {
		$ns = 'kjo/v1';
		register_rest_route(
			$ns,
			'/auto/ping',
			array(
				'methods'             => 'GET',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function () {
					return array( 'ok' => true, 'version' => KJO_VERSION );
				},
			)
		);
		// Angebot anlegen oder (gleiche Referenz, noch nicht versendet) aktualisieren
		register_rest_route(
			$ns,
			'/auto/angebot',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => 'kjo_auto_angebot',
			)
		);
		register_rest_route(
			$ns,
			'/auto/angebot/(?P<id>\d+)/pdf',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function ( WP_REST_Request $r ) {
					$id = kjo_auto_id( $r );
					if ( is_wp_error( $id ) ) {
						return $id;
					}
					$link = kjo_ang_pdf_ablegen( $id, (string) $r->get_param( 'pdf' ) );
					if ( is_wp_error( $link ) ) {
						return new WP_Error( 'kjo_auto', $link->get_error_message(), array( 'status' => (int) $link->get_error_data() ) );
					}
					return array( 'id' => $id, 'link' => $link, 'datei' => kjo_ang_dateiname( kjo_ang_get( $id ) ) );
				},
			)
		);
		register_rest_route(
			$ns,
			'/auto/angebot/(?P<id>\d+)/versendet',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'kjo_auto_key_ok',
				'callback'            => function ( WP_REST_Request $r ) {
					$id = kjo_auto_id( $r );
					if ( is_wp_error( $id ) ) {
						return $id;
					}
					$a  = kjo_ang_get( $id );
					$to = sanitize_email( (string) $r->get_param( 'to' ) );
					update_post_meta( $id, '_kjo_ang_status', 'versendet' );
					kjo_ang_log( $id, 'Per E-Mail' . ( $to ? ' an ' . $to : '' ) . ' gesendet (Telegram)' );
					kjo_ang_lead( $id, 'versendet', 'Angebot ' . $a['nr'] . ' per E-Mail gesendet (' . kjo_vl_eur( kjo_ang_summe( $a ) ) . ')' );
					return array( 'id' => $id, 'status' => 'versendet' );
				},
			)
		);
	}
);
function kjo_auto_id( WP_REST_Request $r ) {
	$id = absint( $r['id'] );
	if ( ! $id || 'kjo_angebot' !== get_post_type( $id ) || ! get_post_meta( $id, '_kjo_ang_ref', true ) ) {
		return new WP_Error( 'kjo_auto', 'Angebot nicht gefunden.', array( 'status' => 404 ) ); // nur Angebote, die über die Automatik entstanden sind
	}
	return $id;
}

/** Felder, die n8n setzen darf. Alles andere (Nummer, Datum, Gültigkeit, Bedingungen) kommt wie gewohnt aus den Einstellungen. */
const KJO_AUTO_FELDER = array( 'titel', 'name', 'firma', 'strasse', 'plzort', 'email', 'tel', 'anlass', 'von', 'zeit', 'ort', 'gaeste', 'gruss', 'sie', 'intro', 'schluss', 'pos', 'rabatt', 'rabatt_txt', 'ust', 'ust_brutto' );

function kjo_auto_angebot( WP_REST_Request $r ) {
	$ref = preg_replace( '/[^A-Za-z0-9-]/', '', (string) $r->get_param( 'ref' ) );
	$in  = $r->get_param( 'data' );
	if ( strlen( $ref ) < 6 || ! is_array( $in ) ) {
		return new WP_Error( 'kjo_auto', 'Referenz oder Daten fehlen.', array( 'status' => 400 ) );
	}
	$neu = array_intersect_key( $in, array_flip( KJO_AUTO_FELDER ) );
	if ( empty( $neu['name'] ) || empty( $neu['pos'] ) || ! is_array( $neu['pos'] ) ) {
		return new WP_Error( 'kjo_auto', 'Name und mindestens eine Position sind nötig.', array( 'status' => 400 ) );
	}
	$alt = get_posts( array( 'post_type' => 'kjo_angebot', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_kjo_ang_ref', 'meta_value' => $ref ) ); // phpcs:ignore
	if ( $alt ) {
		$id = $alt[0]->ID;
		if ( 'versendet' === get_post_meta( $id, '_kjo_ang_status', true ) || in_array( get_post_meta( $id, '_kjo_ang_status', true ), array( 'angenommen', 'abgelehnt' ), true ) ) {
			return new WP_Error( 'kjo_auto', 'Angebot ' . kjo_ang_get( $id )['nr'] . ' ist schon versendet.', array( 'status' => 409 ) );
		}
		$a = kjo_ang_speichern( $id, array_merge( kjo_ang_get( $id ), $neu ) );
		wp_update_post( array( 'ID' => $id, 'post_title' => kjo_ang_titel( $a ), 'post_status' => 'private' ) );
		kjo_ang_log( $id, 'Aktualisiert (Telegram)' );
	} else {
		$quelle = kjo_auto_quelle( isset( $neu['email'] ) ? (string) $neu['email'] : '' );
		$a      = kjo_ang_prefill( 'dj', 0 ); // Nummer, Datum, Gültigkeit, Bedingungen, Schlusstext aus den Einstellungen
		$a      = kjo_ang_clean( array_merge( $a, $neu ) );
		$id     = wp_insert_post( array( 'post_type' => 'kjo_angebot', 'post_status' => 'private', 'post_title' => kjo_ang_titel( $a ) ) );
		if ( ! $id || is_wp_error( $id ) ) {
			return new WP_Error( 'kjo_auto', 'Das Angebot konnte nicht angelegt werden.', array( 'status' => 500 ) );
		}
		update_post_meta( $id, '_kjo_ang', $a );
		update_post_meta( $id, '_kjo_ang_status', 'entwurf' );
		update_post_meta( $id, '_kjo_ang_summe', kjo_ang_summe( $a ) );
		update_post_meta( $id, '_kjo_ang_ref', $ref );
		if ( $quelle ) {
			update_post_meta( $id, '_kjo_ang_quelle', $quelle );
			if ( function_exists( 'kjo_lead_set' ) && in_array( kjo_lead_get( $quelle ), array( 'neu', 'klaerung' ), true ) ) {
				kjo_lead_set( $quelle, 'angebot' );
			}
		}
		kjo_ang_log( $id, 'Angelegt über Telegram' . ( $quelle ? ' zu „' . get_the_title( $quelle ) . '“' : '' ) );
	}
	return array(
		'id'   => $id,
		'nr'   => $a['nr'],
		'data' => $a,
		'cfg'  => kjo_ang_pdf_cfg(), // für den PDF-Dienst: Logo, Absender, Bank
		'edit' => admin_url( 'post.php?post=' . (int) $id . '&action=edit' ),
	);
}
/** Passende DJ-Anfrage aus dem Website-Formular (gleiche E-Mail, letzte 60 Tage), damit Anfrage und Angebot verknüpft sind. */
function kjo_auto_quelle( $email ) {
	$email = sanitize_email( $email );
	if ( ! $email ) {
		return 0;
	}
	$l = get_posts(
		array(
			'post_type'   => 'kjo_anfrage',
			'post_status' => 'any',
			'numberposts' => 1,
			'meta_key'    => '_kjo_email', // phpcs:ignore
			'meta_value'  => $email, // phpcs:ignore
			'date_query'  => array( array( 'after' => '60 days ago' ) ),
		)
	);
	return $l ? (int) $l[0]->ID : 0;
}

/* ---------- Angebote → Automatik: Schlüssel erzeugen ---------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=kjo_angebot', 'Angebote – Automatik', 'Automatik', 'manage_options', 'kjo-angebot-auto', 'kjo_auto_seite' );
	},
	20
);
function kjo_auto_seite() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$schluessel = '';
	if ( isset( $_POST['kjo_auto_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_auto_nonce'] ) ), 'kjo_auto' ) ) {
		if ( isset( $_POST['weg'] ) ) {
			delete_option( 'kjo_ang_auto_key' );
			echo '<div class="notice notice-success"><p>Schlüssel gelöscht – die Automatik hat keinen Zugang mehr.</p></div>';
		} else {
			$schluessel = bin2hex( random_bytes( 24 ) );
			update_option( 'kjo_ang_auto_key', hash( 'sha256', $schluessel ), false );
		}
	}
	$aktiv = (bool) get_option( 'kjo_ang_auto_key', '' );
	echo '<div class="wrap"><h1>Angebote – Automatik (Telegram/n8n)</h1>';
	echo '<p>Mit diesem Schlüssel darf n8n DJ-Angebote anlegen, das PDF ablegen und den Status „Versendet“ setzen. Sonst nichts.</p>';
	echo '<p>Status: <strong>' . ( $aktiv ? 'Schlüssel vorhanden' : 'kein Schlüssel – Automatik aus' ) . '</strong></p>';
	if ( $schluessel ) {
		echo '<div class="notice notice-warning" style="padding:12px"><p><strong>Dein neuer Schlüssel – wird nur jetzt angezeigt:</strong></p>';
		echo '<p><input type="text" readonly value="' . esc_attr( $schluessel ) . '" style="width:100%;font-family:monospace" onclick="this.select()"></p>';
		echo '<p>Trage ihn in n8n in die Zugangsdaten „Plattenkiste Automatik“ ein (Feld <code>X-KJO-Key</code>). Nicht per Chat oder E-Mail verschicken. Ein alter Schlüssel gilt ab jetzt nicht mehr.</p></div>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'kjo_auto', 'kjo_auto_nonce' );
	echo '<p><button class="button button-primary">' . ( $aktiv ? 'Neuen Schlüssel erzeugen' : 'Schlüssel erzeugen' ) . '</button> ';
	if ( $aktiv ) {
		echo '<button class="button" name="weg" value="1" onclick="return confirm(\'Automatik wirklich ausschalten?\')">Schlüssel löschen</button>';
	}
	echo '</p></form></div>';
}
