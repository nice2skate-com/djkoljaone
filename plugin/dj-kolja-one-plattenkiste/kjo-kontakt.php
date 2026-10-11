<?php
/**
 * Eigenes Anfrageformular „Wunschtermin prüfen“ (seit 1.31.0) – ersetzt WPForms (Formular 5020).
 *
 * - Shortcode [kjo_kontakt]; der alte Shortcode [wpforms id="5020"] zeigt automatisch dieses Formular
 *   (zurück zu WPForms: add_filter( 'kjo_kontakt_statt_wpforms', '__return_false' ); ).
 * - Jede Anfrage wird im Admin unter „DJ-Anfragen“ gespeichert UND per E-Mail an anfrage@… geschickt
 *   (Antworten gehen per Reply-To direkt an den Kunden). Der Kunde erhält eine Bestätigung.
 * - Wunschdatum mit Verfügbarkeits-Hinweis aus dem Kalender; Knopf „Als vorreserviert eintragen“ im Admin.
 * - Tracking unverändert: generate_lead {method: formular} über window.kjoTrack (GA4/Google Ads, Clarity).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_KF_ANLAESSE = array( 'Hochzeit', 'Firmenfeier', 'Geburtstag / Party', 'Sonstiges' );

add_action(
	'init',
	function () {
		register_post_type(
			'kjo_anfrage',
			array(
				'labels'              => array(
					'name'          => 'DJ-Anfragen',
					'singular_name' => 'DJ-Anfrage',
					'menu_name'     => 'DJ-Anfragen',
					'all_items'     => 'Alle DJ-Anfragen',
					'edit_item'     => 'DJ-Anfrage',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 24,
				'menu_icon'           => 'dashicons-email-alt',
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'page',
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'        => true,
			)
		);
		if ( ! shortcode_exists( 'wpforms' ) ) {
			add_shortcode(
				'wpforms',
				function ( $a ) {
					return kjo_kf_ersetzt( $a ) ? kjo_kf_form() : '';
				}
			); // WPForms deaktiviert: Formular trotzdem anzeigen.
		}
	},
	20
);
add_shortcode( 'kjo_kontakt', 'kjo_kf_form' );
function kjo_kf_ersetzt( $a ) {
	return apply_filters( 'kjo_kontakt_statt_wpforms', true ) && is_array( $a ) && isset( $a['id'] ) && 5020 === (int) $a['id'];
}
add_filter(
	'pre_do_shortcode_tag',
	function ( $out, $tag, $a ) {
		return 'wpforms' === $tag && kjo_kf_ersetzt( $a ) ? kjo_kf_form() : $out;
	},
	10,
	3
);

/* ---------------------------------------------------------------
 * Formular
 * ------------------------------------------------------------- */
function kjo_kf_form( $atts = array() ) {
	$pre = is_array( $atts ) && isset( $atts['anlass'] ) ? (string) $atts['anlass'] : '';
	$api = esc_url( rest_url( 'kjo/v1/kontakt' ) );
	$opt = '';
	foreach ( KJO_KF_ANLAESSE as $a ) {
		$opt .= '<option' . selected( $pre, $a, false ) . '>' . esc_html( $a ) . '</option>';
	}
	$f = function ( $id, $label, $input, $cls = '' ) {
		return '<div class="kjo-kf-f' . ( $cls ? ' ' . $cls : '' ) . '"><label for="kjo-kf-' . $id . '">' . $label . '</label>' . $input . '</div>';
	};
	$req = ' <span class="kjo-kf-r" aria-hidden="true">*</span>';
	$akt = array();
	foreach ( function_exists( 'kjo_aktionen' ) ? kjo_aktionen() : array() as $c => $a ) {
		$akt[ $c ] = array( $a['kurz'], $a['ende'] * 1000 );
	}
	$h   = '<form class="kjo-kf" id="kjo-kf" novalidate data-api="' . $api . '" data-akt="' . esc_attr( wp_json_encode( (object) $akt ) ) . '">';
	$h  .= '<div class="kjo-kf-g">';
	$h  .= $f( 'vn', 'Vorname' . $req, '<input id="kjo-kf-vn" name="vorname" type="text" autocomplete="given-name" required maxlength="80">' );
	$h  .= $f( 'nn', 'Nachname' . $req, '<input id="kjo-kf-nn" name="nachname" type="text" autocomplete="family-name" required maxlength="80">' );
	$h  .= $f( 'em', 'E-Mail-Adresse' . $req, '<input id="kjo-kf-em" name="email" type="email" autocomplete="email" required maxlength="120">' );
	$h  .= $f( 'tel', 'Telefon <span class="kjo-kf-o">(optional, für schnellere Abklärung)</span>', '<input id="kjo-kf-tel" name="telefon" type="tel" autocomplete="tel" maxlength="40">' );
	$h  .= $f( 'dt', 'Wunschdatum' . $req, '<input id="kjo-kf-dt" name="datum" type="date" required min="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"><button type="button" class="kjo-kf-offen" aria-pressed="false">Noch kein festes Datum?</button><div class="kjo-kf-av" aria-live="polite"></div>', 'kjo-kf-w' );
	$h  .= $f( 'ort', 'Ort / Location <span class="kjo-kf-o">(voraussichtlich)</span>' . $req, '<input id="kjo-kf-ort" name="ort" type="text" required maxlength="150">' );
	$h  .= $f( 'gs', 'Gästezahl <span class="kjo-kf-o">(voraussichtlich)</span>' . $req, '<input id="kjo-kf-gs" name="gaeste" type="text" inputmode="numeric" required maxlength="40" placeholder="z. B. 80">' );
	$h  .= $f( 'an', 'Anlass' . $req, '<select id="kjo-kf-an" name="anlass" required><option value="">– Auswahl treffen –</option>' . $opt . '</select>', 'kjo-kf-w' );
	$h  .= $f( 'msg', 'Kommentar oder Nachricht', '<textarea id="kjo-kf-msg" name="nachricht" rows="5" maxlength="3000" placeholder="Was ist euch wichtig? Musikrichtung, Ablauf, Fragen …"></textarea>', 'kjo-kf-w' );
	$h  .= '</div>';
	$h  .= '<div class="kjo-kf-hp" aria-hidden="true"><label>Website <input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>';
	$h  .= '<label class="kjo-kf-ds"><input type="checkbox" name="datenschutz" value="1" required> <span>Ich habe die <a href="' . esc_url( home_url( '/datenschutz/' ) ) . '" target="_blank" rel="noopener">Datenschutzerklärung</a> gelesen und bin einverstanden.' . $req . '</span></label>';
	$h  .= '<div class="kjo-kf-msgbox" role="alert" hidden></div>';
	$h  .= '<button type="submit" class="kjo-kf-send">Anfrage senden</button>';
	$h  .= '<noscript><p class="kjo-kf-o">Bitte JavaScript aktivieren – oder schreibt mir direkt per WhatsApp oder E-Mail.</p></noscript>';
	$h  .= '</form>';
	$h  .= '<div class="kjo-kf-ok" id="kjo-kf-ok" hidden tabindex="-1"><p class="kjo-kf-okt">Danke, eure Anfrage ist angekommen!</p><p>Ich prüfe euren Termin und melde mich innerhalb von 24 Stunden persönlich bei euch. Eine Bestätigung ist per E-Mail unterwegs – schaut sicherheitshalber auch im Spam-Ordner nach.</p></div>';
	return '<style id="kjo-kf-css">' . KJO_KF_CSS . '</style>' . $h . '<script id="kjo-kf-js">' . KJO_KF_JS . '</script>';
}

define(
	'KJO_KF_CSS',
	'.kjo-kf{font-family:"Fira Sans",sans-serif;color:#F3F1E9}.kjo-kf-g{display:grid;grid-template-columns:1fr 1fr;gap:18px 20px}.kjo-kf-w{grid-column:1/-1}@media(max-width:600px){.kjo-kf-g{grid-template-columns:1fr}}'
	. '.kjo-kf-f label{display:block;margin:0 0 7px;font-size:15px;font-weight:400;color:#F3F1E9}.kjo-kf-r{color:#B29D75}.kjo-kf-o{color:#A39E93;font-size:13px;font-weight:300}'
	. '.kjo-kf input[type=text],.kjo-kf input[type=email],.kjo-kf input[type=tel],.kjo-kf input[type=date],.kjo-kf select,.kjo-kf textarea{display:block;width:100%;box-sizing:border-box;background:#0F0C07;color:#F3F1E9;border:1px solid rgba(178,157,117,.45);border-radius:2px;padding:12px 14px;font:16px/1.4 "Fira Sans",sans-serif;min-height:48px;margin:0;color-scheme:dark}'
	. '.kjo-kf input:focus,.kjo-kf select:focus,.kjo-kf textarea:focus{outline:none;border-color:#B29D75;box-shadow:0 0 0 1px #B29D75}.kjo-kf ::placeholder{color:#6f6a60}.kjo-kf [aria-invalid=true]{border-color:#E0B36A;box-shadow:0 0 0 1px #E0B36A}'
	. '.kjo-kf input[type=date]::-webkit-calendar-picker-indicator{filter:invert(72%) sepia(18%) saturate(500%) hue-rotate(5deg);cursor:pointer}.kjo-kf input[type=date]:disabled{opacity:.45}'
	. '.kjo-kf-offen{background:none;border:0;padding:8px 0 0;color:#B29D75;font:13px "Fira Sans",sans-serif;text-decoration:underline;cursor:pointer}'
	. '.kjo-kf-av:empty{display:none}.kjo-kf-av{margin:10px 0 0;padding:10px 14px;border-left:3px solid #B29D75;background:rgba(178,157,117,.08);font-size:15px;line-height:1.55}.kjo-kf-av.ok{border-color:#7fb77e}.kjo-kf-av.vor{border-color:#E0B36A}.kjo-kf-av.no{border-color:#ff9b8a}.kjo-kf-av a{color:#B29D75}.kjo-kf-av strong{font-weight:500}'
	. '.kjo-kf-akt{margin:0 0 20px;padding:12px 16px;border:1px solid #B29D75;background:rgba(178,157,117,.1);color:#F3F1E9;font-size:15px;line-height:1.5}.kjo-kf-akt strong{color:#B29D75;font-weight:500}'
	. '.kjo-kf-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}'
	. '.kjo-kf-ds{display:flex;gap:10px;align-items:flex-start;margin:22px 0 0;font-size:15px;line-height:1.5;cursor:pointer}.kjo-kf-ds input{width:20px;height:20px;margin:2px 0 0;flex:none;accent-color:#B29D75}.kjo-kf-ds a{color:#B29D75}'
	. '.kjo-kf-msgbox{margin:16px 0 0;padding:12px 14px;border-left:3px solid #E0B36A;background:rgba(224,179,106,.08);font-size:15px}'
	. '.kjo-kf-send{margin:22px 0 0;background:#B29D75;color:#0F0C07;border:1px solid #B29D75;border-radius:2px;padding:15px 34px;font:500 16px "Fira Sans",sans-serif;letter-spacing:.04em;cursor:pointer;transition:background .2s}.kjo-kf-send:hover,.kjo-kf-send:focus-visible{background:#C4B08A;outline:none}.kjo-kf-send[disabled]{opacity:.6;cursor:wait}'
	. '.kjo-kf-ok{background:#1A1712;border:1px solid #B29D75;padding:26px 28px;color:#F3F1E9;font:300 18px/1.6 "Fira Sans",sans-serif}.kjo-kf-ok p{margin:0 0 10px}.kjo-kf-okt{font-size:24px;color:#B29D75}'
);
define(
	'KJO_KF_JS',
	<<<'KJOKF'
(function(){var f=document.getElementById("kjo-kf");if(!f||f.dataset.on)return;f.dataset.on=1;
var ok=document.getElementById("kjo-kf-ok"),box=f.querySelector(".kjo-kf-msgbox"),btn=f.querySelector(".kjo-kf-send"),dt=f.querySelector("#kjo-kf-dt"),off=f.querySelector(".kjo-kf-offen"),av=f.querySelector(".kjo-kf-av"),ts=Math.floor(Date.now()/1000),offen=false;
function val(n){var e=f.elements[n];return e?(e.type==="checkbox"?(e.checked?"1":""):String(e.value||"").trim()):"";}
function msg(t){box.textContent=t;box.hidden=!t;}
var akt="";try{var A=JSON.parse(f.getAttribute("data-akt")||"{}"),q=(new URLSearchParams(location.search).get("aktion")||"").toUpperCase();if(A[q]&&Date.now()<A[q][1]){akt=q;var n=document.createElement("p");n.className="kjo-kf-akt";n.innerHTML="🎁 Aktion <strong>"+q+"</strong> ist vorgemerkt: "+A[q][0]+".";f.insertBefore(n,f.firstChild);}}catch(x){}
try{var pk=new URLSearchParams(location.search).get("paket"),tm=f.elements.nachricht;if(pk&&tm&&!tm.value)tm.value="Technik-Paket: "+pk.slice(0,120)+"\n";}catch(x){}
function hint(c,h){av.className="kjo-kf-av "+c;av.innerHTML=h;}
function check(v){var K=window.KJO_KAL;av.innerHTML="";if(!K||!window.fetch||!v)return;fetch(K.api+(K.api.indexOf("?")>-1?"&":"?")+"von="+v,{credentials:"omit"}).then(function(r){return r.json()}).then(function(j){if(dt.value!==v||!j||!j.dj)return;
 if(j.dj==="frei")hint("ok","✓ <strong>Termin noch verfügbar!</strong> Schickt mir eure Anfrage, ich melde mich innerhalb von 24 Stunden bei euch.");
 else if(j.dj==="vor")hint("vor","◐ <strong>Termin vorreserviert, fragt gerne trotzdem an.</strong> Wird die Reservierung nicht bestätigt, seid ihr als Nächste dran.");
 else if(j.equip!=="belegt")hint("no","✕ <strong>An diesem Tag bin ich leider schon ausgebucht.</strong> Musikanlage und Licht sind aber "+(j.equip==="teil"?"teilweise ":"")+"noch frei: <a href=\""+K.mieten+"?von="+v+"#pakete\">Nur Equipment mieten? →</a>");
 else hint("no","✕ <strong>An diesem Tag bin ich leider schon ausgebucht.</strong> Gerne frage ich in meinem DJ-Netzwerk nach einem Kollegen für euch, oder ihr wählt einen anderen Tag.");}).catch(function(){});}
dt.addEventListener("change",function(){check(dt.value);});
dt.addEventListener("click",function(){try{dt.showPicker&&dt.showPicker();}catch(e){}});
off.addEventListener("click",function(){offen=!offen;dt.disabled=offen;dt.required=!offen;off.setAttribute("aria-pressed",offen?"true":"false");off.textContent=offen?"Doch ein festes Datum wählen":"Noch kein festes Datum?";if(offen){dt.value="";av.innerHTML="";dt.removeAttribute("aria-invalid");var m=f.elements.nachricht;if(!m.value)m.setAttribute("placeholder","Zeitraum oder mögliche Termine, z. B. „Sommer 2027, ein Samstag“");m.focus();}});
f.addEventListener("input",function(e){if(!box.hidden)msg("");if(e.target.getAttribute("aria-invalid")==="true"&&e.target.checkValidity())e.target.removeAttribute("aria-invalid");});
f.addEventListener("submit",function(e){e.preventDefault();msg("");var bad=null;
 [].forEach.call(f.querySelectorAll("[required]"),function(el){var good=el.checkValidity();el.setAttribute("aria-invalid",good?"false":"true");if(!good&&!bad)bad=el;});
 if(bad){msg(bad.type==="checkbox"?"Bitte bestätigt die Datenschutzerklärung.":bad.type==="email"?"Bitte prüft eure E-Mail-Adresse.":"Bitte füllt alle Pflichtfelder (*) aus.");try{bad.focus();}catch(x){}return;}
 btn.disabled=true;btn.textContent="Wird gesendet …";
 var d={vorname:val("vorname"),nachname:val("nachname"),email:val("email"),telefon:val("telefon"),datum:offen?"":val("datum"),offen:offen?1:0,ort:val("ort"),gaeste:val("gaeste"),anlass:val("anlass"),nachricht:val("nachricht"),datenschutz:val("datenschutz"),website:val("website"),aktion:akt,ts:ts,seite:location.pathname};
 fetch(f.getAttribute("data-api"),{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(d),credentials:"omit"}).then(function(r){return r.json().catch(function(){return{ok:false};});}).then(function(j){
  if(j&&j.ok){f.hidden=true;ok.hidden=false;try{ok.focus({preventScroll:true});}catch(x){}ok.scrollIntoView({behavior:"smooth",block:"center"});
   try{if(typeof window.kjoTrack==="function")window.kjoTrack("generate_lead",{method:"formular"});}catch(x){}}
  else{msg((j&&j.message)||"Die Anfrage konnte nicht gesendet werden. Bitte schreibt mir per WhatsApp oder ruft an.");btn.disabled=false;btn.textContent="Anfrage senden";}
 }).catch(function(){msg("Keine Verbindung. Bitte versucht es noch einmal oder schreibt mir per WhatsApp.");btn.disabled=false;btn.textContent="Anfrage senden";});
});})();
KJOKF
);

/* ---------------------------------------------------------------
 * Empfang (REST)
 * ------------------------------------------------------------- */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/kontakt',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'kjo_kf_rest',
			)
		);
	}
);
function kjo_kf_err( $m, $code = 400 ) {
	return new WP_REST_Response( array( 'ok' => false, 'message' => $m ), $code );
}
function kjo_kf_rest( WP_REST_Request $req ) {
	$d = $req->get_json_params();
	$d = is_array( $d ) ? $d : array();
	$s = function ( $k, $ta = false ) use ( $d ) {
		$v = isset( $d[ $k ] ) && is_scalar( $d[ $k ] ) ? (string) $d[ $k ] : '';
		return mb_substr( $ta ? sanitize_textarea_field( $v ) : sanitize_text_field( $v ), 0, $ta ? 3000 : 200 );
	};
	if ( '' !== $s( 'website' ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 ); // Spam-Falle: still verwerfen.
	}
	$ts = isset( $d['ts'] ) ? (int) $d['ts'] : 0;
	if ( $ts && time() - $ts < 3 ) {
		return kjo_kf_err( 'Bitte noch einmal absenden.' );
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk = 'kjo_kf_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 20 );
	$rl = (int) get_transient( $rk );
	if ( $rl >= 5 ) {
		return kjo_kf_err( 'Es wurden gerade sehr viele Anfragen gesendet. Bitte versucht es später noch einmal oder schreibt mir per WhatsApp.', 429 );
	}
	$name   = trim( $s( 'vorname' ) . ' ' . $s( 'nachname' ) );
	$email  = sanitize_email( $s( 'email' ) );
	$offen  = ! empty( $d['offen'] );
	$datum  = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s( 'datum' ) ) ? $s( 'datum' ) : '';
	$anlass = in_array( $s( 'anlass' ), KJO_KF_ANLAESSE, true ) ? $s( 'anlass' ) : '';
	if ( '' === $s( 'vorname' ) || '' === $s( 'nachname' ) || ! is_email( $email ) || ( ! $datum && ! $offen ) || '' === $s( 'ort' ) || '' === $s( 'gaeste' ) || ! $anlass ) {
		return kjo_kf_err( 'Bitte füllt alle Pflichtfelder (*) aus.' );
	}
	if ( '1' !== $s( 'datenschutz' ) ) {
		return kjo_kf_err( 'Bitte bestätigt die Datenschutzerklärung.' );
	}
	if ( $datum && $datum < wp_date( 'Y-m-d' ) ) {
		return kjo_kf_err( 'Das Wunschdatum liegt in der Vergangenheit.' );
	}
	set_transient( $rk, $rl + 1, HOUR_IN_SECONDS );
	$de = '';
	if ( $datum ) {
		$t  = DateTime::createFromFormat( 'Y-m-d', $datum, wp_timezone() );
		$wt = array( 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' );
		$de = $t ? $wt[ (int) $t->format( 'w' ) ] . ', ' . $t->format( 'd.m.Y' ) : $datum;
	}
	$verf = '';
	if ( $datum && function_exists( 'kjo_kal_status' ) ) {
		$st   = kjo_kal_status( $datum, $datum );
		$verf = is_array( $st ) && isset( $st['dj'] ) ? array( 'frei' => 'frei', 'vor' => 'vorreserviert', 'belegt' => 'BELEGT' )[ $st['dj'] ] ?? '' : '';
	}
	$akt   = function_exists( 'kjo_aktion_finden' ) ? kjo_aktion_finden( $s( 'aktion' ) . ' ' . $s( 'nachricht', true ) ) : '';
	$text  = "Neue DJ-Anfrage über dj-kolja-one.de\n\n";
	$text .= $akt ? '*** AKTION ' . $akt . ': ' . kjo_aktionen()[ $akt ]['titel'] . " ***\n\n" : '';
	$text .= 'Anlass: ' . $anlass . "\n";
	$text .= 'Wunschdatum: ' . ( $datum ? $de : 'noch offen' ) . ( $verf ? ' (Kalender: ' . $verf . ')' : '' ) . "\n";
	$text .= 'Ort / Location: ' . $s( 'ort' ) . "\n";
	$text .= 'Gästezahl: ' . $s( 'gaeste' ) . "\n";
	$text .= "\nName: " . $name . "\nE-Mail: " . $email . "\n";
	$text .= $s( 'telefon' ) ? 'Telefon: ' . $s( 'telefon' ) . "\n" : '';
	$text .= $s( 'nachricht', true ) ? "\nNachricht:\n" . $s( 'nachricht', true ) . "\n" : '';
	$text .= "\nGesendet: " . wp_date( 'd.m.Y, H:i' ) . ' Uhr' . ( $s( 'seite' ) ? ' · Seite: ' . $s( 'seite' ) : '' ) . "\n";
	$pid   = wp_insert_post(
		array(
			'post_type'   => 'kjo_anfrage',
			'post_status' => 'private',
			'post_title'  => ( $datum ? $de : 'Datum offen' ) . ' – ' . $anlass . ' – ' . $name,
		)
	);
	if ( $pid && ! is_wp_error( $pid ) ) {
		update_post_meta( $pid, '_kjo_text', $text );
		update_post_meta( $pid, '_kjo_von', $datum );
		update_post_meta( $pid, '_kjo_name', $name );
		update_post_meta( $pid, '_kjo_email', $email );
		update_post_meta( $pid, '_kjo_anlass', $anlass );
		update_post_meta( $pid, '_kjo_ort', $s( 'ort' ) );
		if ( $akt ) {
			update_post_meta( $pid, '_kjo_aktion', $akt );
		}
	} else {
		$pid = 0;
	}
	$to   = function_exists( 'kjo_vl_mail_to' ) ? kjo_vl_mail_to() : get_option( 'admin_email' );
	$hdrs = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>', ',' ), '', $name ) . ' <' . $email . '>' );
	$subj = 'Anfrage ' . $anlass . ': ' . ( $datum ? $de : 'Datum offen' ) . ' – ' . $name;
	$ok   = wp_mail( $to, $subj, $text . ( $pid ? "\nIm Admin: " . admin_url( 'post.php?post=' . (int) $pid . '&action=edit' ) . "\n" : '' ), $hdrs );
	if ( $pid ) {
		update_post_meta( $pid, '_kjo_mail_ok', $ok ? '1' : '0' );
	}
	$conf = 'Hallo ' . $s( 'vorname' ) . ",\n\ndanke für eure Anfrage! Ich prüfe euren Termin und melde mich innerhalb von 24 Stunden persönlich bei euch.\n\n"
		. "Eure Angaben:\nAnlass: " . $anlass . "\nWunschdatum: " . ( $datum ? $de : 'noch offen' ) . "\nOrt / Location: " . $s( 'ort' ) . "\nGästezahl: " . $s( 'gaeste' ) . "\n\n"
		. "Ihr wollt etwas ergänzen? Antwortet einfach auf diese E-Mail oder schreibt mir per WhatsApp: https://wa.me/491727273707\n\nViele Grüße\nKolja – DJ KOLJA ONE\n" . $to . "\nhttps://dj-kolja-one.de\n";
	wp_mail( $email, 'Eure Anfrage bei DJ KOLJA ONE', $conf, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: DJ KOLJA ONE <' . $to . '>' ) );
	if ( ! $ok && ! $pid ) {
		return kjo_kf_err( 'Die Anfrage konnte nicht gesendet werden. Bitte schreibt mir per WhatsApp oder ruft an.', 500 );
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* ---------------------------------------------------------------
 * Admin: Ansicht, Spalten, Kalender-Knopf
 * ------------------------------------------------------------- */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_kf_text', 'Anfrage', 'kjo_kf_box', 'kjo_anfrage', 'normal', 'high' );
		add_meta_box( 'kjo_kf_kal', 'Kalender', 'kjo_kf_box_kal', 'kjo_anfrage', 'side', 'high' );
	}
);
function kjo_kf_box( $post ) {
	$mail = get_post_meta( $post->ID, '_kjo_mail_ok', true );
	if ( '0' === $mail ) {
		echo '<div class="notice notice-error inline"><p><strong>Die E-Mail zu dieser Anfrage konnte nicht verschickt werden.</strong> Bitte E-Mail-Versand prüfen (z. B. Plugin „WP Mail SMTP“ mit dem Strato-Postfach).</p></div>';
	}
	$em = (string) get_post_meta( $post->ID, '_kjo_email', true );
	echo '<pre style="white-space:pre-wrap;font:14px/1.6 system-ui,sans-serif;margin:0">' . esc_html( (string) get_post_meta( $post->ID, '_kjo_text', true ) ) . '</pre>';
	if ( $em ) {
		echo '<p><a class="button" href="' . esc_url( 'mailto:' . $em . '?subject=' . rawurlencode( 'Eure Anfrage bei DJ KOLJA ONE' ) ) . '">Per E-Mail antworten</a></p>';
	}
}
function kjo_kf_box_kal( $post ) {
	$von = (string) get_post_meta( $post->ID, '_kjo_von', true );
	$tid = (int) get_post_meta( $post->ID, '_kjo_termin', true );
	if ( $tid && get_post( $tid ) ) {
		echo '<p>✓ Im Kalender eingetragen: <a href="' . esc_url( get_edit_post_link( $tid ) ) . '">Termin öffnen</a></p>';
		return;
	}
	if ( ! $von || ! function_exists( 'kjo_kal_save' ) ) {
		echo '<p>Kein festes Wunschdatum angegeben.</p>';
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=kjo_kf_termin&id=' . $post->ID ), 'kjo_kf_termin_' . $post->ID );
	echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Als vorreserviert eintragen</a></p><p class="description">Trägt den Tag als DJ-Termin „vorreserviert“ ein (mit komplettem Equipment) – anpassbar im Termin.</p>';
}
add_action(
	'admin_post_kjo_kf_termin',
	function () {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! check_admin_referer( 'kjo_kf_termin_' . $id ) || ! function_exists( 'kjo_kal_save' ) ) {
			wp_die( 'Keine Berechtigung.' );
		}
		$von = (string) get_post_meta( $id, '_kjo_von', true );
		$tid = kjo_kal_save(
			array(
				'von'    => $von,
				'bis'    => $von,
				'art'    => 'dj',
				'status' => 'vor',
				'equip'  => 'all',
				'notiz'  => trim( get_post_meta( $id, '_kjo_name', true ) . ' · ' . get_post_meta( $id, '_kjo_anlass', true ) . ' · ' . get_post_meta( $id, '_kjo_ort', true ), ' ·' ),
			)
		);
		if ( $tid ) {
			update_post_meta( $id, '_kjo_termin', $tid );
		}
		wp_safe_redirect( $tid ? get_edit_post_link( $tid, 'url' ) : get_edit_post_link( $id, 'url' ) );
		exit;
	}
);
add_filter(
	'manage_kjo_anfrage_posts_columns',
	function ( $c ) {
		return array(
			'cb'           => $c['cb'],
			'title'        => 'Anfrage',
			'kjo_kf_mail'  => 'E-Mail versendet',
			'date'         => 'Eingang',
		);
	}
);
add_action(
	'manage_kjo_anfrage_posts_custom_column',
	function ( $col, $id ) {
		if ( 'kjo_kf_mail' === $col ) {
			echo '0' === get_post_meta( $id, '_kjo_mail_ok', true ) ? '<strong style="color:#d63638">nein</strong>' : 'ja';
		}
	},
	10,
	2
);
