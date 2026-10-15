<?php
/**
 * Kennenlerngespräch buchen (seit 1.56.0)
 *
 * Interessenten wählen auf der Website einen freien Zeitpunkt (Telefon oder Video) – statt hin- und herzuschreiben.
 * - Zeiten pro Wochentag, Gesprächsdauer, Vorlauf und Horizont unter „DJ-Anfragen → Kennenlernen: Zeiten“.
 * - Tage mit eigenem Termin im Kalender (DJ-Einsatz oder privat) bieten keine Zeiten an.
 * - Gebuchte Gespräche stehen unter „DJ-Anfragen → Kennenlerngespräche“; beide Seiten bekommen eine E-Mail mit Kalendereintrag (.ics).
 * Shortcode: [kjo_kennenlernen]
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kjo_kl_opts() {
	$d = array(
		'tage'     => array( // ISO-Wochentag => array( von, bis ), leer = kein Termin
			1 => array( '18:00', '20:30' ),
			2 => array( '18:00', '20:30' ),
			3 => array( '18:00', '20:30' ),
			4 => array( '18:00', '20:30' ),
			5 => array( '', '' ),
			6 => array( '', '' ),
			7 => array( '', '' ),
		),
		'dauer'    => 20,  // Minuten
		'raster'   => 30,  // Minuten zwischen zwei Startzeiten
		'vorlauf'  => 24,  // Stunden
		'horizont' => 21,  // Tage
		'video'    => '',  // fester Video-Link (optional), z. B. Google Meet
	);
	$o = get_option( 'kjo_kl', array() );
	return is_array( $o ) ? array_replace_recursive( $d, $o ) : $d;
}
const KJO_KL_ARTEN = array( 'telefon' => 'Telefon', 'video' => 'Video-Call' );

add_action(
	'init',
	function () {
		register_post_type(
			'kjo_gespraech',
			array(
				'labels'          => array( 'name' => 'Kennenlerngespräche', 'singular_name' => 'Kennenlerngespräch', 'menu_name' => 'Kennenlerngespräche', 'edit_item' => 'Kennenlerngespräch' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=kjo_anfrage',
				'capability_type' => 'page',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'supports'        => array( 'title' ),
			)
		);
	}
);

/** Bereits belegte Startzeiten (Unix-Zeit) ab heute. */
function kjo_kl_belegt() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'kjo_gespraech', 'post_status' => 'private', 'numberposts' => 300, 'meta_key' => '_kjo_kl_start', 'meta_value' => time() - DAY_IN_SECONDS, 'meta_compare' => '>', 'meta_type' => 'NUMERIC' ) ) as $p ) { // phpcs:ignore
		if ( 'storniert' !== get_post_meta( $p->ID, '_kjo_kl_status', true ) ) {
			$out[] = (int) get_post_meta( $p->ID, '_kjo_kl_start', true );
		}
	}
	return $out;
}
/** Tage mit eigenem Kalendertermin (DJ-Einsatz/privat): keine Gespräche. */
function kjo_kl_gesperrt( $von, $bis ) {
	$tage = array();
	if ( ! function_exists( 'kjo_kal_termine' ) ) {
		return $tage;
	}
	foreach ( (array) kjo_kal_termine( $von, $bis ) as $t ) {
		$t = (array) $t;
		if ( isset( $t['art'] ) && 'equip' === $t['art'] ) {
			continue; // reiner Verleih blockiert keine Gespräche
		}
		$a = isset( $t['von'] ) ? $t['von'] : '';
		$b = isset( $t['bis'] ) && $t['bis'] ? $t['bis'] : $a;
		for ( $d = $a, $i = 0; $d && $d <= $b && $i < 400; $i++, $d = gmdate( 'Y-m-d', strtotime( $d . ' 12:00:00 UTC' ) + DAY_IN_SECONDS ) ) {
			$tage[ $d ] = true;
		}
	}
	return $tage;
}
/** Freie Zeiten: array( 'Y-m-d' => array( 'H:i', … ) ). */
function kjo_kl_slots() {
	$o    = kjo_kl_opts();
	$tz   = wp_timezone();
	$now  = time() + (int) $o['vorlauf'] * HOUR_IN_SECONDS;
	$bel  = array_flip( kjo_kl_belegt() );
	$heute = new DateTimeImmutable( 'today', $tz );
	$sperr = kjo_kl_gesperrt( $heute->format( 'Y-m-d' ), $heute->modify( '+' . (int) $o['horizont'] . ' days' )->format( 'Y-m-d' ) );
	$out  = array();
	for ( $i = 0; $i <= (int) $o['horizont']; $i++ ) {
		$tag = $heute->modify( '+' . $i . ' days' );
		$w   = (int) $tag->format( 'N' );
		$f   = isset( $o['tage'][ $w ] ) ? $o['tage'][ $w ] : array( '', '' );
		if ( empty( $f[0] ) || empty( $f[1] ) || isset( $sperr[ $tag->format( 'Y-m-d' ) ] ) ) {
			continue;
		}
		$a = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $tag->format( 'Y-m-d' ) . ' ' . $f[0], $tz );
		$e = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $tag->format( 'Y-m-d' ) . ' ' . $f[1], $tz );
		if ( ! $a || ! $e ) {
			continue;
		}
		for ( $t = $a; $t->getTimestamp() + (int) $o['dauer'] * 60 <= $e->getTimestamp(); $t = $t->modify( '+' . max( 10, (int) $o['raster'] ) . ' minutes' ) ) {
			if ( $t->getTimestamp() >= $now && ! isset( $bel[ $t->getTimestamp() ] ) ) {
				$out[ $tag->format( 'Y-m-d' ) ][] = $t->format( 'H:i' );
			}
		}
	}
	return $out;
}

/** Kalendereintrag (.ics) als Text. */
function kjo_kl_ics( $start, $min, $titel, $text, $uid ) {
	$f   = function ( $ts ) {
		return gmdate( 'Ymd\THis\Z', $ts );
	};
	$esc = function ( $s ) {
		return str_replace( array( '\\', ';', ',', "\r", "\n" ), array( '\\\\', '\;', '\,', '', '\n' ), $s );
	};
	return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//DJ KOLJA ONE//Kennenlernen//DE\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\nUID:" . $uid . "@dj-kolja-one.de\r\nDTSTAMP:" . $f( time() ) . "\r\nDTSTART:" . $f( $start ) . "\r\nDTEND:" . $f( $start + $min * 60 ) . "\r\nSUMMARY:" . $esc( $titel ) . "\r\nDESCRIPTION:" . $esc( $text ) . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/kennenlernen',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => function () {
						$o   = kjo_kl_opts();
						$wt  = array( 1 => 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So' );
						$out = array();
						foreach ( kjo_kl_slots() as $d => $z ) {
							$t     = new DateTimeImmutable( $d, wp_timezone() );
							$out[] = array( 'd' => $d, 'l' => $wt[ (int) $t->format( 'N' ) ] . ', ' . $t->format( 'd.m.' ), 'z' => $z );
						}
						$r = new WP_REST_Response( array( 'tage' => $out, 'dauer' => (int) $o['dauer'], 'arten' => KJO_KL_ARTEN ), 200 );
						$r->header( 'Cache-Control', 'no-store' );
						return $r;
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true',
					'callback'            => 'kjo_kl_rest_buchen',
				),
			)
		);
	}
);

function kjo_kl_rest_buchen( WP_REST_Request $r ) {
	$d = $r->get_json_params();
	$d = is_array( $d ) ? $d : array();
	$s = function ( $k, $len = 200 ) use ( $d ) {
		return isset( $d[ $k ] ) && is_scalar( $d[ $k ] ) ? mb_substr( trim( sanitize_text_field( (string) $d[ $k ] ) ), 0, $len ) : '';
	};
	$err = function ( $m, $c = 400 ) {
		return new WP_REST_Response( array( 'message' => $m ), $c );
	};
	if ( '' !== $s( 'website' ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk = 'kjo_kl_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 20 );
	if ( (int) get_transient( $rk ) >= 4 ) {
		return $err( 'Es wurden gerade mehrere Termine gebucht. Bitte meldet euch kurz per WhatsApp oder Telefon.', 429 );
	}
	$tag  = $s( 'd', 10 );
	$zeit = $s( 'z', 5 );
	$art  = isset( KJO_KL_ARTEN[ $s( 'art', 10 ) ] ) ? $s( 'art', 10 ) : 'telefon';
	$name = $s( 'name', 80 );
	$tel  = $s( 'tel', 40 );
	$mail = sanitize_email( $s( 'email', 120 ) );
	$noti = isset( $d['notiz'] ) && is_scalar( $d['notiz'] ) ? mb_substr( trim( sanitize_textarea_field( (string) $d['notiz'] ) ), 0, 800 ) : '';
	if ( ! $name || ! is_email( $mail ) ) {
		return $err( 'Bitte gebt euren Namen und eine gültige E-Mail-Adresse an.' );
	}
	if ( 'telefon' === $art && strlen( preg_replace( '/\D/', '', $tel ) ) < 6 ) {
		return $err( 'Für ein Telefongespräch brauche ich eure Telefonnummer.' );
	}
	$frei = kjo_kl_slots();
	if ( empty( $frei[ $tag ] ) || ! in_array( $zeit, $frei[ $tag ], true ) ) {
		return $err( 'Dieser Zeitpunkt ist leider nicht mehr frei. Bitte wählt einen anderen.', 409 );
	}
	$o     = kjo_kl_opts();
	$start = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $tag . ' ' . $zeit, wp_timezone() )->getTimestamp();
	$wann  = wp_date( 'l, d.m.Y \u\m H:i \U\h\r', $start );
	$pid   = wp_insert_post(
		array(
			'post_type'   => 'kjo_gespraech',
			'post_status' => 'private',
			'post_title'  => wp_date( 'd.m.Y H:i', $start ) . ' – ' . $name . ' (' . KJO_KL_ARTEN[ $art ] . ')',
		)
	);
	if ( ! $pid || is_wp_error( $pid ) ) {
		return $err( 'Das hat nicht geklappt. Bitte meldet euch per WhatsApp oder Telefon.', 500 );
	}
	set_transient( $rk, (int) get_transient( $rk ) + 1, HOUR_IN_SECONDS );
	foreach ( array( 'start' => $start, 'art' => $art, 'name' => $name, 'tel' => $tel, 'email' => $mail, 'notiz' => $noti, 'status' => 'gebucht' ) as $k => $v ) {
		update_post_meta( $pid, '_kjo_kl_' . $k, $v );
	}
	// Passende DJ-Anfrage (gleiche E-Mail, letzte 90 Tage) verknüpfen
	$an = get_posts( array( 'post_type' => 'kjo_anfrage', 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_kjo_email', 'meta_value' => $mail, 'date_query' => array( array( 'after' => '90 days ago' ) ) ) ); // phpcs:ignore
	if ( $an ) {
		update_post_meta( $pid, '_kjo_kl_anfrage', $an[0]->ID );
	}
	$wie   = 'video' === $art ? 'Video-Call' . ( $o['video'] ? ' – Link: ' . $o['video'] : ' – den Link schicke ich euch vorab per E-Mail.' ) : 'Telefon – ich rufe euch an unter ' . $tel . '.';
	$to    = function_exists( 'kjo_vl_mail_to' ) ? kjo_vl_mail_to() : get_option( 'admin_email' );
	$up    = wp_upload_dir();
	$tmp   = trailingslashit( $up['basedir'] ) . 'kjo-tmp';
	wp_mkdir_p( $tmp );
	$ics_k = $tmp . '/kennenlernen-' . $pid . '.ics';
	file_put_contents( $ics_k, kjo_kl_ics( $start, (int) $o['dauer'], 'Kennenlerngespräch mit DJ KOLJA ONE', $wie, 'kl-' . $pid ) ); // phpcs:ignore
	$hdr   = array( 'Content-Type: text/plain; charset=UTF-8' );
	$mk    = "Neues Kennenlerngespräch\n\nWann: " . $wann . "\nWie: " . KJO_KL_ARTEN[ $art ] . "\nName: " . $name . "\nE-Mail: " . $mail . "\nTelefon: " . ( $tel ? $tel : '–' ) . ( $noti ? "\n\nNotiz:\n" . $noti : '' ) . ( $an ? "\n\nZur Anfrage: " . admin_url( 'post.php?post=' . $an[0]->ID . '&action=edit' ) : '' ) . "\n\nIm Admin: " . admin_url( 'post.php?post=' . $pid . '&action=edit' ) . "\n";
	$ics_m = $tmp . '/kolja-' . $pid . '.ics';
	file_put_contents( $ics_m, kjo_kl_ics( $start, (int) $o['dauer'], 'Kennenlernen: ' . $name . ' (' . KJO_KL_ARTEN[ $art ] . ')', 'Telefon: ' . $tel . ' · E-Mail: ' . $mail . ( $noti ? ' · ' . $noti : '' ), 'kl-k-' . $pid ) ); // phpcs:ignore
	wp_mail( $to, 'Kennenlerngespräch: ' . wp_date( 'd.m. H:i', $start ) . ' – ' . $name, $mk, array_merge( $hdr, array( 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $mail . '>' ) ), array( $ics_m ) );
	$mc = 'Hallo ' . $name . ",\n\ndanke! Unser Kennenlerngespräch ist eingetragen:\n\n" . $wann . "\n" . $wie . "\nDauer: ca. " . (int) $o['dauer'] . " Minuten, kostenlos und unverbindlich.\n\nWir sprechen über euren Anlass, eure Musik und den Ablauf. Den Termin findet ihr auch im Anhang für euren Kalender.\n\nPasst der Zeitpunkt doch nicht? Antwortet einfach auf diese E-Mail oder schreibt mir per WhatsApp.\n\nBis bald\nKolja – DJ KOLJA ONE\ndj-kolja-one.de\n";
	wp_mail( $mail, 'Euer Kennenlerngespräch mit DJ KOLJA ONE', $mc, array_merge( $hdr, array( 'Reply-To: DJ KOLJA ONE <' . $to . '>' ) ), array( $ics_k ) );
	@unlink( $ics_k ); // phpcs:ignore
	@unlink( $ics_m ); // phpcs:ignore
	return new WP_REST_Response( array( 'ok' => true, 'wann' => $wann, 'ics' => kjo_kl_ics( $start, (int) $o['dauer'], 'Kennenlerngespräch mit DJ KOLJA ONE', $wie, 'kl-' . $pid ) ), 200 );
}

/* ---------- Website: Shortcode ---------- */
add_shortcode(
	'kjo_kennenlernen',
	function () {
		$css               = empty( $GLOBALS['kjo_kl'] ) ? kjo_kl_css() : '';
		$GLOBALS['kjo_kl'] = true;
		return $css . '<div class="kjo-kl" id="kennenlernen" data-api="' . esc_url( rest_url( 'kjo/v1/kennenlernen' ) ) . '">'
			. '<p class="kjo-kl-l">1. Tag wählen</p><div class="kjo-kl-tage" role="group" aria-label="Tag wählen"><span class="kjo-kl-lade">Freie Zeiten werden geladen …</span></div>'
			. '<p class="kjo-kl-l">2. Uhrzeit wählen</p><div class="kjo-kl-zeiten" role="group" aria-label="Uhrzeit wählen"><span class="kjo-kl-hint">Bitte zuerst einen Tag wählen.</span></div>'
			. '<form class="kjo-kl-f" novalidate hidden><p class="kjo-kl-l">3. Wie und wer?</p>'
			. '<div class="kjo-kl-art" role="radiogroup" aria-label="Gesprächsart"><label><input type="radio" name="art" value="telefon" checked><span>Telefon</span></label><label><input type="radio" name="art" value="video"><span>Video-Call</span></label></div>'
			. '<div class="kjo-kl-g"><label>Name *<input name="name" autocomplete="name" maxlength="80" required></label><label>E-Mail *<input name="email" type="email" autocomplete="email" maxlength="120" required></label><label>Telefon <span class="kjo-kl-tel-o">*</span><input name="tel" type="tel" autocomplete="tel" maxlength="40"></label></div>'
			. '<label>Worum geht es? <span class="kjo-kl-o">(optional)</span><textarea name="notiz" maxlength="800" rows="3" placeholder="z. B. Hochzeit am 12.06.2027 in Memmingen, ca. 90 Gäste"></textarea></label>'
			. '<label class="kjo-kl-hp" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>'
			. '<p class="kjo-kl-err" role="alert"></p><button type="submit" class="kjo-kl-btn">Termin verbindlich buchen</button>'
			. '<p class="kjo-kl-ds">Eure Angaben nutze ich nur für das Gespräch. <a href="' . esc_url( home_url( '/datenschutz/' ) ) . '">Datenschutz</a></p></form>'
			. '<div class="kjo-kl-ok" hidden tabindex="-1"></div></div>';
	}
);
/** Styles direkt vor dem Formular (nur auf Seiten mit dem Shortcode, kein Layout-Sprung). */
function kjo_kl_css() {
	return '<style id="kjo-kl-css">.kjo-kl{width:100%;max-width:760px;margin:0 auto;font-family:"Fira Sans",sans-serif;color:#F3F1E9}.kjo-kl-l{font-size:13px;letter-spacing:3px;text-transform:uppercase;color:#B29D75;margin:22px 0 10px}'
		. '.kjo-kl-tage{display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;scrollbar-width:thin}.kjo-kl-zeiten{display:flex;flex-wrap:wrap;gap:8px}'
		. '.kjo-kl button.kjo-kl-c{all:unset;box-sizing:border-box;cursor:pointer;flex:0 0 auto;min-height:44px;padding:10px 14px;border:1px solid rgba(178,157,117,.55);border-radius:2px;font:400 15px "Fira Sans",sans-serif;color:#F3F1E9;text-align:center}'
		. '.kjo-kl button.kjo-kl-c:hover,.kjo-kl button.kjo-kl-c:focus-visible{border-color:#B29D75}.kjo-kl button.kjo-kl-c[aria-pressed=true]{background:#B29D75;color:#0F0C07;border-color:#B29D75}'
		. '.kjo-kl-hint,.kjo-kl-lade,.kjo-kl-o,.kjo-kl-ds{color:#A79E8E;font-size:14px}.kjo-kl-ds{font-size:12px;margin-top:10px}.kjo-kl-ds a{color:#A79E8E}'
		. '.kjo-kl-art{display:flex;gap:10px;margin-bottom:12px}.kjo-kl-art label{position:relative}.kjo-kl-art input{position:absolute;opacity:0}.kjo-kl-art span{display:inline-block;padding:10px 16px;border:1px solid rgba(178,157,117,.55);cursor:pointer}.kjo-kl-art input:checked+span{background:#B29D75;color:#0F0C07}.kjo-kl-art input:focus-visible+span{outline:2px solid #F3F1E9}'
		. '.kjo-kl-g{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}@media(max-width:700px){.kjo-kl-g{grid-template-columns:1fr}}'
		. '.kjo-kl-f label{display:block;font-size:13px;color:#B29D75;letter-spacing:1px;margin-bottom:10px}.kjo-kl-f input,.kjo-kl-f textarea{display:block;width:100%;box-sizing:border-box;margin-top:6px;padding:12px;background:#0F0C07;border:1px solid rgba(178,157,117,.55);border-radius:2px;color:#F3F1E9;font:16px "Fira Sans",sans-serif}'
		. '.kjo-kl-hp{position:absolute!important;left:-9999px}.kjo-kl-err{color:#ff9b8a;min-height:1em;margin:6px 0}'
		. 'button.kjo-kl-btn{all:unset;box-sizing:border-box;cursor:pointer;display:inline-block;padding:14px 26px;background:#B29D75;color:#0F0C07;font:500 14px "Fira Sans",sans-serif;letter-spacing:1.5px;text-transform:uppercase}button.kjo-kl-btn:disabled{opacity:.6}'
		. '.kjo-kl-ok{border-left:3px solid #7fb77e;background:rgba(178,157,117,.08);padding:16px 18px;margin-top:16px;line-height:1.6}.kjo-kl-ok a{color:#B29D75}.kjo-kl [hidden]{display:none!important}</style>';
}
add_action(
	'wp_footer',
	function () {
		if ( empty( $GLOBALS['kjo_kl'] ) ) {
			return;
		}
		?>
<script id="kjo-kl-js">(function(){document.querySelectorAll(".kjo-kl").forEach(function(r){var api=r.dataset.api,T=r.querySelector(".kjo-kl-tage"),Z=r.querySelector(".kjo-kl-zeiten"),F=r.querySelector(".kjo-kl-f"),E=r.querySelector(".kjo-kl-err"),OK=r.querySelector(".kjo-kl-ok"),tag=null,zeit=null,D=[];
function btn(t,on){var b=document.createElement("button");b.type="button";b.className="kjo-kl-c";b.textContent=t;b.setAttribute("aria-pressed","false");b.onclick=on;return b}
function press(box,b){box.querySelectorAll("button").forEach(function(x){x.setAttribute("aria-pressed",x===b?"true":"false")})}
function zeiten(d){Z.innerHTML="";zeit=null;F.hidden=true;d.z.forEach(function(z){var b=btn(z+" Uhr",function(){zeit=z;press(Z,b);F.hidden=false;var a=F.querySelector("[name=name]");if(a&&!a.value)a.focus({preventScroll:true})});Z.appendChild(b)})}
fetch(api,{credentials:"omit"}).then(function(x){return x.json()}).then(function(j){D=j.tage||[];T.innerHTML="";if(!D.length){T.innerHTML='<span class="kjo-kl-hint">Gerade sind keine Zeiten frei. Schreibt mir einfach per WhatsApp oder ruft an.</span>';Z.innerHTML="";return}
 D.forEach(function(d){var b=btn(d.l,function(){tag=d.d;press(T,b);zeiten(d)});T.appendChild(b)})}).catch(function(){T.innerHTML='<span class="kjo-kl-hint">Die Zeiten konnten nicht geladen werden. Bitte ruft an oder schreibt per WhatsApp.</span>'});
function art(){var v=(F.querySelector("[name=art]:checked")||{}).value;r.querySelector(".kjo-kl-tel-o").style.visibility=v==="telefon"?"visible":"hidden"}F.addEventListener("change",art);art();
F.addEventListener("submit",function(e){e.preventDefault();E.textContent="";var d={d:tag,z:zeit};["name","email","tel","notiz","website"].forEach(function(n){d[n]=F.elements[n].value.trim()});d.art=(F.querySelector("[name=art]:checked")||{}).value;
 if(!tag||!zeit){E.textContent="Bitte Tag und Uhrzeit wählen.";return}if(!d.name||!/^\S+@\S+\.\S+$/.test(d.email)){E.textContent="Bitte Name und E-Mail angeben.";return}if(d.art==="telefon"&&d.tel.replace(/\D/g,"").length<6){E.textContent="Bitte eure Telefonnummer angeben, ich rufe euch an.";return}
 var b=F.querySelector(".kjo-kl-btn");b.disabled=true;b.textContent="wird gebucht …";
 fetch(api,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(d),credentials:"omit"}).then(function(x){return x.json().then(function(j){return{ok:x.ok,j:j}})}).then(function(x){b.disabled=false;b.textContent="Termin verbindlich buchen";
  if(!x.ok){E.textContent=(x.j&&x.j.message)||"Das hat nicht geklappt.";return}
  T.hidden=Z.hidden=F.hidden=true;r.querySelectorAll(".kjo-kl-l").forEach(function(l){l.hidden=true});
  var u="data:text/calendar;charset=utf-8,"+encodeURIComponent(x.j.ics);OK.innerHTML="<strong>Danke, euer Kennenlerngespräch ist gebucht!</strong><br>"+x.j.wann.replace(/[<>&]/g,"")+"<br>Eine Bestätigung ist per E-Mail unterwegs. <a download=\"kennenlernen-dj-kolja-one.ics\" href=\""+u+"\">In den Kalender eintragen</a>";OK.hidden=false;OK.focus();
  if(window.kjoTrack)try{kjoTrack("kennenlernen_gebucht",{art:d.art})}catch(z){}})
 .catch(function(){b.disabled=false;b.textContent="Termin verbindlich buchen";E.textContent="Keine Verbindung – bitte noch einmal versuchen."})})})})();</script>
		<?php
	},
	30
);

/* ---------- Admin ---------- */
add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=kjo_anfrage', 'Kennenlernen: Zeiten', 'Kennenlernen: Zeiten', 'edit_pages', 'kjo-kennenlernen', 'kjo_kl_admin' );
	},
	20
);
function kjo_kl_admin() {
	if ( isset( $_POST['kjo_kl_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_kl_nonce'] ) ), 'kjo_kl' ) && current_user_can( 'edit_pages' ) ) {
		$n = array( 'tage' => array() );
		for ( $w = 1; $w <= 7; $w++ ) {
			$v = sanitize_text_field( wp_unslash( $_POST['von'][ $w ] ?? '' ) ); // phpcs:ignore
			$b = sanitize_text_field( wp_unslash( $_POST['bis'][ $w ] ?? '' ) ); // phpcs:ignore
			$n['tage'][ $w ] = ( preg_match( '/^\d{2}:\d{2}$/', $v ) && preg_match( '/^\d{2}:\d{2}$/', $b ) && $v < $b ) ? array( $v, $b ) : array( '', '' );
		}
		foreach ( array( 'dauer' => array( 10, 120 ), 'raster' => array( 10, 240 ), 'vorlauf' => array( 0, 336 ), 'horizont' => array( 1, 90 ) ) as $k => $m ) {
			$n[ $k ] = max( $m[0], min( $m[1], (int) ( $_POST[ $k ] ?? 0 ) ) ); // phpcs:ignore
		}
		$n['video'] = esc_url_raw( wp_unslash( $_POST['video'] ?? '' ) ); // phpcs:ignore
		update_option( 'kjo_kl', $n, false );
		echo '<div class="notice notice-success"><p>Gespeichert.</p></div>';
	}
	$o  = kjo_kl_opts();
	$wt = array( 1 => 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag' );
	echo '<div class="wrap"><h1>Kennenlerngespräche: Zeiten</h1><p>Zu diesen Zeiten können Interessenten auf der Website (Kontaktseite) ein Gespräch buchen. Tage mit DJ-Einsatz oder privatem Termin im Kalender sind automatisch gesperrt. Leer lassen = an diesem Tag keine Termine.</p><form method="post">';
	wp_nonce_field( 'kjo_kl', 'kjo_kl_nonce' );
	echo '<table class="form-table">';
	foreach ( $wt as $w => $l ) {
		echo '<tr><th>' . esc_html( $l ) . '</th><td><input type="time" name="von[' . (int) $w . ']" value="' . esc_attr( $o['tage'][ $w ][0] ) . '"> bis <input type="time" name="bis[' . (int) $w . ']" value="' . esc_attr( $o['tage'][ $w ][1] ) . '"></td></tr>';
	}
	echo '<tr><th>Gesprächsdauer</th><td><input type="number" name="dauer" value="' . (int) $o['dauer'] . '" min="10" max="120"> Minuten</td></tr>';
	echo '<tr><th>Abstand der Startzeiten</th><td><input type="number" name="raster" value="' . (int) $o['raster'] . '" min="10" max="240"> Minuten</td></tr>';
	echo '<tr><th>Frühestens</th><td><input type="number" name="vorlauf" value="' . (int) $o['vorlauf'] . '" min="0" max="336"> Stunden im Voraus</td></tr>';
	echo '<tr><th>Buchbar für die nächsten</th><td><input type="number" name="horizont" value="' . (int) $o['horizont'] . '" min="1" max="90"> Tage</td></tr>';
	echo '<tr><th>Fester Video-Link</th><td><input type="url" class="regular-text" name="video" value="' . esc_attr( $o['video'] ) . '" placeholder="https://meet.google.com/…"><p class="description">Optional. Steht dann direkt in der Bestätigung; sonst schickst du den Link vorab selbst.</p></td></tr>';
	echo '</table><p><button class="button button-primary">Speichern</button></p></form>';
	$n = 0;
	foreach ( kjo_kl_slots() as $z ) {
		$n += count( $z );
	}
	echo '<p class="description">Aktuell buchbar: ' . (int) $n . ' Zeitpunkte.</p></div>';
}
add_filter(
	'manage_kjo_gespraech_posts_columns',
	function () {
		return array( 'cb' => '<input type="checkbox">', 'title' => 'Gespräch', 'kl_kontakt' => 'Kontakt', 'kl_status' => 'Status' );
	}
);
add_action(
	'manage_kjo_gespraech_posts_custom_column',
	function ( $c, $id ) {
		if ( 'kl_kontakt' === $c ) {
			$t = (string) get_post_meta( $id, '_kjo_kl_tel', true );
			$m = (string) get_post_meta( $id, '_kjo_kl_email', true );
			echo ( $t ? '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $t ) ) . '">' . esc_html( $t ) . '</a><br>' : '' ) . '<a href="mailto:' . esc_attr( $m ) . '">' . esc_html( $m ) . '</a>';
		} elseif ( 'kl_status' === $c ) {
			$s = (string) get_post_meta( $id, '_kjo_kl_status', true );
			$a = (int) get_post_meta( $id, '_kjo_kl_anfrage', true );
			echo esc_html( 'storniert' === $s ? 'storniert' : ( (int) get_post_meta( $id, '_kjo_kl_start', true ) < time() ? 'vorbei' : 'gebucht' ) ) . ( $a ? '<br><a href="' . esc_url( admin_url( 'post.php?post=' . $a . '&action=edit' ) ) . '">zur Anfrage</a>' : '' );
		}
	},
	10,
	2
);
add_action(
	'add_meta_boxes_kjo_gespraech',
	function () {
		add_meta_box(
			'kjo_kl_box',
			'Gespräch',
			function ( $post ) {
				$g = function ( $k ) use ( $post ) {
					return (string) get_post_meta( $post->ID, '_kjo_kl_' . $k, true );
				};
				echo '<p><strong>' . esc_html( wp_date( 'l, d.m.Y \u\m H:i \U\h\r', (int) $g( 'start' ) ) ) . '</strong> · ' . esc_html( KJO_KL_ARTEN[ $g( 'art' ) ] ?? $g( 'art' ) ) . '</p>';
				echo '<p>' . esc_html( $g( 'name' ) ) . '<br><a href="mailto:' . esc_attr( $g( 'email' ) ) . '">' . esc_html( $g( 'email' ) ) . '</a><br>' . esc_html( $g( 'tel' ) ) . '</p>';
				echo $g( 'notiz' ) ? '<p style="white-space:pre-wrap">' . esc_html( $g( 'notiz' ) ) . '</p>' : '';
				echo '<p><label><input type="checkbox" name="kjo_kl_storno" value="1"' . checked( 'storniert', $g( 'status' ), false ) . '> Storniert (Zeit wird wieder frei)</label></p>';
				wp_nonce_field( 'kjo_kl_box', 'kjo_kl_box_nonce' );
			},
			'kjo_gespraech',
			'normal',
			'high'
		);
	}
);
add_action(
	'save_post_kjo_gespraech',
	function ( $id ) {
		if ( ! isset( $_POST['kjo_kl_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_kl_box_nonce'] ) ), 'kjo_kl_box' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		update_post_meta( $id, '_kjo_kl_status', empty( $_POST['kjo_kl_storno'] ) ? 'gebucht' : 'storniert' );
	}
);
