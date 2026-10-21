<?php
/**
 * Kommentar → WhatsApp (seit 1.71.0) – wie ManyChat, ohne Fremddienst.
 *
 * 1. Jemand kommentiert ein Stichwort (z. B. „DJ“) unter einem Reel.
 * 2. Die Website antwortet öffentlich unter dem Kommentar und schickt eine Instagram-DM (Private Reply) mit einem
 *    wa.me-Link, in dem das Stichwort schon eingetippt ist.
 * 3. Die Person sendet das Stichwort auf WhatsApp → die Website antwortet automatisch mit dem Link und schickt,
 *    wenn hinterlegt, die Datei (MP3/PDF, z. B. aus Google Drive) direkt in den Chat.
 *
 * Instagram läuft über die Verbindung aus Social → Verbindung (Seiten-Zugriffsschlüssel, zusätzlich mit
 * instagram_manage_messages und pages_manage_metadata). WhatsApp über die WhatsApp Cloud API derselben Meta-App.
 * Meta meldet Kommentare und WhatsApp-Nachrichten an /wp-json/kjo/v1/meta-webhook.
 * Instagram gibt keine Telefonnummern heraus – deshalb der Umweg über die DM, wie bei ManyChat.
 * Einstellungen: Social → Kommentar → WhatsApp. Einrichtung: social/AUTO-DM.md im Repo.
 */

defined( 'ABSPATH' ) || exit;

function kjo_adm_opts() {
	$d = array(
		'aktiv'      => 0,
		'verify'     => '',
		'app_secret' => '',
		'wa_token'   => '',
		'wa_phone'   => '', // Telefonnummer-ID der WhatsApp Cloud API
		'wa_nummer'  => '', // dieselbe Nummer im Format 49170…, für den wa.me-Link
		'regeln'     => array(
			array(
				'worte'   => 'DJ',
				'antwort' => 'Check deine DMs 📩',
				'dm'      => "Hey @{name}! 🎧 Tipp auf den Link und schick die Nachricht ab – dann bekommst du sofort deinen Link auf WhatsApp:\n{whatsapp}",
				'wa'      => "Hey {name}! 🎶 Hier ist dein Download: {datei}\n\nViel Spaß beim Reinhören – Kolja",
				'datei'   => '',
				'name'    => '',
			),
		),
	);
	$o = get_option( 'kjo_autodm', array() );
	$o = is_array( $o ) ? array_merge( $d, array_intersect_key( $o, $d ) ) : $d;
	if ( '' === $o['verify'] ) { // Verifizierungstoken für Meta einmalig erzeugen
		$o['verify'] = wp_generate_password( 24, false );
		update_option( 'kjo_autodm', $o, false );
	}
	return $o;
}

function kjo_adm_log( $kanal, $ok, $info ) {
	$l   = get_option( 'kjo_autodm_log', array() );
	$l   = is_array( $l ) ? $l : array();
	$l[] = array( time(), $kanal, $ok, $info );
	update_option( 'kjo_autodm_log', array_slice( $l, -50 ), false );
}

/** Zähler pro Regel: kommentare, dm, wa. */
function kjo_adm_zaehlen( $wort, $feld ) {
	$s = get_option( 'kjo_autodm_stat', array() );
	$s = is_array( $s ) ? $s : array();
	$s[ $wort ][ $feld ] = ( $s[ $wort ][ $feld ] ?? 0 ) + 1;
	update_option( 'kjo_autodm_stat', $s, false );
}

/** Jedes Ereignis nur einmal bearbeiten – Meta schickt Webhooks bei Zeitüberschreitung erneut. */
function kjo_adm_neu( $id ) {
	$s = get_option( 'kjo_autodm_seen', array() );
	$s = is_array( $s ) ? $s : array();
	if ( isset( $s[ $id ] ) ) {
		return false;
	}
	$s[ $id ] = time();
	update_option( 'kjo_autodm_seen', array_slice( $s, -3000, null, true ), false );
	return true;
}

/** Erste Regel, deren Stichwort als ganzes Wort im Text vorkommt (Groß-/Kleinschreibung egal, „Adjust“ zählt nicht für „DJ“). */
function kjo_adm_regel( $text ) {
	foreach ( kjo_adm_opts()['regeln'] as $r ) {
		foreach ( array_filter( array_map( 'trim', explode( ',', $r['worte'] ) ) ) as $w ) {
			if ( preg_match( '/(?<![\p{L}\p{N}])' . preg_quote( $w, '/' ) . '(?![\p{L}\p{N}])/iu', (string) $text ) ) {
				return $r;
			}
		}
	}
	return null;
}
function kjo_adm_wort( $r ) {
	$w = array_filter( array_map( 'trim', explode( ',', $r['worte'] ) ) );
	return $w ? reset( $w ) : '';
}

/** wa.me-Link mit eingetipptem Stichwort. Ohne eigene Bot-Nummer: die normale WhatsApp-Nummer der Website (dann ohne automatische Antwort). */
function kjo_adm_wa_link( $r ) {
	$o = kjo_adm_opts();
	$n = $o['wa_nummer'] ? $o['wa_nummer'] : ( defined( 'KJO_WA' ) ? KJO_WA : '' );
	return 'https://wa.me/' . $n . '?text=' . rawurlencode( kjo_adm_wort( $r ) );
}

function kjo_adm_text( $t, $name, $r ) {
	$datei = ! empty( $r['datei'] ) ? $r['datei'] : home_url( '/meine-musik/?utm_source=instagram&utm_medium=whatsapp&utm_campaign=kommentar-' . rawurlencode( strtolower( kjo_adm_wort( $r ) ) ) );
	return trim( strtr( $t, array( '{name}' => $name, '{whatsapp}' => kjo_adm_wa_link( $r ), '{website}' => home_url( '/' ), '{datei}' => $datei ) ) );
}

/** Google-Drive-Freigabelink → direkter Download, damit WhatsApp die Datei selbst holen kann. Andere https-Links bleiben. */
function kjo_adm_download( $url ) {
	if ( preg_match( '#drive\.google\.com/(?:file/d/|open\?id=|uc\?(?:.*&)?id=)([\w-]{10,})#', (string) $url, $m ) ) {
		return 'https://drive.google.com/uc?export=download&id=' . $m[1];
	}
	return 0 === strpos( (string) $url, 'https://' ) ? $url : '';
}

/* ---------- Webhook ---------- */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/meta-webhook',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => 'kjo_adm_verify',
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true',
					'callback'            => 'kjo_adm_webhook',
				),
			)
		);
	}
);

/** Meta prüft die Adresse beim Einrichten: Token vergleichen und die Challenge unverändert (nicht als JSON) zurückgeben. */
function kjo_adm_verify( WP_REST_Request $req ) {
	$o = kjo_adm_opts();
	if ( 'subscribe' === $req->get_param( 'hub_mode' ) && hash_equals( $o['verify'], (string) $req->get_param( 'hub_verify_token' ) ) ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $req->get_param( 'hub_challenge' ) ); // phpcs:ignore
		exit;
	}
	return new WP_REST_Response( 'forbidden', 403 );
}

function kjo_adm_webhook( WP_REST_Request $req ) {
	$o   = kjo_adm_opts();
	$raw = $req->get_body();
	$sig = (string) $req->get_header( 'x-hub-signature-256' );
	if ( ! $o['app_secret'] || ! hash_equals( 'sha256=' . hash_hmac( 'sha256', $raw, $o['app_secret'] ), $sig ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 401 );
	}
	$p = json_decode( $raw, true );
	if ( ! $o['aktiv'] || ! is_array( $p ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	foreach ( (array) ( $p['entry'] ?? array() ) as $e ) {
		foreach ( (array) ( $e['changes'] ?? array() ) as $c ) {
			$f = $c['field'] ?? '';
			$v = (array) ( $c['value'] ?? array() );
			if ( 'instagram' === ( $p['object'] ?? '' ) && 'comments' === $f ) {
				kjo_adm_kommentar( $v );
			} elseif ( 'whatsapp_business_account' === ( $p['object'] ?? '' ) && 'messages' === $f ) {
				foreach ( (array) ( $v['messages'] ?? array() ) as $m ) {
					kjo_adm_whatsapp( $m, (array) ( $v['contacts'] ?? array() ) );
				}
			}
		}
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* ---------- Instagram: Kommentar → öffentliche Antwort + DM ---------- */
function kjo_adm_kommentar( $c ) {
	$id   = (string) ( $c['id'] ?? '' );
	$from = (array) ( $c['from'] ?? array() );
	$user = sanitize_text_field( (string) ( $from['username'] ?? '' ) );
	$soc  = kjo_soc_opts();
	if ( '' === $id || ( $soc['ig_id'] && (string) ( $from['id'] ?? '' ) === (string) $soc['ig_id'] ) || ( $soc['ig_name'] && $user === $soc['ig_name'] ) ) {
		return; // eigene Antworten nicht beantworten
	}
	$r = kjo_adm_regel( $c['text'] ?? '' );
	if ( ! $r || ! kjo_adm_neu( 'ig:' . $id ) ) {
		return;
	}
	$wort = kjo_adm_wort( $r );
	kjo_adm_zaehlen( $wort, 'kommentare' );

	// Private Reply: genau eine DM pro Kommentar, bis 7 Tage danach.
	$dm = kjo_soc_api(
		rawurlencode( $soc['page_id'] ) . '/messages',
		array(
			'recipient' => wp_json_encode( array( 'comment_id' => $id ) ),
			'message'   => wp_json_encode( array( 'text' => kjo_adm_text( $r['dm'], $user, $r ) ) ),
		)
	);
	if ( is_wp_error( $dm ) ) {
		kjo_adm_log( 'Instagram-DM', false, '@' . $user . ': ' . $dm->get_error_message() );
	} else {
		kjo_adm_zaehlen( $wort, 'dm' );
		kjo_adm_log( 'Instagram-DM', true, '@' . $user . ' hat „' . $wort . '“ kommentiert' );
	}
	if ( '' !== trim( $r['antwort'] ) ) {
		$a = kjo_soc_api( rawurlencode( $id ) . '/replies', array( 'message' => kjo_adm_text( $r['antwort'], $user, $r ) ) );
		if ( is_wp_error( $a ) ) {
			kjo_adm_log( 'Kommentar-Antwort', false, '@' . $user . ': ' . $a->get_error_message() );
		}
	}
}

/* ---------- WhatsApp: Stichwort → Antwort mit Link ---------- */
function kjo_adm_whatsapp( $m, $contacts ) {
	if ( 'text' !== ( $m['type'] ?? '' ) ) {
		return;
	}
	$r = kjo_adm_regel( $m['text']['body'] ?? '' );
	if ( ! $r || ! kjo_adm_neu( 'wa:' . ( $m['id'] ?? '' ) ) ) {
		return;
	}
	$von  = preg_replace( '/\D/', '', (string) ( $m['from'] ?? '' ) );
	$name = '';
	foreach ( $contacts as $k ) {
		if ( ( $k['wa_id'] ?? '' ) === $von ) {
			$name = sanitize_text_field( (string) ( $k['profile']['name'] ?? '' ) );
		}
	}
	$res  = kjo_adm_wa_senden( $von, preg_replace( '/ +([!,.])/u', '$1', kjo_adm_text( $r['wa'], $name, $r ) ) );
	$wort = kjo_adm_wort( $r );
	$kurz = substr( $von, 0, 5 ) . '…' . substr( $von, -2 ); // Nummer im Verlauf nur gekürzt
	if ( is_wp_error( $res ) ) {
		kjo_adm_log( 'WhatsApp', false, $kurz . ': ' . $res->get_error_message() );
		return;
	}
	kjo_adm_zaehlen( $wort, 'wa' );
	kjo_adm_log( 'WhatsApp', true, $kurz . ' hat „' . $wort . '“ geschrieben' );
	// Zusätzlich die Datei selbst in den Chat – der Link steht schon in der Nachricht davor, falls WhatsApp sie nicht laden kann.
	$dl = kjo_adm_download( $r['datei'] ?? '' );
	if ( $dl ) {
		$f   = trim( (string) ( $r['name'] ?? '' ) );
		$doc = kjo_adm_wa_api(
			rawurlencode( kjo_adm_opts()['wa_phone'] ) . '/messages',
			array(
				'messaging_product' => 'whatsapp',
				'to'                => $von,
				'type'              => 'document',
				'document'          => array( 'link' => $dl, 'filename' => '' !== $f ? $f : 'DJ KOLJA ONE – ' . $wort ),
			)
		);
		if ( is_wp_error( $doc ) ) {
			kjo_adm_log( 'WhatsApp-Datei', false, $kurz . ': ' . $doc->get_error_message() );
		}
	}
}

function kjo_adm_wa_api( $pfad, $body = null ) {
	$o = kjo_adm_opts();
	if ( ! $o['wa_token'] || ! $o['wa_phone'] ) {
		return new WP_Error( 'kjo_adm', 'WhatsApp ist noch nicht eingerichtet.' );
	}
	$url  = 'https://graph.facebook.com/' . rawurlencode( kjo_soc_opts()['version'] ) . '/' . $pfad;
	$args = array( 'timeout' => 20, 'headers' => array( 'Authorization' => 'Bearer ' . $o['wa_token'], 'Content-Type' => 'application/json' ) );
	return kjo_soc_antwort( null === $body ? wp_remote_get( $url, $args ) : wp_remote_post( $url, $args + array( 'body' => wp_json_encode( $body ) ) ) );
}
function kjo_adm_wa_senden( $an, $text ) {
	return kjo_adm_wa_api(
		rawurlencode( kjo_adm_opts()['wa_phone'] ) . '/messages',
		array(
			'messaging_product' => 'whatsapp',
			'to'                => $an,
			'type'              => 'text',
			'text'              => array( 'preview_url' => true, 'body' => $text ),
		)
	);
}

/* ---------- Admin: Social → Kommentar → WhatsApp ---------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'kjo-social', 'Kommentar → WhatsApp', 'Kommentar → WhatsApp', 'manage_options', 'kjo-autodm', 'kjo_adm_page' );
	},
	21
);

function kjo_adm_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o   = kjo_adm_opts();
	$soc = kjo_soc_opts();
	$msg = array();
	if ( isset( $_POST['kjo_adm_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_adm_nonce'] ) ), 'kjo_adm' ) ) {
		$tun = sanitize_key( wp_unslash( $_POST['tun'] ?? 'speichern' ) ); // phpcs:ignore
		if ( 'speichern' === $tun ) {
			$o['aktiv'] = empty( $_POST['aktiv'] ) ? 0 : 1;
			foreach ( array( 'app_secret', 'wa_token' ) as $k ) { // Geheimnisse: leer lassen = behalten
				$v = trim( sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) ); // phpcs:ignore
				if ( '' !== $v ) {
					$o[ $k ] = $v;
				}
			}
			$o['wa_phone']  = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['wa_phone'] ?? '' ) ) ); // phpcs:ignore
			$n              = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['wa_nummer'] ?? '' ) ) ); // phpcs:ignore
			$o['wa_nummer'] = preg_replace( '/^0(?=[1-9])/', '49', preg_replace( '/^00/', '', $n ) ); // 0170… / 0049170… → 49170…
			$o['regeln']    = array();
			foreach ( (array) ( $_POST['regeln'] ?? array() ) as $i => $r ) { // phpcs:ignore
				$r = array(
					'worte'   => sanitize_text_field( wp_unslash( $r['worte'] ?? '' ) ),
					'antwort' => sanitize_text_field( wp_unslash( $r['antwort'] ?? '' ) ),
					'dm'      => sanitize_textarea_field( wp_unslash( $r['dm'] ?? '' ) ),
					'wa'      => sanitize_textarea_field( wp_unslash( $r['wa'] ?? '' ) ),
					'datei'   => esc_url_raw( trim( wp_unslash( $r['datei'] ?? '' ) ), array( 'https' ) ),
					'name'    => sanitize_file_name( wp_unslash( $r['name'] ?? '' ) ),
				);
				if ( '' !== trim( $r['worte'] ) && empty( $_POST['weg'][ $i ] ) ) { // phpcs:ignore
					$o['regeln'][] = $r;
				}
			}
			update_option( 'kjo_autodm', $o, false );
			$msg[] = array( true, 'Gespeichert.' );
		} elseif ( 'pruefen' === $tun ) {
			$ig    = kjo_soc_api( rawurlencode( $soc['page_id'] ), null, array( 'fields' => 'name,instagram_business_account{id,username}' ) );
			$msg[] = is_wp_error( $ig ) ? array( false, 'Instagram: ' . $ig->get_error_message() ) : array( ! empty( $ig['instagram_business_account'] ), ! empty( $ig['instagram_business_account'] ) ? 'Instagram: verbunden mit @' . $ig['instagram_business_account']['username'] : 'Instagram: Die Facebook-Seite ist mit keinem Instagram-Profi-Konto verknüpft.' );
			if ( ! is_wp_error( $ig ) && ! empty( $ig['instagram_business_account'] ) ) {
				$soc['ig_id']   = $ig['instagram_business_account']['id'];
				$soc['ig_name'] = $ig['instagram_business_account']['username'];
				update_option( 'kjo_social', $soc, false );
			}
			$sub   = kjo_soc_api( rawurlencode( $soc['page_id'] ) . '/subscribed_apps', array( 'subscribed_fields' => 'feed' ) );
			$msg[] = is_wp_error( $sub ) ? array( false, 'Seiten-Abo: ' . $sub->get_error_message() . ' – braucht pages_manage_metadata im Seitenschlüssel.' ) : array( true, 'Seiten-Abo: App ist für die Seite angemeldet.' );
			$wa    = kjo_adm_wa_api( rawurlencode( $o['wa_phone'] ) . '?fields=display_phone_number,verified_name' );
			$msg[] = is_wp_error( $wa ) ? array( false, 'WhatsApp: ' . $wa->get_error_message() ) : array( true, 'WhatsApp: verbunden als ' . ( $wa['verified_name'] ?? '' ) . ' (' . ( $wa['display_phone_number'] ?? '' ) . ')' );
		} elseif ( 'test' === $tun ) {
			$t     = sanitize_text_field( wp_unslash( $_POST['testtext'] ?? '' ) ); // phpcs:ignore
			$r     = kjo_adm_regel( $t );
			$msg[] = $r
				? array( true, 'Regel „' . kjo_adm_wort( $r ) . '“ greift. Die DM würde lauten:<br><code style="white-space:pre-wrap">' . esc_html( kjo_adm_text( $r['dm'], 'testname', $r ) ) . '</code><br>Die WhatsApp-Antwort:<br><code style="white-space:pre-wrap">' . esc_html( kjo_adm_text( $r['wa'], 'Testname', $r ) ) . '</code>' )
				: array( false, 'Kein Stichwort gefunden in „' . esc_html( $t ) . '“ – der Bot würde nicht antworten.' );
		}
	}
	$ok  = '<span style="color:#008a20">✔</span> ';
	$no  = '<span style="color:#b32d2e">✘</span> ';
	$url = rest_url( 'kjo/v1/meta-webhook' );
	echo '<div class="wrap"><h1>Kommentar → WhatsApp</h1>';
	foreach ( $msg as $m ) {
		echo '<div class="notice notice-' . ( $m[0] ? 'success' : 'error' ) . '"><p>' . wp_kses_post( $m[1] ) . '</p></div>';
	}
	echo '<p>Wie ManyChat: Wer ein Stichwort unter deinen Reels kommentiert, bekommt eine Instagram-DM mit WhatsApp-Link. Schickt die Person das Stichwort auf WhatsApp, antwortet die Website automatisch mit deinem Link. Einrichtung Schritt für Schritt: <code>social/AUTO-DM.md</code> im Repo.</p>';
	echo '<ul style="line-height:1.9">';
	echo '<li>' . ( $o['aktiv'] ? $ok . 'eingeschaltet' : $no . 'ausgeschaltet' ) . '</li>';
	echo '<li>' . ( $soc['token'] && $soc['page_id'] ? $ok . 'Instagram-Verbindung aus Social' . ( $soc['ig_name'] ? ' (@' . esc_html( $soc['ig_name'] ) . ')' : '' ) : $no . 'Instagram: erst unter Social → Verbindung einrichten' ) . '</li>';
	echo '<li>' . ( $o['app_secret'] ? $ok . 'App-Geheimcode gespeichert' : $no . 'App-Geheimcode fehlt – ohne ihn nimmt die Website keine Meldungen von Meta an' ) . '</li>';
	echo '<li>' . ( $o['wa_token'] && $o['wa_phone'] ? $ok . 'WhatsApp Cloud API eingerichtet' : $no . 'WhatsApp Cloud API fehlt – der DM-Link führt dann zu deiner normalen Nummer ' . esc_html( defined( 'KJO_WA' ) ? KJO_WA : '' ) . ' (ohne automatische Antwort)' ) . '</li>';
	echo '</ul>';
	echo '<h2>Für die Meta-App (Webhooks)</h2><table class="form-table">';
	echo '<tr><th>Callback-URL</th><td><input readonly value="' . esc_attr( $url ) . '" class="large-text" onclick="this.select()"></td></tr>';
	echo '<tr><th>Verifizierungstoken</th><td><input readonly value="' . esc_attr( $o['verify'] ) . '" class="regular-text" onclick="this.select()"></td></tr>';
	echo '</table>';

	echo '<form method="post">';
	wp_nonce_field( 'kjo_adm', 'kjo_adm_nonce' );
	echo '<h2>Einstellungen</h2><table class="form-table">';
	echo '<tr><th>Status</th><td><label><input type="checkbox" name="aktiv" value="1"' . checked( $o['aktiv'], 1, false ) . '> Automatische Antworten einschalten</label></td></tr>';
	echo '<tr><th><label for="kjo-adm-secret">App-Geheimcode</label></th><td><input id="kjo-adm-secret" name="app_secret" type="password" autocomplete="off" class="regular-text" placeholder="' . esc_attr( $o['app_secret'] ? 'gespeichert – leer lassen zum Behalten' : 'Meta-App → App-Einstellungen → Allgemein' ) . '"><p class="description">Damit prüft die Website, dass Meldungen wirklich von Meta kommen.</p></td></tr>';
	echo '<tr><th><label for="kjo-adm-watoken">WhatsApp-Zugriffsschlüssel</label></th><td><input id="kjo-adm-watoken" name="wa_token" type="password" autocomplete="off" class="regular-text" placeholder="' . esc_attr( $o['wa_token'] ? 'gespeichert – leer lassen zum Behalten' : 'dauerhafter Systemnutzer-Schlüssel' ) . '"></td></tr>';
	echo '<tr><th><label for="kjo-adm-waphone">Telefonnummer-ID</label></th><td><input id="kjo-adm-waphone" name="wa_phone" value="' . esc_attr( $o['wa_phone'] ) . '" class="regular-text" inputmode="numeric"><p class="description">Meta-App → WhatsApp → API-Einrichtung → „Telefonnummer-ID“ (nicht die Telefonnummer).</p></td></tr>';
	echo '<tr><th><label for="kjo-adm-wanr">WhatsApp-Nummer des Bots</label></th><td><input id="kjo-adm-wanr" name="wa_nummer" value="' . esc_attr( $o['wa_nummer'] ) . '" class="regular-text" inputmode="tel" placeholder="z. B. 491701234567"><p class="description">Die Nummer, die bei der Cloud API hinterlegt ist. Daraus entsteht der Link in der DM.</p></td></tr>';
	echo '</table>';

	echo '<h2>Stichwörter</h2><p>Platzhalter: <code>{name}</code> (Instagram-Name bzw. WhatsApp-Name), <code>{whatsapp}</code> (Link zu WhatsApp mit eingetipptem Stichwort), <code>{website}</code> (' . esc_html( home_url( '/' ) ) . '), <code>{datei}</code> (dein Datei-Link; leer = Meine Musik). Mehrere Stichwörter pro Regel mit Komma trennen; das erste wird in WhatsApp vorausgefüllt. Groß-/Kleinschreibung ist egal.</p>';
	$regeln   = $o['regeln'];
	$regeln[] = array( 'worte' => '', 'antwort' => '', 'dm' => '', 'wa' => '', 'datei' => '', 'name' => '' ); // leere Zeile für eine neue Regel
	foreach ( $regeln as $i => $r ) {
		$n = 'regeln[' . $i . ']';
		echo '<fieldset style="background:#fff;border:1px solid #c3c4c7;padding:4px 16px;margin:0 0 16px;max-width:900px"><table class="form-table" style="margin:0">';
		echo '<tr><th>' . ( '' === $r['worte'] ? 'Neues Stichwort' : 'Stichwort' ) . '</th><td><input name="' . esc_attr( $n ) . '[worte]" value="' . esc_attr( $r['worte'] ) . '" class="regular-text" placeholder="z. B. DJ, MIX">' . ( '' !== $r['worte'] ? ' <label><input type="checkbox" name="weg[' . (int) $i . ']" value="1"> löschen</label>' : '' ) . '</td></tr>';
		echo '<tr><th>Öffentliche Antwort</th><td><input name="' . esc_attr( $n ) . '[antwort]" value="' . esc_attr( $r['antwort'] ) . '" class="large-text" placeholder="leer = keine"><p class="description">Erscheint unter dem Kommentar. Das Stichwort selbst hier nicht verwenden.</p></td></tr>';
		echo '<tr><th>Instagram-DM</th><td><textarea name="' . esc_attr( $n ) . '[dm]" rows="3" class="large-text">' . esc_textarea( $r['dm'] ) . '</textarea></td></tr>';
		echo '<tr><th>WhatsApp-Antwort</th><td><textarea name="' . esc_attr( $n ) . '[wa]" rows="3" class="large-text">' . esc_textarea( $r['wa'] ) . '</textarea></td></tr>';
		echo '<tr><th>Datei (MP3/PDF)</th><td><input name="' . esc_attr( $n ) . '[datei]" value="' . esc_attr( $r['datei'] ?? '' ) . '" class="large-text" placeholder="https://drive.google.com/file/d/…/view?usp=sharing"> <input name="' . esc_attr( $n ) . '[name]" value="' . esc_attr( $r['name'] ?? '' ) . '" class="regular-text" placeholder="Dateiname, z. B. Sommer-Mix-2026.mp3"><p class="description">Google Drive: Datei → Teilen → „Jeder mit dem Link“ → Link kopieren. Steht als <code>{datei}</code> in der Antwort und kommt zusätzlich als Datei in den Chat (bis 100 MB; größere Dateien nur als Link).</p></td></tr>';
		echo '</table></fieldset>';
	}
	echo '<p><button class="button button-primary" name="tun" value="speichern">Speichern</button> <button class="button" name="tun" value="pruefen">Verbindung prüfen &amp; Seite anmelden</button></p>';
	echo '<h2>Ausprobieren</h2><p><input name="testtext" class="regular-text" placeholder="Kommentar, z. B. „Ich will DJ 🔥“"> <button class="button" name="tun" value="test">Testen (sendet nichts)</button></p>';
	echo '</form>';

	$s = get_option( 'kjo_autodm_stat', array() );
	if ( is_array( $s ) && $s ) {
		echo '<h2>Zahlen</h2><table class="widefat striped" style="max-width:600px"><thead><tr><th>Stichwort</th><th>Kommentare</th><th>Instagram-DMs</th><th>WhatsApp-Antworten</th></tr></thead><tbody>';
		foreach ( $s as $w => $z ) {
			echo '<tr><td>' . esc_html( $w ) . '</td><td>' . (int) ( $z['kommentare'] ?? 0 ) . '</td><td>' . (int) ( $z['dm'] ?? 0 ) . '</td><td>' . (int) ( $z['wa'] ?? 0 ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	$log = get_option( 'kjo_autodm_log', array() );
	if ( is_array( $log ) && $log ) {
		echo '<h2>Verlauf</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>Zeit</th><th>Kanal</th><th>Ergebnis</th></tr></thead><tbody>';
		foreach ( array_reverse( $log ) as $e ) {
			echo '<tr><td>' . esc_html( wp_date( 'd.m.Y H:i', (int) $e[0] ) ) . '</td><td>' . esc_html( $e[1] ) . '</td><td>' . ( $e[2] ? $ok : $no ) . esc_html( $e[3] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}
