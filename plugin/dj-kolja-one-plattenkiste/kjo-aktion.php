<?php
/**
 * Aktionen mit Enddatum (seit 1.34.0)
 *
 * - Hinweis-Kasten auf den gewählten Seiten bis zum Enddatum (verschwindet danach automatisch, auch aus dem Cache).
 * - Link „Wunschtermin prüfen“ führt zu /kontakt/?aktion=CODE; das Formular übernimmt den Code.
 * - DJ-Anfragen mit Aktionscode (aus dem Link oder im Nachrichtentext) werden markiert (Mail, Liste, Pipeline).
 * Neue Aktion: Eintrag in KJO_AKTIONEN ergänzen. Abschalten: add_filter( 'kjo_aktionen', '__return_empty_array' );
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_AKTIONEN = array(
	'WEIHNACHT26' => array(
		'bis'    => '2026-10-31 23:59:59',
		'seiten' => array( 'firmenfeier-dj' ),
		'titel'  => 'Licht-Upgrade gratis für eure Weihnachtsfeier',
		'kurz'   => 'Licht-Upgrade gratis',
		'text'   => 'Bucht mich bis 31. Oktober für eure Firmen-Weihnachtsfeier im Dezember – und ich bringe mein erweitertes Lichtpaket ohne Aufpreis mit (Wert 150 €):',
		'items'  => array( '2 Moving Heads', '6 kabellose Akku-Scheinwerfer, auch für draußen', 'Wash-Scheinwerfer', 'UV-Schwarzlicht', 'LED-Laufschrift, z. B. mit eurem Firmennamen' ),
		'klein'  => 'Für Weihnachtsfeiern im Dezember 2026 bei verbindlicher Buchung bis 31.10.2026. Umfang je nach Raum und Location, nach Verfügbarkeit.',
	),
);

/** Aktive Aktionen (Code => Daten, inkl. Ende als Zeitstempel). */
function kjo_aktionen() {
	$out = array();
	foreach ( apply_filters( 'kjo_aktionen', KJO_AKTIONEN ) as $code => $a ) {
		$dt = date_create( $a['bis'], wp_timezone() );
		$ts = $dt ? $dt->getTimestamp() : 0;
		if ( $ts > time() ) {
			$a['ende']    = $ts;
			$out[ $code ] = $a;
		}
	}
	return $out;
}
/** Findet einen aktiven Aktionscode in einem Text (Groß-/Kleinschreibung egal). */
function kjo_aktion_finden( $text ) {
	foreach ( kjo_aktionen() as $code => $a ) {
		if ( false !== stripos( (string) $text, $code ) ) {
			return $code;
		}
	}
	return '';
}

/* Hinweis-Kasten auf den Aktionsseiten, direkt unter dem ersten Abschnitt (Hero). */
add_action(
	'wp_footer',
	function () {
		if ( is_admin() ) {
			return;
		}
		foreach ( kjo_aktionen() as $code => $a ) {
			if ( ! is_page( $a['seiten'] ) ) {
				continue;
			}
			$li = '';
			foreach ( $a['items'] as $i ) {
				$li .= '<li>' . esc_html( $i ) . '</li>';
			}
			$url = add_query_arg( 'aktion', $code, home_url( '/kontakt/' ) ) . '#formular';
			$h   = '<section class="kjo-akt" data-ende="' . (int) $a['ende'] . '000" aria-label="Aktion"><div class="kjo-akt-in">'
				. '<p class="kjo-akt-eye">Aktion bis ' . esc_html( wp_date( 'd.m.', $a['ende'] ) ) . '</p>'
				. '<h2 class="kjo-akt-h">' . esc_html( $a['titel'] ) . '</h2>'
				. '<p class="kjo-akt-t">' . esc_html( $a['text'] ) . '</p><ul class="kjo-akt-l">' . $li . '</ul>'
				. '<p class="kjo-akt-cta"><a class="kjo-akt-b" href="' . esc_url( $url ) . '">Wunschtermin prüfen</a> <span>Code <strong>' . esc_html( $code ) . '</strong> wird automatisch übernommen</span></p>'
				. '<p class="kjo-akt-k">' . esc_html( $a['klein'] ) . '</p></div></section>';
			echo '<template id="kjo-akt-tpl">' . $h . '</template>'; // phpcs:ignore
			echo '<style id="kjo-akt-css">.kjo-akt{background:#0F0C07;padding:56px 20px;font-family:"Fira Sans",sans-serif}.kjo-akt-in{max-width:880px;margin:0 auto;border:1px solid #B29D75;box-shadow:0 0 0 6px #0F0C07,0 0 0 7px rgba(178,157,117,.45),0 24px 60px rgba(0,0,0,.5);background:linear-gradient(160deg,#1E1A14,#14110C);padding:38px 40px;color:#F3F1E9}'
				. '.kjo-akt-eye{margin:0 0 8px;color:#B29D75;font-size:13px;letter-spacing:.18em;text-transform:uppercase;font-weight:500}.kjo-akt-h{margin:0 0 14px;font-size:34px;line-height:1.2;font-weight:300;color:#F3F1E9}.kjo-akt-t{margin:0 0 12px;font-size:17px;line-height:1.6;color:#D9D5CA}'
				. '.kjo-akt-l{margin:0 0 22px;padding:0;list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:6px 24px}.kjo-akt-l li{position:relative;padding-left:24px;font-size:16px;line-height:1.5;color:#F3F1E9}.kjo-akt-l li:before{content:"✦";position:absolute;left:0;color:#B29D75}'
				. '.kjo-akt-cta{margin:0 0 14px;display:flex;flex-wrap:wrap;align-items:center;gap:12px 18px}.kjo-akt-cta span{color:#A39E93;font-size:14px}.kjo-akt-cta strong{color:#B29D75;font-weight:500;letter-spacing:.05em}'
				. '.kjo-akt-b{display:inline-block;background:#B29D75;color:#0F0C07!important;text-decoration:none!important;padding:14px 30px;border-radius:2px;font-weight:500;letter-spacing:.04em}.kjo-akt-b:hover{background:#C4B08A}.kjo-akt-k{margin:0;color:#7d786e;font-size:12.5px;line-height:1.5}'
				. '@media(max-width:700px){.kjo-akt{padding:36px 14px}.kjo-akt-in{padding:26px 20px}.kjo-akt-h{font-size:26px}.kjo-akt-l{grid-template-columns:1fr}}</style>';
			echo '<script id="kjo-akt-js">(function(){var t=document.getElementById("kjo-akt-tpl");if(!t)return;var n=t.content.firstElementChild;if(!n||Date.now()>+n.getAttribute("data-ende"))return;'
				. 'var p=document.querySelector(".elementor[data-elementor-type=wp-page]")||document.querySelector(".elementor");var l=p?p.querySelectorAll(":scope > .e-con"):[];var s=l[1]||l[0];if(s){s.parentNode.insertBefore(n,s.nextSibling);}})();</script>' . "\n";
			break; // eine Aktion pro Seite
		}
	},
	33
);
