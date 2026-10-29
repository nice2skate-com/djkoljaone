<?php
/**
 * Erster Kommentar bei Song-Releases (seit 1.69.0)
 *
 * Alle 15 Minuten (WP-Cron) prüft die Website, ob ein geplanter Song (Spalte „Release“ in der Plattenkiste) inzwischen
 * als Reel auf Instagram erschienen ist: Bildunterschrift enthält den Songtitel. Dann schreibt sie den ersten Kommentar
 * aus der Vorlage, merkt sich die Kommentar-ID am Song (kjm_ig_kommentar) und hakt den Song mit dem echten
 * Veröffentlichungsdatum als „erl.“ ab. Jeder Song bekommt höchstens einen Kommentar.
 *
 * Braucht im Seiten-Zugriffsschlüssel (Social → Verbindung) zusätzlich das Recht „instagram_manage_comments“.
 * Kurzlink für Kommentare: https://dj-kolja-one.de/song/<ID> → Meine Musik mit dem Song auf Deck A (utm_medium=kommentar).
 * Einstellungen: Social → Song-Kommentar.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Einmalig (1.69.2): Release-Zeit ist jetzt 18 Uhr – gespeicherte Kommentarvorlage anpassen. */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'kjo_kom_18uhr' ) ) {
			return;
		}
		update_option( 'kjo_kom_18uhr', 1, false );
		$o = get_option( 'kjo_social_kommentar', array() );
		if ( is_array( $o ) && ! empty( $o['text'] ) ) {
			$o['text'] = str_replace( 'um 19 Uhr', 'um 18 Uhr', $o['text'] );
			update_option( 'kjo_social_kommentar', $o, false );
		}
	}
);

function kjo_kom_opts() {
	$d = array(
		'aktiv' => 0,
		'zeit'  => '18:10', // frühestens um diese Uhrzeit am Release-Tag (seit 1.69.1)
		'text'  => "🎧 „{titel}“ ganz hören, selbst mixen und bewerten – im DJ-Pult auf meiner Website.\n👉 Direkt zum Song: {kurzlink}\n🔗 Oder einfach über den Link in meiner Bio.\n\nJeden Dienstag um 18 Uhr kommt ein neuer Song. Folg mir, damit du keinen verpasst. 🔔",
	);
	$o = get_option( 'kjo_social_kommentar', array() );
	return is_array( $o ) ? array_merge( $d, array_intersect_key( $o, $d ) ) : $d;
}

/* Kurzlink /song/<ID> – lässt sich aus einem Kommentar leicht abtippen. */
add_action(
	'init',
	function () {
		$p = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore
		if ( ! preg_match( '#^/song/(\d+)/?$#', $p, $m ) ) {
			return;
		}
		$id = (int) $m[1];
		if ( ! function_exists( 'kjm_is_audio' ) || ! kjm_is_audio( get_post( $id ) ) || ! kjm_visible( $id ) ) {
			wp_safe_redirect( home_url( '/meine-musik/' ), 302 );
			exit;
		}
		wp_safe_redirect( home_url( '/meine-musik/?song=' . $id . '&utm_source=instagram&utm_medium=kommentar&utm_campaign=song-' . $id ), 302 );
		exit;
	},
	0
);

function kjo_kom_text( $id ) {
	$t = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	return strtr(
		kjo_kom_opts()['text'],
		array(
			'{titel}'    => $t,
			'{kurzlink}' => preg_replace( '#^https?://#', '', home_url( '/song/' . $id ) ),
			'{link}'     => home_url( '/meine-musik/?song=' . $id . '&utm_source=instagram&utm_medium=kommentar&utm_campaign=song-' . $id ),
		)
	);
}

/* Songs, die jetzt einen Kommentar brauchen: noch nicht erledigt, noch kein Kommentar, Release-Datum von vor 3 bis in 6 Tagen. */
function kjo_kom_kandidaten() {
	$von   = wp_date( 'Y-m-d', time() - 3 * DAY_IN_SECONDS );
	$bis   = wp_date( 'Y-m-d', time() + 6 * DAY_IN_SECONDS );
	$out   = array();
	foreach ( function_exists( 'kjm_pult_ids' ) ? kjm_pult_ids() : array() as $id ) {
		$d = (string) get_post_meta( $id, 'kjm_release', true );
		if ( $d && $d >= $von && $d <= $bis && ! get_post_meta( $id, 'kjm_release_erl', true ) && ! get_post_meta( $id, 'kjm_ig_kommentar', true ) ) {
			$out[] = $id;
		}
	}
	return $out;
}

define( 'KJO_KOM_VERSUCHE', 6 );            // so oft wird es versucht …
define( 'KJO_KOM_ABSTAND', 20 * MINUTE_IN_SECONDS ); // … im Abstand von 20 Minuten (18:10, 18:30 … 19:50)

/* Nächster Zeitpunkt für einen Versuch: Release-Datum + Uhrzeit, nach einem Fehlversuch 20 Minuten später. Nach 6 Versuchen: nie. */
function kjo_kom_faellig( $id ) {
	if ( (int) get_post_meta( $id, 'kjm_ig_versuche', true ) >= KJO_KOM_VERSUCHE ) {
		return PHP_INT_MAX;
	}
	$d = (string) get_post_meta( $id, 'kjm_release', true );
	try {
		$t = ( new DateTimeImmutable( $d . ' ' . kjo_kom_opts()['zeit'], wp_timezone() ) )->getTimestamp();
	} catch ( Exception $e ) {
		return PHP_INT_MAX;
	}
	$l = (int) get_post_meta( $id, 'kjm_ig_versuch_ts', true );
	return $l ? max( $t, $l + KJO_KOM_ABSTAND - 60 ) : $t;
}

/**
 * Problem melden (seit 1.72.2): Hinweis oben im Admin und eine E-Mail an die Anfrage-Adresse, höchstens alle 6 Stunden.
 * So fällt ein abgelaufener Schlüssel oder ein nicht gefundenes Reel am selben Abend auf, nicht erst Tage später.
 */
function kjo_kom_melden( $text, $art = 'fehler' ) {
	update_option( 'kjo_kom_problem', array( time(), (string) $text, $art ), false );
	if ( get_transient( 'kjo_kom_mail' ) ) {
		return;
	}
	set_transient( 'kjo_kom_mail', 1, 6 * HOUR_IN_SECONDS );
	$to = function_exists( 'kjo_vl_mail_to' ) ? kjo_vl_mail_to() : get_option( 'admin_email' );
	wp_mail( $to, 'Song-Kommentar: bitte prüfen', $text . "\n\nEinstellungen und Verlauf: " . admin_url( 'admin.php?page=kjo-song-kommentar' ) . "\n", array( 'Content-Type: text/plain; charset=UTF-8' ) );
}
function kjo_kom_ok() {
	delete_option( 'kjo_kom_problem' );
	delete_transient( 'kjo_kom_mail' );
}

/* Einmal am Tag prüfen, ob der Zugriffsschlüssel noch gilt – nicht erst am Release-Abend. */
function kjo_kom_schluessel_pruefen() {
	$soc = function_exists( 'kjo_soc_opts' ) ? kjo_soc_opts() : array();
	if ( ! kjo_kom_opts()['aktiv'] || empty( $soc['ig_id'] ) || get_transient( 'kjo_kom_check' ) ) {
		return;
	}
	set_transient( 'kjo_kom_check', 1, DAY_IN_SECONDS );
	$r = kjo_soc_api( $soc['ig_id'], null, array( 'fields' => 'username' ) );
	if ( is_wp_error( $r ) ) {
		kjo_soc_log( 'Kommentar', false, 'Schlüssel-Prüfung: ' . $r->get_error_message() );
		kjo_kom_melden( 'Der Instagram-Zugriffsschlüssel funktioniert nicht mehr – der nächste Song-Kommentar würde scheitern.' . "\n\nMeta meldet: " . $r->get_error_message() . "\n\nNeuen Seiten-Schlüssel erzeugen (Anleitung social/README.md, Schritt 3) und unter Social → Verbindung speichern." );
	} else {
		$p = get_option( 'kjo_kom_problem' );
		if ( is_array( $p ) && 'aufgegeben' !== ( $p[2] ?? '' ) ) {
			kjo_kom_ok(); // Schlüssel geht wieder – „aufgegeben“ bleibt stehen, bis du es auf der Einstellungsseite gelesen und gespeichert hast
		}
	}
}

/* Hinweis oben im Admin, solange ein Problem offen ist. */
add_action(
	'admin_notices',
	function () {
		$p = get_option( 'kjo_kom_problem' );
		if ( ! is_array( $p ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Song-Kommentar:</strong> ' . esc_html( $p[1] ) . ' (' . esc_html( wp_date( 'd.m. H:i', (int) $p[0] ) ) . ' Uhr) – <a href="' . esc_url( admin_url( 'admin.php?page=kjo-song-kommentar' ) ) . '">ansehen</a></p></div>';
	}
);

/* Fehlversuch zählen; nach dem letzten einmal im Verlauf melden. */
function kjo_kom_fehlversuch( $id, $grund ) {
	$n = (int) get_post_meta( $id, 'kjm_ig_versuche', true ) + 1;
	update_post_meta( $id, 'kjm_ig_versuche', $n );
	update_post_meta( $id, 'kjm_ig_versuch_ts', time() );
	if ( $n >= KJO_KOM_VERSUCHE ) {
		kjo_soc_log( 'Kommentar', false, get_the_title( $id ) . ': nach ' . $n . ' Versuchen aufgegeben (' . $grund . ') – bitte von Hand kommentieren und „erl.“ abhaken.' );
		kjo_kom_melden( '„' . get_the_title( $id ) . '“: nach ' . $n . ' Versuchen aufgegeben (' . $grund . '). Bitte den ersten Kommentar von Hand posten und in der Plattenkiste „erl.“ abhaken.', 'aufgegeben' );
	} elseif ( 1 === $n && false === strpos( $grund, 'kein Reel' ) ) {
		kjo_kom_melden( '„' . get_the_title( $id ) . '“: ' . $grund ); // Fehler von Meta (z. B. Schlüssel) sofort melden, nicht erst nach 6 Versuchen
	}
	return $n;
}

/* Pünktlich statt nur alle 15 Minuten: Ab der Uhrzeit löst jeder Seitenaufruf den Durchlauf aus (höchstens alle 5 Minuten).
   Am Dienstag ruft zusätzlich ein GitHub-Ablauf die Seite auf (.github/workflows/song-kommentar.yml). */
add_action(
	'init',
	function () {
		if ( ! kjo_kom_opts()['aktiv'] || wp_doing_ajax() ) {
			return;
		}
		$next = get_transient( 'kjo_kom_next' );
		if ( false === $next ) {
			$next = 0;
			foreach ( kjo_kom_kandidaten() as $id ) {
				$f    = kjo_kom_faellig( $id );
				$next = $next ? min( $next, $f ) : $f;
			}
			set_transient( 'kjo_kom_next', $next, 30 * MINUTE_IN_SECONDS );
		}
		if ( $next && time() >= (int) $next && ! get_transient( 'kjo_kom_sperre' ) ) {
			set_transient( 'kjo_kom_sperre', 1, 5 * MINUTE_IN_SECONDS );
			kjo_kom_lauf( false );
			delete_transient( 'kjo_kom_next' );
		}
	},
	99
);

function kjo_kom_norm( $s ) {
	$s = html_entity_decode( (string) $s, ENT_QUOTES, 'UTF-8' );
	$s = str_replace( array( '’', '‘', '`', '´' ), "'", $s );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s ) : strtolower( $s );
}

/**
 * Ein Durchlauf. $probe = true: nichts schreiben, nur berichten.
 * Rückgabe: Liste von Meldungen (für die Einstellungsseite) – geloggt wird im Social-Verlauf.
 */
function kjo_kom_lauf( $probe = false ) {
	$o   = kjo_kom_opts();
	$soc = function_exists( 'kjo_soc_opts' ) ? kjo_soc_opts() : array();
	$msg = array();
	if ( ! $probe && ! $o['aktiv'] ) {
		return array( 'Automatik ist aus.' );
	}
	if ( empty( $soc['ig_id'] ) ) {
		return array( 'Instagram ist nicht verbunden (Social → „Verbindung prüfen“).' );
	}
	$ids = kjo_kom_kandidaten();
	if ( ! $ids ) {
		return array( 'Kein offener Song mit Release-Termin zwischen vor 3 und in 6 Tagen.' );
	}
	if ( ! $probe ) { // nur Songs, deren Versuch jetzt dran ist – spart Abfragen bei Meta
		$dran = array_values( array_filter( $ids, function ( $id ) { return time() >= kjo_kom_faellig( $id ); } ) );
		if ( ! $dran ) {
			$f = min( array_map( 'kjo_kom_faellig', $ids ) );
			return array( PHP_INT_MAX === $f ? 'Alle Versuche aufgebraucht.' : 'Nächster Versuch ab ' . wp_date( 'd.m.Y H:i', $f ) . ' Uhr.' );
		}
		$ids = $dran;
	}
	$media = kjo_soc_api( $soc['ig_id'] . '/media', null, array( 'fields' => 'id,caption,timestamp,permalink', 'limit' => 12 ) );
	if ( is_wp_error( $media ) ) {
		if ( ! $probe ) {
			kjo_soc_log( 'Kommentar', false, $media->get_error_message() );
			foreach ( $ids as $id ) {
				if ( time() >= kjo_kom_faellig( $id ) ) {
					kjo_kom_fehlversuch( $id, $media->get_error_message() );
				}
			}
		}
		return array( $media->get_error_message() );
	}
	$grenze = time() - 4 * DAY_IN_SECONDS;
	foreach ( $ids as $id ) {
		if ( ! $probe && time() < kjo_kom_faellig( $id ) ) {
			$f     = kjo_kom_faellig( $id );
			$msg[] = '„' . get_the_title( $id ) . '“: ' . ( PHP_INT_MAX === $f ? 'nach ' . KJO_KOM_VERSUCHE . ' Versuchen aufgegeben.' : 'nächster Versuch ab ' . wp_date( 'd.m.Y H:i', $f ) . ' Uhr.' );
			continue;
		}
		$titel = kjo_kom_norm( get_the_title( $id ) );
		$treff = null;
		foreach ( (array) ( $media['data'] ?? array() ) as $m ) {
			if ( strtotime( (string) ( $m['timestamp'] ?? '' ) ) >= $grenze && false !== strpos( kjo_kom_norm( $m['caption'] ?? '' ), $titel ) ) {
				$treff = $m;
				break;
			}
		}
		$name = get_the_title( $id );
		if ( ! $treff ) {
			$v     = $probe ? '' : ' (Versuch ' . kjo_kom_fehlversuch( $id, 'kein Reel mit dem Titel gefunden' ) . ' von ' . KJO_KOM_VERSUCHE . ')';
			$msg[] = '„' . $name . '“ (geplant ' . get_post_meta( $id, 'kjm_release', true ) . '): noch kein Reel mit diesem Titel in der Bildunterschrift gefunden' . $v . '.';
			continue;
		}
		if ( $probe ) {
			$msg[] = '„' . $name . '“: Reel gefunden (' . ( $treff['permalink'] ?? $treff['id'] ) . '). Kommentar ab ' . wp_date( 'd.m.Y H:i', kjo_kom_faellig( $id ) ) . ' Uhr: ' . kjo_kom_text( $id );
			continue;
		}
		$r = kjo_soc_api( $treff['id'] . '/comments', array( 'message' => kjo_kom_text( $id ) ) );
		if ( is_wp_error( $r ) ) {
			$e = $r->get_error_message();
			if ( preg_match( '/Code (10|200|3)\)/', $e ) ) {
				$e .= ' – vermutlich fehlt dem Zugriffsschlüssel das Recht „instagram_manage_comments“.';
			}
			$n = kjo_kom_fehlversuch( $id, $e );
			if ( $n < KJO_KOM_VERSUCHE ) {
				kjo_soc_log( 'Kommentar', false, $name . ': ' . $e . ' (Versuch ' . $n . ')' );
			}
			$msg[] = '„' . $name . '“: ' . $e . ' (Versuch ' . $n . ' von ' . KJO_KOM_VERSUCHE . ')';
			continue;
		}
		update_post_meta( $id, 'kjm_ig_kommentar', (string) ( $r['id'] ?? '1' ) );
		update_post_meta( $id, 'kjm_release', wp_date( 'Y-m-d', strtotime( (string) $treff['timestamp'] ) ) );
		update_post_meta( $id, 'kjm_release_erl', 1 );
		kjo_soc_log( 'Kommentar', true, ! empty( $treff['permalink'] ) ? (string) $treff['permalink'] : $name . ': Kommentar geschrieben' );
		kjo_kom_ok();
		$msg[] = '„' . $name . '“: erster Kommentar geschrieben, Song als erledigt abgehakt.';
	}
	return $msg;
}

/* ---------- Zeitplan: alle 15 Minuten ---------- */
add_filter(
	'cron_schedules',
	function ( $s ) {
		$s['kjo_15min'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'Alle 15 Minuten',
		);
		return $s;
	}
);
add_action(
	'init',
	function () {
		$an = (bool) kjo_kom_opts()['aktiv'];
		$n  = wp_next_scheduled( 'kjo_kom_cron' );
		if ( $an && ! $n ) {
			wp_schedule_event( time() + 60, 'kjo_15min', 'kjo_kom_cron' );
		} elseif ( ! $an && $n ) {
			wp_clear_scheduled_hook( 'kjo_kom_cron' );
		}
	}
);
add_action(
	'kjo_kom_cron',
	function () {
		kjo_kom_schluessel_pruefen();
		kjo_kom_lauf( false );
	}
);

/* ---------- Einstellungen: Social → Song-Kommentar ---------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'kjo-social', 'Song-Kommentar', 'Song-Kommentar', 'manage_options', 'kjo-song-kommentar', 'kjo_kom_page' );
	},
	21
);

function kjo_kom_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o   = kjo_kom_opts();
	$out = array();
	if ( isset( $_POST['kjo_kom_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_kom_nonce'] ) ), 'kjo_kom' ) ) {
		$o['aktiv'] = empty( $_POST['aktiv'] ) ? 0 : 1;
		$z          = sanitize_text_field( wp_unslash( $_POST['zeit'] ?? '' ) ); // phpcs:ignore
		$o['zeit']  = preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $z ) ? $z : $o['zeit'];
		delete_transient( 'kjo_kom_next' );
		delete_transient( 'kjo_kom_check' ); // Schlüssel beim nächsten Durchlauf gleich neu prüfen
		kjo_kom_ok(); // Hinweis gelesen
		$t          = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) ); // phpcs:ignore
		$o['text']  = '' !== trim( $t ) ? $t : $o['text'];
		update_option( 'kjo_social_kommentar', $o, false );
		if ( ! empty( $_POST['probe'] ) ) {
			$out = kjo_kom_lauf( true );
		} elseif ( ! empty( $_POST['jetzt'] ) ) {
			$out = kjo_kom_lauf( false );
		} else {
			echo '<div class="notice notice-success"><p>Gespeichert.</p></div>';
		}
	}
	$soc = function_exists( 'kjo_soc_opts' ) ? kjo_soc_opts() : array();
	$ids = kjo_kom_kandidaten();
	echo '<div class="wrap"><h1>Erster Kommentar bei Song-Releases</h1>';
	echo '<p>Sobald ein geplanter Song (Spalte „Release“ unter Medien → Plattenkiste) als Reel auf Instagram erscheint, schreibt die Website automatisch den ersten Kommentar darunter und hakt den Song als „erl.“ ab. Erkannt wird das Reel am <strong>Songtitel in der Bildunterschrift</strong>. Geprüft wird alle 15 Minuten, sobald jemand die Website aufruft; dienstags ruft zusätzlich ein GitHub-Ablauf die Website alle 5 Minuten auf. Den Zugriffsschlüssel prüft die Website einmal am Tag. Klappt etwas nicht, kommt eine E-Mail an die Anfrage-Adresse und oben im Admin steht ein Hinweis.</p>';
	echo '<ul style="line-height:1.9">';
	echo '<li>' . ( ! empty( $soc['ig_id'] ) ? '✔ Instagram verbunden: @' . esc_html( $soc['ig_name'] ) : '✘ Instagram nicht verbunden – unter Social „Verbindung prüfen“' ) . '</li>';
	echo '<li>' . ( $o['aktiv'] ? '✔ Automatik an' : '✘ Automatik aus' ) . '</li>';
	echo '<li>Wartet auf ein Reel: ' . ( $ids ? esc_html( implode( ', ', array_map( 'get_the_title', $ids ) ) ) : 'keiner' ) . '</li>';
	echo '</ul>';
	if ( $out ) {
		echo '<div class="notice notice-info"><p>' . implode( '<br>', array_map( 'esc_html', $out ) ) . '</p></div>';
	}
	echo '<form method="post"><table class="form-table">';
	wp_nonce_field( 'kjo_kom', 'kjo_kom_nonce' );
	echo '<tr><th>Automatik</th><td><label><input type="checkbox" name="aktiv" value="1"' . checked( $o['aktiv'], 1, false ) . '> ersten Kommentar automatisch schreiben</label></td></tr>';
	echo '<tr><th><label for="kjo-kom-zeit">Uhrzeit</label></th><td><input id="kjo-kom-zeit" type="time" name="zeit" value="' . esc_attr( $o['zeit'] ) . '"> <span class="description">am Release-Tag. Ist das Reel noch nicht online, wird es alle 20 Minuten erneut versucht, insgesamt 6-mal (z. B. 18:10 bis 19:50); danach steht im Social-Verlauf ein Hinweis.</span></td></tr>';
	echo '<tr><th><label for="kjo-kom-text">Kommentar</label></th><td><textarea id="kjo-kom-text" name="text" rows="7" class="large-text">' . esc_textarea( $o['text'] ) . '</textarea><p class="description">Platzhalter: <code>{titel}</code> Songtitel · <code>{kurzlink}</code> dj-kolja-one.de/song/&lt;ID&gt; (kurz, zum Abtippen) · <code>{link}</code> langer Direktlink. Links sind in Instagram-Kommentaren nicht anklickbar.</p>';
	if ( $ids ) {
		echo '<p class="description">Vorschau für „' . esc_html( get_the_title( $ids[0] ) ) . '“:</p><pre style="white-space:pre-wrap;background:#fff;border:1px solid #dcdcde;padding:10px;max-width:640px">' . esc_html( kjo_kom_text( $ids[0] ) ) . '</pre>';
	}
	echo '</td></tr></table><p><button class="button button-primary">Speichern</button> <button class="button" name="probe" value="1">Probelauf (schreibt nichts)</button> <button class="button" name="jetzt" value="1" onclick="return confirm(\'Jetzt nach dem Reel suchen und – falls gefunden – den Kommentar schreiben?\')">Jetzt prüfen und kommentieren</button></p></form>';
	echo '<h2>Einrichtung (einmalig)</h2><ol><li>Der Seiten-Zugriffsschlüssel unter <strong>Social → Verbindung</strong> braucht zusätzlich das Recht <code>instagram_manage_comments</code>. Neuen Schlüssel mit diesem Recht erzeugen und dort speichern (Anleitung: <code>social/README.md</code>).</li><li><strong>Probelauf</strong> klicken: zeigt, ob das Reel gefunden wird und wie der Kommentar lautet.</li><li>Automatik anhaken und speichern.</li></ol></div>';
}
