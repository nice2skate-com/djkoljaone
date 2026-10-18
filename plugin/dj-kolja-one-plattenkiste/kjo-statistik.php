<?php
/**
 * Besucher-Statistik aus Google Analytics 4 – seit 1.62.0.
 *
 * - Dashboard-Box „Besucher“: gerade aktiv, heute pro Stunde (gestern zum Vergleich), letzte 14 Tage.
 * - Tagesbericht per E-Mail (ca. 7 Uhr): gestern pro Stunde, 14 Tage, Top-Seiten, Quellen, Kontakte und Songs.
 * - Liest über die Google Analytics Data API mit einem Dienstkonto (nur Lesezugriff „Betrachter“).
 *   Es werden keine zusätzlichen Besucherdaten erhoben – nur die vorhandenen GA4-Zahlen abgerufen.
 * Einstellungen: Dashboard → Besucher-Statistik. Anleitung dort.
 */

defined( 'ABSPATH' ) || exit;

function kjo_stat_opts() {
	$o = get_option( 'kjo_statistik', array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), array( 'sa' => '', 'property' => '', 'mail' => 1, 'an' => '' ) );
}
/** Property-ID: eigene Angabe, sonst aus Site Kit. */
function kjo_stat_property() {
	$o = kjo_stat_opts();
	if ( $o['property'] ) {
		return $o['property'];
	}
	$sk = get_option( 'googlesitekit_analytics-4_settings', array() );
	return is_array( $sk ) && ! empty( $sk['propertyID'] ) ? preg_replace( '/\D/', '', (string) $sk['propertyID'] ) : '';
}
function kjo_stat_sa() {
	$j = json_decode( (string) kjo_stat_opts()['sa'], true );
	return is_array( $j ) && ! empty( $j['client_email'] ) && ! empty( $j['private_key'] ) ? $j : null;
}
function kjo_stat_b64( $s ) {
	return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
}
/** Zugriffsschlüssel per signiertem JWT (Dienstkonto), 50 Minuten zwischengespeichert. */
function kjo_stat_token() {
	$t = get_transient( 'kjo_stat_token' );
	if ( $t ) {
		return $t;
	}
	$sa = kjo_stat_sa();
	if ( ! $sa ) {
		return new WP_Error( 'kjo_stat', 'Kein gültiges Dienstkonto hinterlegt.' );
	}
	$now   = time();
	$head  = kjo_stat_b64( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
	$claim = kjo_stat_b64( wp_json_encode( array( 'iss' => $sa['client_email'], 'scope' => 'https://www.googleapis.com/auth/analytics.readonly', 'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600 ) ) );
	$sig   = '';
	if ( ! function_exists( 'openssl_sign' ) || ! openssl_sign( $head . '.' . $claim, $sig, $sa['private_key'], 'sha256WithRSAEncryption' ) ) {
		return new WP_Error( 'kjo_stat', 'Schlüssel des Dienstkontos konnte nicht verwendet werden.' );
	}
	$r = wp_remote_post( 'https://oauth2.googleapis.com/token', array( 'timeout' => 20, 'body' => array( 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $head . '.' . $claim . '.' . kjo_stat_b64( $sig ) ) ) );
	$j = is_wp_error( $r ) ? null : json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( empty( $j['access_token'] ) ) {
		return new WP_Error( 'kjo_stat', 'Google-Anmeldung fehlgeschlagen: ' . ( is_wp_error( $r ) ? $r->get_error_message() : ( $j['error_description'] ?? $j['error'] ?? 'unbekannt' ) ) );
	}
	set_transient( 'kjo_stat_token', $j['access_token'], 50 * MINUTE_IN_SECONDS );
	return $j['access_token'];
}
/** Bericht abrufen. $art: runReport oder runRealtimeReport. Rückgabe: Liste von [dims[], mets[]] oder WP_Error. */
function kjo_stat_report( $body, $art = 'runReport' ) {
	$p = kjo_stat_property();
	if ( ! $p ) {
		return new WP_Error( 'kjo_stat', 'Keine GA4-Property gefunden – bitte Property-ID eintragen.' );
	}
	$t = kjo_stat_token();
	if ( is_wp_error( $t ) ) {
		return $t;
	}
	$r = wp_remote_post(
		'https://analyticsdata.googleapis.com/v1beta/properties/' . $p . ':' . $art,
		array( 'timeout' => 20, 'headers' => array( 'Authorization' => 'Bearer ' . $t, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ) )
	);
	$j = is_wp_error( $r ) ? null : json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		$m = is_wp_error( $r ) ? $r->get_error_message() : ( $j['error']['message'] ?? 'HTTP ' . wp_remote_retrieve_response_code( $r ) );
		if ( false !== stripos( $m, 'permission' ) ) {
			$m .= ' – ist das Dienstkonto in GA4 als „Betrachter“ eingetragen?';
		}
		return new WP_Error( 'kjo_stat', 'Google Analytics: ' . $m );
	}
	$out = array();
	foreach ( (array) ( $j['rows'] ?? array() ) as $row ) {
		$out[] = array( array_column( (array) ( $row['dimensionValues'] ?? array() ), 'value' ), array_map( 'floatval', array_column( (array) ( $row['metricValues'] ?? array() ), 'value' ) ) );
	}
	return $out;
}
function kjo_stat_dr( $von, $bis = null ) {
	return array( array( 'startDate' => $von, 'endDate' => $bis ? $bis : $von ) );
}
/** Werte je Stunde 0–23. */
function kjo_stat_stunden( $tag ) {
	$r = kjo_stat_report( array( 'dateRanges' => kjo_stat_dr( $tag ), 'dimensions' => array( array( 'name' => 'hour' ) ), 'metrics' => array( array( 'name' => 'activeUsers' ) ) ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$h = array_fill( 0, 24, 0 );
	foreach ( $r as $x ) {
		$h[ (int) $x[0][0] ] = (int) $x[1][0];
	}
	return $h;
}
/** Letzte $n Tage (bis gestern bzw. heute): [Ymd => [Nutzer, Sitzungen]]. */
function kjo_stat_tage( $n = 14, $bis = 'yesterday' ) {
	$r = kjo_stat_report( array( 'dateRanges' => kjo_stat_dr( $n . 'daysAgo', $bis ), 'dimensions' => array( array( 'name' => 'date' ) ), 'metrics' => array( array( 'name' => 'activeUsers' ), array( 'name' => 'sessions' ) ) ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$tz  = wp_timezone();
	$out = array();
	for ( $i = $n; $i >= ( 'today' === $bis ? 0 : 1 ); $i-- ) {
		$out[ ( new DateTime( "-$i days", $tz ) )->format( 'Ymd' ) ] = array( 0, 0 );
	}
	foreach ( $r as $x ) {
		if ( isset( $out[ $x[0][0] ] ) ) {
			$out[ $x[0][0] ] = array( (int) $x[1][0], (int) $x[1][1] );
		}
	}
	return $out;
}
function kjo_stat_liste( $dim, $met, $von, $bis, $limit, $filter = null ) {
	$b = array( 'dateRanges' => kjo_stat_dr( $von, $bis ), 'dimensions' => array( array( 'name' => $dim ) ), 'metrics' => array( array( 'name' => $met ) ), 'orderBys' => array( array( 'metric' => array( 'metricName' => $met ), 'desc' => true ) ), 'limit' => $limit );
	if ( $filter ) {
		$b['dimensionFilter'] = $filter;
	}
	$r = kjo_stat_report( $b );
	return is_wp_error( $r ) ? $r : array_map(
		function ( $x ) {
			return array( $x[0][0], (int) $x[1][0] );
		},
		$r
	);
}
const KJO_STAT_EVENTS = array(
	'generate_lead'        => 'Anfragen (Formular)',
	'kennenlernen_gebucht' => 'Kennenlerngespräche',
	'click_whatsapp'       => 'WhatsApp-Klicks',
	'click_phone'          => 'Anrufe (Klicks)',
	'click_email'          => 'E-Mail-Klicks',
	'song_play'            => 'Songs abgespielt',
	'share'                => 'Songs geteilt',
);
function kjo_stat_events( $von, $bis ) {
	$f = array( 'filter' => array( 'fieldName' => 'eventName', 'inListFilter' => array( 'values' => array_keys( KJO_STAT_EVENTS ) ) ) );
	return kjo_stat_liste( 'eventName', 'eventCount', $von, $bis, 20, $f );
}

/* ---------- Darstellung: Balken als Tabelle (läuft im Dashboard und in E-Mail-Programmen) ---------- */
function kjo_stat_balken( $labels, $werte, $hoehe = 90, $markiere = -1, $vergleich = null ) {
	$max = max( 1, max( $werte ), $vergleich ? max( $vergleich ) : 0 );
	$h   = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;table-layout:fixed"><tr>';
	foreach ( $werte as $i => $v ) {
		$px  = $v ? max( 2, (int) round( $v / $max * $hoehe ) ) : 0;
		$vg  = $vergleich ? (int) round( $vergleich[ $i ] / $max * $hoehe ) : 0;
		$col = $i === $markiere ? '#D4BF94' : '#B29D75';
		$h  .= '<td valign="bottom" style="padding:0 1px;height:' . ( $hoehe + 14 ) . 'px;vertical-align:bottom;text-align:center" title="' . esc_attr( $labels[ $i ] . ': ' . $v ) . '">'
			. ( $v ? '<div style="font:10px/12px sans-serif;color:#666">' . (int) $v . '</div>' : '' )
			. '<div style="height:' . $px . 'px;background:' . $col . ';border-radius:2px 2px 0 0;' . ( $vg > $px ? 'box-shadow:0 -' . ( $vg - $px ) . 'px 0 0 #E8E2D4;' : '' ) . 'line-height:0;font-size:0">&nbsp;</div></td>';
	}
	$h .= '</tr><tr>';
	$n  = count( $labels );
	foreach ( $labels as $i => $l ) {
		$show = $n <= 16 || 0 === $i % 3;
		$h   .= '<td style="font:10px/14px sans-serif;color:#888;text-align:center;padding-top:3px">' . ( $show ? esc_html( $l ) : '' ) . '</td>';
	}
	return $h . '</tr></table>';
}
function kjo_stat_tabelle( $zeilen, $kopf ) {
	if ( ! $zeilen ) {
		return '<p style="color:#888;font:13px sans-serif">Noch keine Daten.</p>';
	}
	$max = max( 1, max( array_column( $zeilen, 1 ) ) );
	$h   = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font:13px/1.4 sans-serif"><tr><th style="text-align:left;color:#888;font-weight:normal;padding:4px 0">' . esc_html( $kopf[0] ) . '</th><th style="text-align:right;color:#888;font-weight:normal;padding:4px 0">' . esc_html( $kopf[1] ) . '</th></tr>';
	foreach ( $zeilen as $z ) {
		$w  = (int) round( $z[1] / $max * 100 );
		$h .= '<tr><td style="padding:4px 8px 4px 0;border-top:1px solid #eee"><div style="word-break:break-all">' . esc_html( $z[0] ) . '</div><div style="height:4px;width:' . $w . '%;background:#B29D75;border-radius:2px;margin-top:3px;line-height:0;font-size:0">&nbsp;</div></td><td style="padding:4px 0;border-top:1px solid #eee;text-align:right;white-space:nowrap">' . (int) $z[1] . '</td></tr>';
	}
	return $h . '</table>';
}
function kjo_stat_tag_label( $ymd ) {
	$d = DateTime::createFromFormat( 'Ymd', $ymd, wp_timezone() );
	return $d ? array( 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' )[ (int) $d->format( 'w' ) ] . ' ' . $d->format( 'j.' ) : $ymd;
}

/* ---------- Dashboard-Box ---------- */
add_action(
	'wp_dashboard_setup',
	function () {
		if ( current_user_can( 'manage_options' ) ) {
			wp_add_dashboard_widget( 'kjo_stat_dash', 'Besucher (Google Analytics)', 'kjo_stat_dash' );
		}
	}
);
function kjo_stat_dash() {
	if ( ! kjo_stat_sa() ) {
		echo '<p>Noch nicht verbunden. <a href="' . esc_url( admin_url( 'index.php?page=kjo-statistik' ) ) . '">Jetzt einrichten</a> (ca. 10 Minuten).</p>';
		return;
	}
	$d = get_transient( 'kjo_stat_dash' );
	if ( ! is_array( $d ) || isset( $_GET['kjo_stat_neu'] ) ) { // phpcs:ignore
		$rt = kjo_stat_report( array( 'metrics' => array( array( 'name' => 'activeUsers' ) ) ), 'runRealtimeReport' );
		$d  = array(
			'jetzt'   => is_wp_error( $rt ) ? $rt : (int) ( $rt[0][1][0] ?? 0 ),
			'heute'   => kjo_stat_stunden( 'today' ),
			'gestern' => kjo_stat_stunden( 'yesterday' ),
			'tage'    => kjo_stat_tage( 14, 'today' ),
			'zeit'    => time(),
		);
		$fehler = array_filter( $d, 'is_wp_error' );
		if ( ! $fehler ) {
			set_transient( 'kjo_stat_dash', $d, 15 * MINUTE_IN_SECONDS );
		}
	}
	foreach ( $d as $v ) {
		if ( is_wp_error( $v ) ) {
			echo '<p style="color:#b32d2e">' . esc_html( $v->get_error_message() ) . '</p><p><a href="' . esc_url( admin_url( 'index.php?page=kjo-statistik' ) ) . '">Einstellungen prüfen</a></p>';
			return;
		}
	}
	$jetzt = (int) wp_date( 'G' );
	$sum   = array_sum( $d['heute'] );
	$sumg  = array_sum( array_slice( $d['gestern'], 0, $jetzt + 1 ) );
	echo '<div style="display:flex;gap:18px;margin-bottom:12px"><div><div style="font-size:26px;font-weight:600">' . (int) $d['jetzt'] . '</div><div style="color:#666">gerade aktiv (30 Min.)</div></div><div><div style="font-size:26px;font-weight:600">' . (int) $sum . '</div><div style="color:#666">heute bisher · gestern bis jetzt ' . (int) $sumg . '</div></div></div>';
	echo '<h3 style="margin:10px 0 4px">Heute pro Stunde <small style="font-weight:normal;color:#888">(hell = gestern)</small></h3>';
	echo kjo_stat_balken( array_map( 'strval', range( 0, 23 ) ), $d['heute'], 70, $jetzt, $d['gestern'] ); // phpcs:ignore
	echo '<h3 style="margin:14px 0 4px">Letzte 14 Tage <small style="font-weight:normal;color:#888">(Besucher pro Tag)</small></h3>';
	echo kjo_stat_balken( array_map( 'kjo_stat_tag_label', array_keys( $d['tage'] ) ), array_column( array_values( $d['tage'] ), 0 ), 70, count( $d['tage'] ) - 1 ); // phpcs:ignore
	echo '<p style="color:#888;margin-top:10px">Stand ' . esc_html( wp_date( 'H:i', $d['zeit'] ) ) . ' Uhr (alle 15 Min. neu) · <a href="' . esc_url( add_query_arg( 'kjo_stat_neu', 1, admin_url( 'index.php' ) ) ) . '">aktualisieren</a> · <a href="' . esc_url( admin_url( 'index.php?page=kjo-statistik' ) ) . '">Tagesbericht</a></p>';
}

/* ---------- Tagesbericht per E-Mail ---------- */
function kjo_stat_bericht_html() {
	$tz      = wp_timezone();
	$gestern = new DateTime( 'yesterday', $tz );
	$std     = kjo_stat_stunden( 'yesterday' );
	$vor     = kjo_stat_stunden( '2daysAgo' );
	$tage    = kjo_stat_tage( 14, 'yesterday' );
	$seiten  = kjo_stat_liste( 'pagePath', 'screenPageViews', '7daysAgo', 'yesterday', 8 );
	$quellen = kjo_stat_liste( 'sessionSourceMedium', 'sessions', '7daysAgo', 'yesterday', 6 );
	$ev      = kjo_stat_events( '7daysAgo', 'yesterday' );
	$evg     = kjo_stat_events( 'yesterday', 'yesterday' );
	$songs   = kjo_stat_report(
		array(
			'dateRanges'      => kjo_stat_dr( '7daysAgo', 'yesterday' ),
			'dimensions'      => array( array( 'name' => 'customEvent:item_name' ) ),
			'metrics'         => array( array( 'name' => 'eventCount' ) ),
			'dimensionFilter' => array( 'filter' => array( 'fieldName' => 'eventName', 'stringFilter' => array( 'value' => 'song_play' ) ) ),
			'orderBys'        => array( array( 'metric' => array( 'metricName' => 'eventCount' ), 'desc' => true ) ),
			'limit'           => 6,
		)
	);
	foreach ( array( $std, $vor, $tage, $seiten, $quellen, $ev, $evg ) as $x ) {
		if ( is_wp_error( $x ) ) {
			return $x;
		}
	}
	$songs = is_wp_error( $songs ) ? array() : array_values(
		array_filter(
			array_map(
				function ( $x ) {
					return array( $x[0][0], (int) $x[1][0] );
				},
				$songs
			),
			function ( $z ) {
				return '' !== $z[0] && '(not set)' !== $z[0];
			}
		)
	);
	$sum  = array_sum( $std );
	$sumv = array_sum( $vor );
	$t7   = array_sum( array_slice( array_column( array_values( $tage ), 0 ), -7 ) );
	$v7   = array_sum( array_slice( array_column( array_values( $tage ), 0 ), 0, 7 ) );
	$diff = function ( $a, $b ) {
		if ( ! $b ) {
			return '';
		}
		$p = (int) round( ( $a - $b ) / $b * 100 );
		return ' <span style="color:' . ( $p >= 0 ? '#2e7d32' : '#b32d2e' ) . '">' . ( $p >= 0 ? '+' : '' ) . $p . ' %</span>';
	};
	$evm  = array();
	foreach ( $ev as $e ) {
		$evm[ $e[0] ] = $e[1];
	}
	$evgm = array();
	foreach ( $evg as $e ) {
		$evgm[ $e[0] ] = $e[1];
	}
	$kz = '';
	foreach ( KJO_STAT_EVENTS as $k => $l ) {
		$kz .= '<tr><td style="padding:4px 0;border-top:1px solid #eee">' . esc_html( $l ) . '</td><td style="padding:4px 0;border-top:1px solid #eee;text-align:right">' . (int) ( $evgm[ $k ] ?? 0 ) . '</td><td style="padding:4px 0;border-top:1px solid #eee;text-align:right">' . (int) ( $evm[ $k ] ?? 0 ) . '</td></tr>';
	}
	$box = function ( $titel, $inhalt ) {
		return '<tr><td style="padding:18px 22px;background:#fff;border-radius:4px"><h2 style="margin:0 0 10px;font:600 16px sans-serif;color:#0F0C07">' . $titel . '</h2>' . $inhalt . '</td></tr><tr><td style="height:12px"></td></tr>';
	};
	$h  = '<div style="background:#F3F1E9;padding:20px 10px"><table role="presentation" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;margin:0 auto;font:14px/1.5 sans-serif;color:#222">';
	$h .= '<tr><td style="padding:18px 22px;background:#0F0C07;color:#F3F1E9;border-radius:4px"><div style="color:#B29D75;font:12px sans-serif;letter-spacing:2px;text-transform:uppercase">DJ KOLJA ONE · Besucher</div><div style="font:600 22px sans-serif;margin-top:4px">' . esc_html( wp_date( 'l, j. F Y', $gestern->getTimestamp() ) ) . '</div>'
		. '<div style="margin-top:8px">' . (int) $sum . ' Besucher' . $diff( $sum, $sumv ) . ' zum Vortag · 7 Tage: ' . (int) $t7 . $diff( $t7, $v7 ) . ' zur Vorwoche</div></td></tr><tr><td style="height:12px"></td></tr>';
	$h .= $box( 'Gestern pro Stunde <small style="font-weight:normal;color:#888">(hell = Vortag)</small>', kjo_stat_balken( array_map( 'strval', range( 0, 23 ) ), $std, 90, -1, $vor ) );
	$h .= $box( 'Letzte 14 Tage', kjo_stat_balken( array_map( 'kjo_stat_tag_label', array_keys( $tage ) ), array_column( array_values( $tage ), 0 ), 90, count( $tage ) - 1 ) );
	$h .= $box( 'Kontakte &amp; Songs', '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font:13px/1.4 sans-serif"><tr><th style="text-align:left;color:#888;font-weight:normal">Ereignis</th><th style="text-align:right;color:#888;font-weight:normal">gestern</th><th style="text-align:right;color:#888;font-weight:normal">7 Tage</th></tr>' . $kz . '</table>' );
	if ( $songs ) {
		$h .= $box( 'Meistgespielte Songs (7 Tage)', kjo_stat_tabelle( $songs, array( 'Song', 'Plays' ) ) );
	}
	$h .= $box( 'Top-Seiten (7 Tage)', kjo_stat_tabelle( $seiten, array( 'Seite', 'Aufrufe' ) ) );
	$h .= $box( 'Woher kommen die Besucher? (7 Tage)', kjo_stat_tabelle( $quellen, array( 'Quelle / Medium', 'Sitzungen' ) ) );
	$h .= '<tr><td style="color:#888;font:12px sans-serif;text-align:center;padding:6px">Zahlen aus Google Analytics 4. Gestrige Werte können sich bis zum Mittag noch leicht ändern. Abbestellen: Dashboard → Besucher-Statistik.</td></tr></table></div>';
	return array( 'html' => $h, 'betreff' => 'Besucher ' . wp_date( 'D, j.n.', $gestern->getTimestamp() ) . ': ' . $sum . ' (7 Tage: ' . $t7 . ')' );
}
function kjo_stat_bericht_senden() {
	$o = kjo_stat_opts();
	$b = kjo_stat_bericht_html();
	$an = $o['an'] && is_email( $o['an'] ) ? $o['an'] : get_option( 'admin_email' );
	if ( is_wp_error( $b ) ) {
		wp_mail( $an, 'Besucher-Bericht: Abruf fehlgeschlagen', $b->get_error_message() . "\n\nEinstellungen: " . admin_url( 'index.php?page=kjo-statistik' ), array( 'Content-Type: text/plain; charset=UTF-8' ) );
		return $b;
	}
	return wp_mail( $an, $b['betreff'], $b['html'], array( 'Content-Type: text/html; charset=UTF-8' ) );
}
/* Täglich gegen 7 Uhr (Ortszeit der Website). */
add_action(
	'init',
	function () {
		$o = kjo_stat_opts();
		$n = wp_next_scheduled( 'kjo_stat_taeglich' );
		if ( $o['mail'] && kjo_stat_sa() && ! $n ) {
			$t = new DateTime( 'tomorrow 07:00', wp_timezone() );
			if ( (int) wp_date( 'G' ) < 7 ) {
				$t = new DateTime( 'today 07:00', wp_timezone() );
			}
			wp_schedule_event( $t->getTimestamp(), 'daily', 'kjo_stat_taeglich' );
		} elseif ( $n && ( ! $o['mail'] || ! kjo_stat_sa() ) ) {
			wp_clear_scheduled_hook( 'kjo_stat_taeglich' );
		}
	}
);
add_action( 'kjo_stat_taeglich', 'kjo_stat_bericht_senden' );

/* ---------- Einstellungen ---------- */
add_action(
	'admin_menu',
	function () {
		add_dashboard_page( 'Besucher-Statistik', 'Besucher-Statistik', 'manage_options', 'kjo-statistik', 'kjo_stat_page' );
	}
);
function kjo_stat_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o   = kjo_stat_opts();
	$msg = '';
	if ( isset( $_POST['kjo_stat_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_stat_nonce'] ) ), 'kjo_stat' ) ) {
		$sa = trim( wp_unslash( $_POST['sa'] ?? '' ) ); // phpcs:ignore -- JSON, wird geprüft und nie ausgegeben
		if ( '' !== $sa ) {
			$j = json_decode( $sa, true );
			if ( is_array( $j ) && ! empty( $j['client_email'] ) && ! empty( $j['private_key'] ) && 'service_account' === ( $j['type'] ?? '' ) ) {
				$o['sa'] = wp_json_encode( array( 'type' => 'service_account', 'client_email' => $j['client_email'], 'private_key' => $j['private_key'] ) );
				delete_transient( 'kjo_stat_token' );
			} else {
				$msg = 'Die eingefügte Schlüsseldatei ist kein gültiges Dienstkonto (JSON von Google Cloud).';
			}
		}
		if ( ! empty( $_POST['sa_weg'] ) ) {
			$o['sa'] = '';
		}
		$o['property'] = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['property'] ?? '' ) ) ); // phpcs:ignore
		$o['mail']     = empty( $_POST['mail'] ) ? 0 : 1;
		$an            = sanitize_email( wp_unslash( $_POST['an'] ?? '' ) ); // phpcs:ignore
		$o['an']       = is_email( $an ) ? $an : '';
		update_option( 'kjo_statistik', $o, false );
		delete_transient( 'kjo_stat_dash' );
		wp_clear_scheduled_hook( 'kjo_stat_taeglich' );
		echo '<div class="notice ' . ( $msg ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $msg ? $msg : 'Gespeichert.' ) . '</p></div>';
	}
	if ( isset( $_POST['kjo_stat_test'] ) && check_admin_referer( 'kjo_stat_test' ) ) {
		$r = kjo_stat_bericht_senden();
		echo '<div class="notice ' . ( true === $r ? 'notice-success' : 'notice-error' ) . '"><p>' . esc_html( true === $r ? 'Testbericht verschickt.' : ( is_wp_error( $r ) ? $r->get_error_message() : 'E-Mail konnte nicht gesendet werden.' ) ) . '</p></div>';
	}
	$sa   = kjo_stat_sa();
	$prop = kjo_stat_property();
	echo '<div class="wrap"><h1>Besucher-Statistik</h1><p>Zeigt deine Google-Analytics-Zahlen im Dashboard und schickt dir jeden Morgen gegen 7 Uhr einen Bericht mit Diagrammen. Es werden keine zusätzlichen Daten über Besucher erhoben.</p>';
	echo '<form method="post"><table class="form-table">';
	wp_nonce_field( 'kjo_stat', 'kjo_stat_nonce' );
	echo '<tr><th><label for="kjo-stat-sa">Dienstkonto (JSON-Schlüssel)</label></th><td>' . ( $sa ? '<p>✔ Verbunden als <code>' . esc_html( $sa['client_email'] ) . '</code> <label style="margin-left:10px"><input type="checkbox" name="sa_weg" value="1"> löschen</label></p>' : '' ) . '<textarea id="kjo-stat-sa" name="sa" rows="4" class="large-text code" placeholder="' . esc_attr( $sa ? 'Neuen Schlüssel einfügen, um ihn zu ersetzen' : 'Inhalt der heruntergeladenen JSON-Datei hier einfügen' ) . '"></textarea><p class="description">Wird nur auf deinem Server gespeichert und nie wieder angezeigt.</p></td></tr>';
	echo '<tr><th><label for="kjo-stat-prop">GA4-Property-ID</label></th><td><input id="kjo-stat-prop" name="property" value="' . esc_attr( $o['property'] ) . '" placeholder="' . esc_attr( $prop ? $prop . ' (aus Site Kit)' : 'z. B. 412345678' ) . '" class="regular-text"><p class="description">Leer lassen, wenn Site Kit sie kennt. Sonst: GA4 → Verwaltung → Property-Details.</p></td></tr>';
	echo '<tr><th>Tagesbericht</th><td><label><input type="checkbox" name="mail" value="1"' . checked( $o['mail'], 1, false ) . '> jeden Morgen gegen 7 Uhr per E-Mail</label><br><input name="an" value="' . esc_attr( $o['an'] ) . '" class="regular-text" placeholder="' . esc_attr( get_option( 'admin_email' ) ) . '" aria-label="Empfänger"></td></tr>';
	echo '</table><p><button class="button button-primary">Speichern</button></p></form>';
	if ( $sa ) {
		echo '<form method="post" style="margin-top:-6px">';
		wp_nonce_field( 'kjo_stat_test' );
		echo '<button class="button" name="kjo_stat_test" value="1">Testbericht jetzt senden</button>' . ( wp_next_scheduled( 'kjo_stat_taeglich' ) ? ' <span style="color:#666">Nächster Bericht: ' . esc_html( wp_date( 'D, j.n. H:i', wp_next_scheduled( 'kjo_stat_taeglich' ) ) ) . ' Uhr</span>' : '' ) . '</form>';
	}
	echo '<h2>Einrichtung (einmalig, ca. 10 Minuten)</h2><ol style="max-width:760px;line-height:1.7">'
		. '<li><a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener">Google Cloud</a> mit deinem Google-Konto öffnen → Projekt „DJ KOLJA ONE Statistik“ anlegen.</li>'
		. '<li><a href="https://console.cloud.google.com/apis/library/analyticsdata.googleapis.com" target="_blank" rel="noopener">Google Analytics Data API</a> → <strong>Aktivieren</strong>.</li>'
		. '<li><a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener">Dienstkonten</a> → <strong>Dienstkonto erstellen</strong> → Name „website-statistik“ → Rollen überspringen → Fertig.</li>'
		. '<li>Das Dienstkonto öffnen → Reiter <strong>Schlüssel</strong> → Schlüssel hinzufügen → Neuen Schlüssel erstellen → <strong>JSON</strong>. Die Datei wird heruntergeladen.</li>'
		. '<li>Die E-Mail-Adresse des Dienstkontos kopieren (endet auf <code>.iam.gserviceaccount.com</code>) → <strong>Google Analytics</strong> → Verwaltung → Property → <strong>Property-Zugriffsverwaltung</strong> → + → Adresse einfügen, Rolle <strong>Betrachter</strong>, ohne Benachrichtigung → Hinzufügen.</li>'
		. '<li>Die JSON-Datei mit einem Texteditor öffnen, alles kopieren und oben einfügen → Speichern → „Testbericht jetzt senden“. Die Datei danach löschen. Den Schlüssel nirgends sonst teilen, auch nicht im Chat.</li></ol></div>';
}
