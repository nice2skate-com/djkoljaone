<?php
/**
 * Meta-Pixel (Facebook/Instagram) – seit 1.61.0.
 *
 * - Lädt erst, wenn Besucher im Complianz-Banner „Marketing“ zugestimmt haben (ohne Complianz: nie).
 * - Ereignisse kommen aus window.kjoTrack (dj-kolja-one-plattenkiste.php) über das DOM-Ereignis „kjo:track“:
 *     generate_lead → Lead · kennenlernen_gebucht → Schedule · click_whatsapp/phone/email → Contact
 *     song_play → SongPlay (eigenes Ereignis, mit Songname) · share → SongShare
 *   Dazu PageView auf jeder Seite und ViewContent auf Leistungs-, Orts-, Verleih- und Musikseiten.
 * - Domain-Bestätigung: <meta name="facebook-domain-verification"> (unabhängig von der Zustimmung, setzt nichts).
 * Einstellungen: Social → Meta-Pixel. Ohne Pixel-ID passiert nichts.
 */

defined( 'ABSPATH' ) || exit;

function kjo_meta_opts() {
	$o = get_option( 'kjo_meta', array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), array( 'pixel' => '', 'domain' => '', 'aktiv' => 1 ) );
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'kjo-social', 'Meta-Pixel', 'Meta-Pixel', 'manage_options', 'kjo-meta-pixel', 'kjo_meta_page' );
	},
	20
);

function kjo_meta_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = kjo_meta_opts();
	if ( isset( $_POST['kjo_meta_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_meta_nonce'] ) ), 'kjo_meta' ) ) {
		$p           = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['pixel'] ?? '' ) ) ); // phpcs:ignore
		$d           = trim( (string) wp_unslash( $_POST['domain'] ?? '' ) ); // phpcs:ignore -- roh, damit ein eingefügter <meta>-Tag nicht weggefiltert wird; danach nur [A-Za-z0-9_-]
		$d           = preg_match( '/content\s*=\s*["\']?([A-Za-z0-9_-]+)/i', $d, $m ) ? $m[1] : preg_replace( '/[^A-Za-z0-9_-]/', '', $d ); // ganzer Meta-Tag oder nur der Wert
		$o['pixel']  = strlen( $p ) >= 10 && strlen( $p ) <= 20 ? $p : '';
		$o['domain'] = $d;
		$o['aktiv']  = empty( $_POST['aktiv'] ) ? 0 : 1;
		update_option( 'kjo_meta', $o, false );
		if ( function_exists( 'kjo_cache_leeren' ) ) {
			kjo_cache_leeren();
		}
		echo '<div class="notice notice-success"><p>Gespeichert' . ( '' !== $p && '' === $o['pixel'] ? ' – die Pixel-ID war ungültig (10–20 Ziffern) und wurde nicht übernommen' : '' ) . '.</p></div>';
	}
	$complianz = defined( 'cmplz_plugin' ) || defined( 'CMPLZ_VERSION' ) || function_exists( 'cmplz_has_consent' );
	$ok        = '<span style="color:#008a20">✔</span> ';
	$no        = '<span style="color:#b32d2e">✘</span> ';
	echo '<div class="wrap"><h1>Meta-Pixel</h1>';
	echo '<p>Misst, was Besucher aus Instagram- und Facebook-Anzeigen auf der Website tun – damit Meta auf echte Anfragen optimieren und gezielt nachfassen kann (z. B. alle, die „Electric Blue“ gehört haben). Der Pixel lädt <strong>nur nach Zustimmung</strong> im Cookie-Banner (Kategorie Marketing).</p>';
	echo '<ul style="line-height:1.9">';
	echo '<li>' . ( $o['pixel'] && $o['aktiv'] ? $ok . 'Pixel aktiv' : $no . 'Pixel aus' . ( $o['pixel'] ? ' (abgeschaltet)' : ' – noch keine Pixel-ID' ) ) . '</li>';
	echo '<li>' . ( $complianz ? $ok . 'Complianz erkannt – der Pixel wartet auf die Zustimmung „Marketing“' : $no . 'Complianz nicht aktiv – ohne Cookie-Banner lädt der Pixel nicht' ) . '</li>';
	echo '<li>' . ( $o['domain'] ? $ok . 'Domain-Bestätigung eingebaut' : $no . 'Domain-Bestätigung fehlt (optional, aber empfohlen)' ) . '</li>';
	echo '</ul><form method="post"><table class="form-table">';
	wp_nonce_field( 'kjo_meta', 'kjo_meta_nonce' );
	echo '<tr><th><label for="kjo-meta-pixel">Pixel-ID</label></th><td><input id="kjo-meta-pixel" name="pixel" value="' . esc_attr( $o['pixel'] ) . '" class="regular-text" inputmode="numeric" placeholder="z. B. 1234567890123456"><p class="description">Events Manager → Datenquellen → dein Pixel → Einstellungen → „Pixel-ID“ bzw. „Datensatz-ID“. Den Code von Meta nicht zusätzlich einbauen.</p></td></tr>';
	echo '<tr><th><label for="kjo-meta-domain">Domain-Bestätigung</label></th><td><input id="kjo-meta-domain" name="domain" value="' . esc_attr( $o['domain'] ) . '" class="regular-text" placeholder="Wert oder ganzer Meta-Tag"><p class="description">Unternehmenseinstellungen → Markensicherheit → Domains → dj-kolja-one.de → Meta-Tag. Du kannst den ganzen Tag einfügen, übernommen wird nur der Wert. Danach bei Meta auf „Bestätigen“.</p></td></tr>';
	echo '<tr><th>Status</th><td><label><input type="checkbox" name="aktiv" value="1"' . checked( $o['aktiv'], 1, false ) . '> Pixel einschalten</label></td></tr>';
	echo '</table><p><button class="button button-primary">Speichern</button></p></form>';
	echo '<h2>Gemessene Ereignisse</h2><table class="widefat striped" style="max-width:760px"><thead><tr><th>Meta-Ereignis</th><th>Wann</th></tr></thead><tbody>';
	foreach ( array(
		array( 'PageView', 'jede Seite' ),
		array( 'ViewContent', 'Leistungsseiten, Ortsseiten, Verleih/Equipment und Meine Musik' ),
		array( 'SongPlay (eigenes)', 'ein Song startet im Pult – mit Songname, z. B. „Electric Blue“' ),
		array( 'SongShare (eigenes)', 'ein Song wird geteilt' ),
		array( 'Contact', 'Klick auf WhatsApp, Telefon oder E-Mail' ),
		array( 'Schedule', 'Kennenlerngespräch gebucht' ),
		array( 'Lead', 'Anfrageformular oder Mietanfrage abgeschickt' ),
	) as $r ) {
		echo '<tr><td><code>' . esc_html( $r[0] ) . '</code></td><td>' . esc_html( $r[1] ) . '</td></tr>';
	}
	echo '</tbody></table><h2>Testen</h2><ol><li>Events Manager → dein Pixel → <strong>Ereignisse testen</strong> → <code>https://dj-kolja-one.de/meine-musik/</code> öffnen.</li><li>Im Cookie-Banner zustimmen (Marketing), einen Song abspielen, WhatsApp antippen.</li><li>Im Events Manager erscheinen PageView, ViewContent, SongPlay und Contact.</li></ol></div>';
}

/* Domain-Bestätigung im <head> */
add_action(
	'wp_head',
	function () {
		$o = kjo_meta_opts();
		if ( $o['domain'] && ! is_admin() ) {
			echo '<meta name="facebook-domain-verification" content="' . esc_attr( $o['domain'] ) . '">' . "\n";
		}
	},
	1
);

/* Pixel – nach dem Tracking-Helfer (wp_footer, Priorität 30) */
add_action(
	'wp_footer',
	function () {
		$o = kjo_meta_opts();
		if ( is_admin() || ! $o['pixel'] || ! $o['aktiv'] ) {
			return;
		}
		$js = <<<'KJOMETA'
(function(){
var ID=__ID__,on=false,P=location.pathname;
var VC=[[/^\/(hochzeits|geburtstags|firmenfeier|event)-dj\//,"DJ-Leistung"],[/^\/dj-[a-z-]+\//,"Region"],[/^\/meine-musik\//,"Musik"],[/^\/(technik-mieten|equipment)\//,"Verleih"]];
function ok(){try{if(typeof window.cmplz_has_consent==="function")return!!window.cmplz_has_consent("marketing")}catch(e){}return/(?:^|;\s*)cmplz_marketing=allow/.test(document.cookie)}
function start(){if(on||!ok())return;on=true;
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");
fbq("init",ID);fbq("track","PageView");
for(var i=0;i<VC.length;i++)if(VC[i][0].test(P)){fbq("track","ViewContent",{content_name:document.title,content_category:VC[i][1]});break}}
function map(n,p){
 if(n==="generate_lead")return["track","Lead",{content_name:p.method||"formular"}];
 if(n==="kennenlernen_gebucht")return["track","Schedule",{content_name:p.art||""}];
 if(n==="click_whatsapp"||n==="click_phone"||n==="click_email")return["track","Contact",{content_name:p.method||n}];
 if(n==="song_play")return["trackCustom","SongPlay",{content_name:p.item_name||"",content_ids:[p.item_id||""],genre:p.genre||""}];
 if(n==="share")return["trackCustom","SongShare",{content_ids:[p.item_id||""],method:p.method||""}];
 return null}
document.addEventListener("kjo:track",function(e){if(!on)return;var d=e.detail||{},m=map(d.name,d.params||{});if(m)try{fbq(m[0],m[1],m[2])}catch(x){}});
document.addEventListener("cmplz_fire_categories",start);document.addEventListener("cmplz_status_change",start);
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",start);else start();
})();
KJOMETA;
		echo '<script id="kjo-meta-pixel">' . str_replace( '__ID__', wp_json_encode( (string) $o['pixel'] ), $js ) . '</script>' . "\n";
	},
	31
);
