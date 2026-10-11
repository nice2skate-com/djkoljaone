<?php
/**
 * Verleih (seit 1.22.0): Geräte, Pakete, Mietkorb und Mietanfragen.
 *
 * Verwaltung im Admin unter „Verleih“:
 *  - Geräte   (Beitragstyp kjo_geraet)  – öffentliche Daten, Preise, Wetterfestigkeit, Anleitung (DE) und interne Inventardaten
 *  - Pakete   (Beitragstyp kjo_paket)   – Party-Pakete S / M / L
 *  - Mietanfragen (kjo_mietanfrage)     – Kopie jeder Anfrage, falls eine E-Mail verloren geht
 *  - Einstellungen                       – Empfänger-Adresse, Wochenendtarif, Sorglos-Option
 * Auf der Website: [kjo_pakete], [kjo_verleih] (Equipment-Browser + Mietkorb + Anfrage) und [kjo_verleih_hinweise].
 * Preise erscheinen nur, wenn sie eingetragen sind – sonst steht ein Ersatztext ohne Preis.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KJO_VL_MAIL', 'anfrage@dj-kolja-one.de' );

/* ---------------------------------------------------------------
 * Felder
 * ------------------------------------------------------------- */
function kjo_vl_kats() {
	return array(
		'lautsprecher' => 'Lautsprecher',
		'subwoofer'    => 'Subwoofer',
		'dj'           => 'DJ-Technik',
		'mikrofone'    => 'Mikrofone',
		'licht'        => 'Licht',
		'effekte'      => 'Effekte',
		'buehne'       => 'Bühne & Stative',
		'zubehoer'     => 'Zubehör',
	);
}
function kjo_vl_wetter() {
	return array(
		''           => array( 'Nicht eingetragen (bitte absprechen)', 'Wetterfestigkeit: bitte vorher absprechen', '❔' ),
		'innen'      => array( 'Nur innen', 'Nur für Innenräume', '🏠' ),
		'trocken'    => array( 'Draußen nur trocken und überdacht', 'Draußen nur trocken und überdacht', '⛱️' ),
		'spritz'     => array( 'Spritzwassergeschützt', 'Spritzwassergeschützt', '💧' ),
		'wetterfest' => array( 'Wetterfest', 'Wetterfest', '🌧️' ),
	);
}
function kjo_vl_fields( $type ) {
	if ( 'kjo_paket' === $type ) {
		return array(
			'gaeste'   => array( 'Gästezahl', 'text', 'z. B. Für bis zu ca. 50 Gäste' ),
			'anlaesse' => array( 'Anlässe', 'text', 'z. B. bspw. Geburtstag, Gartenparty, Sektempfang, etc.' ),
			'inhalt'   => array( 'Inhalt (eine Zeile pro Punkt)', 'textarea', '' ),
			'hinweis'  => array( 'Hinweis / Empfehlung', 'textarea', '' ),
			'preis'    => array( 'Mietpreis pro Tag in € (leer = „Preis auf Anfrage“)', 'price', 'z. B. 89' ),
			'preis_we' => array( 'Mietpreis Wochenende Fr 15 – Mo 21 Uhr in € (leer = 1,5 × Tagespreis)', 'price', 'z. B. 129' ),
			'kaution'  => array( 'Kaution in € (leer = „Kaution laut Angebot“)', 'price', 'z. B. 200' ),
			'medien'   => array( 'Bilder & Videos', 'medien', '' ),
			'bild'     => array( 'Bildname in der Mediathek (alternativ)', 'bild', '' ),
		);
	}
	return array(
		'kat'          => array( 'Kategorie', 'kat', '' ),
		'kurz'         => array( 'Kurzbeschreibung (1 Satz)', 'text', '' ),
		'beschr'       => array( 'Beschreibung', 'textarea', '' ),
		'mietbar'      => array( 'Im Verleih anbieten', 'check', '' ),
		'bestand'      => array( 'Bestand (Stück)', 'number', '' ),
		'preis'        => array( 'Mietpreis pro Tag in € (leer = „auf Anfrage“)', 'price', 'z. B. 25' ),
		'preis_we'     => array( 'Mietpreis Wochenende Fr 15 – Mo 21 Uhr in € (leer = 1,5 × Tagespreis)', 'price', 'z. B. 35' ),
		'kaution'      => array( 'Kaution in € (leer = „laut Angebot“)', 'price', '' ),
		'gaeste'       => array( 'Geeignet für', 'text', 'z. B. bis ca. 50 Gäste (als Paar)' ),
		'breite'       => array( 'Breite (mm)', 'mm', 'z. B. 241' ),
		'laenge'       => array( 'Länge / Tiefe (mm)', 'mm', 'z. B. 286' ),
		'hoehe'        => array( 'Höhe (mm)', 'mm', 'z. B. 330' ),
		'masse'        => array( 'Maße – Zusatz (optional)', 'text', 'z. B. mit Bügel 255 × 235 × 105 mm' ),
		'gewicht'      => array( 'Gewicht', 'text', 'z. B. 7,1 kg' ),
		'volumen'      => array( 'Transportvolumen', 'text', 'z. B. 22,75 l' ),
		'leistung'     => array( 'Leistung / Pegel', 'text', '' ),
		'anschluesse'  => array( 'Anschlüsse', 'text', '' ),
		'strom'        => array( 'Stromversorgung', 'text', 'z. B. 230 V Schuko / Akku' ),
		'lieferumfang' => array( 'Lieferumfang', 'textarea', '' ),
		'wetter'       => array( 'Wetterfestigkeit (wird im Browser angezeigt)', 'wetter', '' ),
		'wetter_ip'    => array( 'Schutzart', 'text', 'z. B. IP65' ),
		'anleitung'    => array( 'Bedienungsanleitung (Deutsch): Link oder PDF aus der Mediathek', 'anleitung', 'https://…' ),
		'hersteller'   => array( 'Herstellerseite (Infos, Downloads, Bildanfrage)', 'url', 'https://…' ),
		'medien'       => array( 'Bilder & Videos', 'medien', '' ),
		'bild'         => array( 'Bildname in der Mediathek (alternativ)', 'bild', '' ),
	);
}
function kjo_vl_intern_fields() {
	return array(
		'seriennr'   => 'Seriennummer(n)',
		'kaufdatum'  => 'Kaufdatum / Rechnung',
		'wert'       => 'Neupreis / Wiederbeschaffungswert pro Stück (€)',
		'wert_quelle' => 'Preisquelle & Stand',
		'pruefdatum' => 'Letzte Prüfung (DGUV V3)',
		'lagerort'   => 'Lagerort',
		'zustand'    => 'Zustand',
		'notiz'      => 'Interne Notiz',
	);
}
function kjo_vl_get( $id, $k ) {
	return (string) get_post_meta( $id, '_kjo_' . $k, true );
}
/** Maße aus Breite, Länge und Höhe (mm), ergänzt um den Freitext. */
function kjo_vl_masse( $id ) {
	$d = array();
	foreach ( array( 'breite', 'laenge', 'hoehe' ) as $k ) {
		$n = kjo_vl_num( kjo_vl_get( $id, $k ) );
		if ( null === $n ) {
			return kjo_vl_get( $id, 'masse' );
		}
		$d[] = str_replace( '.', ',', (string) $n );
	}
	$z = kjo_vl_get( $id, 'masse' );
	return implode( ' × ', $d ) . ' mm' . ( '' !== $z ? ' (' . $z . ')' : '' );
}
/** Transportvolumen: eingetragen oder aus den Maßen berechnet. */
function kjo_vl_volumen( $id ) {
	$v = kjo_vl_get( $id, 'volumen' );
	if ( '' !== $v ) {
		return $v;
	}
	$l = 1;
	foreach ( array( 'breite', 'laenge', 'hoehe' ) as $k ) {
		$n = kjo_vl_num( kjo_vl_get( $id, $k ) );
		if ( ! $n ) {
			return '';
		}
		$l *= $n / 100; // mm → dm
	}
	return 'ca. ' . number_format( $l, $l < 10 ? 1 : 0, ',', '.' ) . ' l';
}
/** Preis als Zahl (oder null, wenn nicht eingetragen). */
function kjo_vl_num( $v ) {
	$v = trim( str_replace( array( '€', ' ' ), '', (string) $v ) );
	if ( '' === $v ) {
		return null;
	}
	if ( false !== strpos( $v, ',' ) ) {
		$v = str_replace( ',', '.', str_replace( '.', '', $v ) ); // 1.234,50 → 1234.50
	}
	return is_numeric( $v ) ? (float) $v : null;
}
function kjo_vl_eur( $n ) {
	return ( floor( $n ) == $n ? number_format( $n, 0, ',', '.' ) : number_format( $n, 2, ',', '.' ) ) . ' €'; // phpcs:ignore
}
/** Wochenendpreis (Fr 15 – Mo 21 Uhr): eingetragener Wert oder 1,5 × Tagespreis (auf volle Euro). */
function kjo_vl_we( $id ) {
	$w = kjo_vl_num( kjo_vl_get( $id, 'preis_we' ) );
	$p = kjo_vl_num( kjo_vl_get( $id, 'preis' ) );
	return null !== $w ? $w : ( null === $p ? null : ceil( $p * 1.5 ) );
}
/**
 * Tarif für einen Zeitraum: Liegt Samstag oder Sonntag darin, gilt je Wochenende der Wochenendpreis (Fr 15 – Mo 21 Uhr),
 * zusätzliche Werktage (Di–Do bzw. Fr/Mo außerhalb) kosten den Tagespreis. Ohne Wochenende: Anzahl Tage, mindestens 1.
 */
function kjo_vl_tarif( $von, $bis ) {
	$a = date_create( $von );
	$b = date_create( $bis ? $bis : $von );
	if ( ! $a || ! $b || $b < $a ) {
		return null;
	}
	$sat = array();
	$all = array();
	for ( $d = clone $a; $d <= $b; $d->modify( '+1 day' ) ) {
		$w     = (int) $d->format( 'w' );
		$all[] = array( clone $d, $w );
		if ( 6 === $w || 0 === $w ) {
			$x                              = clone $d;
			$sat[ $x->modify( 6 === $w ? '+0 day' : '-1 day' )->format( 'Y-m-d' ) ] = 1;
		}
	}
	if ( ! $sat ) {
		return array( 'we' => 0, 'd' => max( 1, (int) $a->diff( $b )->days ) );
	}
	$n = 0;
	foreach ( $all as $e ) {
		list( $d, $w ) = $e;
		if ( 6 === $w || 0 === $w ) {
			continue;
		}
		$x = clone $d;
		if ( 5 === $w && isset( $sat[ $x->modify( '+1 day' )->format( 'Y-m-d' ) ] ) ) {
			continue;
		}
		$x = clone $d;
		if ( 1 === $w && isset( $sat[ $x->modify( '-2 day' )->format( 'Y-m-d' ) ] ) ) {
			continue;
		}
		++$n;
	}
	return array( 'we' => count( $sat ), 'd' => $n );
}
function kjo_vl_tarif_txt( $t ) {
	$p = array();
	if ( $t['we'] ) {
		$p[] = $t['we'] . ( 1 === $t['we'] ? ' Wochenende' : ' Wochenenden' );
	}
	if ( $t['d'] ) {
		$p[] = $t['d'] . ( 1 === $t['d'] ? ' Tag' : ' Tage' );
	}
	return implode( ' + ', $p );
}
function kjo_vl_opt( $k ) {
	$o = get_option( 'kjo_verleih', array() );
	return isset( $o[ $k ] ) ? trim( (string) $o[ $k ] ) : '';
}
function kjo_vl_mail_to() {
	$m = kjo_vl_opt( 'mail' );
	return is_email( $m ) ? $m : KJO_VL_MAIL;
}
/** Bildname (ohne Endung), unter dem die Seite Fotos und Videos in der Mediathek sucht. */
function kjo_vl_bild( $post ) {
	$b = kjo_vl_get( $post->ID, 'bild' );
	if ( '' === $b ) {
		$pre = 'kjo_paket' === $post->post_type ? 'paket_' : 'equip_';
		$b   = $pre . str_replace( '-', '_', sanitize_title( $post->post_name ? $post->post_name : $post->post_title ) );
	}
	return preg_replace( '/[^a-z0-9_]/', '', strtolower( $b ) );
}
/** Alle Bilder/Videos zu einem Bildnamen: name, name_1 … name_8. */
/** Bilder/Videos eines Geräts oder Pakets: zuerst die in der Mediathek ausgewählten (Reihenfolge wie sortiert), dann die über den Dateinamen gefundenen. */
function kjo_vl_media_post( $post ) {
	$out = array();
	foreach ( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_kjo_medien', true ) ) ) ) as $aid ) {
		$url  = wp_get_attachment_url( $aid );
		$mime = (string) get_post_mime_type( $aid );
		if ( ! $url ) {
			continue;
		}
		if ( 0 === strpos( $mime, 'video/' ) ) {
			$out[] = array( 'vid' => set_url_scheme( $url ) );
		} elseif ( 0 === strpos( $mime, 'image/' ) ) {
			$big   = wp_get_attachment_image_url( $aid, 'large' );
			$out[] = array(
				'img'  => set_url_scheme( $big ? $big : $url ),
				'full' => set_url_scheme( function_exists( 'kjo_img_web_url' ) ? kjo_img_web_url( $aid ) : $url ),
			);
		}
	}
	return array_merge( $out, kjo_vl_media( kjo_vl_bild( $post ) ) );
}
function kjo_vl_media( $key ) {
	$map = function_exists( 'kjo_media_map' ) ? kjo_media_map() : array();
	$out = array();
	foreach ( array_merge( array( $key ), array_map( function ( $i ) use ( $key ) { return $key . '_' . $i; }, range( 1, 8 ) ) ) as $k ) {
		if ( ! empty( $map[ $k ] ) ) {
			$out[] = $map[ $k ];
		}
	}
	return $out;
}

/* ---------------------------------------------------------------
 * Beitragstypen und Menü
 * ------------------------------------------------------------- */
add_action(
	'init',
	function () {
		$base = array(
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'kjo-verleih',
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
		);
		register_post_type(
			'kjo_geraet',
			$base + array(
				'labels'   => array(
					'name'          => 'Geräte',
					'singular_name' => 'Gerät',
					'add_new'       => 'Gerät hinzufügen',
					'add_new_item'  => 'Neues Gerät',
					'edit_item'     => 'Gerät bearbeiten',
					'search_items'  => 'Geräte durchsuchen',
					'not_found'     => 'Keine Geräte gefunden',
					'all_items'     => 'Geräte',
				),
				'supports' => array( 'title', 'page-attributes' ),
			)
		);
		register_post_type(
			'kjo_paket',
			$base + array(
				'labels'   => array(
					'name'          => 'Pakete',
					'singular_name' => 'Paket',
					'add_new'       => 'Paket hinzufügen',
					'add_new_item'  => 'Neues Paket',
					'edit_item'     => 'Paket bearbeiten',
					'all_items'     => 'Pakete',
					'not_found'     => 'Keine Pakete gefunden',
				),
				'supports' => array( 'title', 'page-attributes' ),
			)
		);
		register_post_type(
			'kjo_mietanfrage',
			array_merge(
				$base,
				array(
					'labels'       => array(
						'name'          => 'Mietanfragen',
						'singular_name' => 'Mietanfrage',
						'all_items'     => 'Mietanfragen',
						'edit_item'     => 'Mietanfrage',
						'not_found'     => 'Noch keine Mietanfragen',
					),
					'supports'     => array( 'title' ),
					'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				)
			)
		);
	}
);
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Verleih', 'Verleih', 'edit_pages', 'kjo-verleih', '__return_null', 'dashicons-cart', 26 );
		add_submenu_page( 'kjo-verleih', 'Verleih – Einstellungen', 'Einstellungen', 'manage_options', 'kjo-verleih-optionen', 'kjo_vl_options_page' );
	}
);
add_action(
	'admin_menu',
	function () {
		remove_submenu_page( 'kjo-verleih', 'kjo-verleih' ); // doppelten Eintrag „Verleih“ ausblenden
	},
	99
);
/* Der Hauptpunkt „Verleih“ öffnet direkt die Geräte-Liste. */
add_action(
	'admin_init',
	function () {
		global $pagenow;
		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 'kjo-verleih' === $_GET['page'] ) { // phpcs:ignore
			wp_safe_redirect( admin_url( 'edit.php?post_type=kjo_geraet' ) );
			exit;
		}
	}
);

/* ---------------------------------------------------------------
 * Startbestand (einmalig): Equipmentliste vom 10.10.2026 und Pakete S/M/L
 * ------------------------------------------------------------- */
function kjo_vl_seed_data() {
	$bose = 'https://assets.bose.com/content/dam/Bose_DAM/Web/consumer_electronics/global/products/speakers/s1_pro_system/pdfs/807173_og_s1-pro-system_de.pdf';
	$g    = array(
		array( 'bose-s1-pro', 'Bose S1 Pro Aktivlautsprecher', 'lautsprecher', 2, 'Kompakter Aktivlautsprecher mit Bluetooth: klarer Klang für Sprache und Musik, dezent im Raum.', array( 'breite' => '241', 'laenge' => '286', 'hoehe' => '330', 'gewicht' => '7,1 kg', 'volumen' => '22,75 l', 'anschluesse' => 'Bluetooth, Kabel-Eingänge', 'gaeste' => 'bis ca. 50 Gäste (als Paar)', 'anleitung' => $bose ) ),
		array( 'bose-subwoofer', 'Bose Subwoofer', 'subwoofer', 1, 'Bassergänzung für das Bose-System – mehr Druck auf der Tanzfläche.', array( 'gaeste' => 'bis ca. 100 Gäste (mit 2 × Bose S1 Pro)' ) ),
		array( 'pronomic-c-215-ma', KJO_VL_DATEN['pronomic-c-215-ma'][1], 'lautsprecher', 2, KJO_VL_DATEN['pronomic-c-215-ma'][3], KJO_VL_DATEN['pronomic-c-215-ma'][4] ),
		array( 'pronomic-c-118sa', 'Pronomic C-118SA 18"-Aktiv-Subwoofer', 'subwoofer', 2, '18"-Aktiv-Subwoofer für druckvollen Bass, den man spürt.', array( 'leistung' => '800 W RMS, 30–200 Hz, max. 126 dB', 'anschluesse' => '2 × Combo-Eingang, 2 × XLR-Ausgang, M20-Flansch für Distanzstange' ) ),
		array( 'pioneer-ddj-flx10', 'Pioneer DJ DDJ-FLX10', 'dj', 1, 'Vierkanaliger DJ-Controller für rekordbox und Serato – den Laptop bringt ihr mit.', array( 'breite' => '716', 'laenge' => '400.3', 'hoehe' => '73.4', 'gewicht' => '6,7 kg', 'volumen' => '21,04 l', 'anschluesse' => 'USB zum Laptop (rekordbox oder Serato)', 'lieferumfang' => "Netzteil\nUSB-Kabel\nAudiokabel", 'anleitung' => 'https://support.alphatheta.com/de/articles/16716272919193' ) ),
		array( 'dj-pult', 'Roadworx DJ Table (schwarz, mit Zwischenboden)', 'buehne', 1, 'Schwarzes, faltbares DJ-Pult mit Zwischenboden und Stoffverkleidung – eine saubere Bühne für Controller und Laptop.', array( 'lieferumfang' => "Zwischenboden\nStoffverkleidung (schwarz, 4 Teile)\nTragetasche" ) ),
		array( 'shure-beta-58a', KJO_VL_DATEN['shure-beta-58a'][1], 'mikrofone', 1, KJO_VL_DATEN['shure-beta-58a'][3], KJO_VL_DATEN['shure-beta-58a'][4] ),
		array( 'funkmikrofon-set', 'Funkmikrofon-Set', 'mikrofone', 1, 'Für Reden, Spiele und Ansagen – ohne Kabelsalat.', array() ),
		array( 'stairville-tri-flat-par', 'Stairville Tri Flat PAR Profile 5 × 3 W', 'licht', 4, 'Flacher LED-Scheinwerfer für farbiges Party- und Flächenlicht, z. B. an der Traverse.', array( 'breite' => '165', 'laenge' => '105', 'hoehe' => '165', 'masse' => 'mit Bügel 255 × 235 × 105 mm', 'gewicht' => '2,04 kg', 'leistung' => '15 W, RGB-LED', 'anschluesse' => 'DMX, Musiksteuerung, Automatik' ) ),
		array( 'moving-head', KJO_VL_DATEN['moving-head'][1], 'licht', 2, KJO_VL_DATEN['moving-head'][3], KJO_VL_DATEN['moving-head'][4] ),
		array( 'kls-lightbar', 'Eurolite KLS Laser Bar FX-Set mit Lichtstativ', 'licht', 1, 'Partylicht auf eigenem Stativ, das die ganze Tanzfläche ausleuchtet.', array( 'lieferumfang' => "Lichtstativ Stageworx BLS-315 Pro (1,50–3,10 m)\nTransporttasche\nFunk-Fußschalter\nIR-Fernbedienung" ) ),
		array( 'algam-akku-par', 'ALGAM Akku-Parlight', 'licht', 6, 'Kabelloses Ambientelicht für Wände, Säulen, Garten und Terrasse.', array( 'lieferumfang' => 'Ladegerät', 'strom' => 'Akku' ) ),
		array( 'uv-strahler', KJO_VL_DATEN['uv-strahler'][1], 'effekte', 1, KJO_VL_DATEN['uv-strahler'][3], KJO_VL_DATEN['uv-strahler'][4] ),
		array( 'wash-scheinwerfer', KJO_VL_DATEN['wash-scheinwerfer'][1], 'licht', 1, KJO_VL_DATEN['wash-scheinwerfer'][3], KJO_VL_DATEN['wash-scheinwerfer'][4] ),
		array( 'spiegelkugel', KJO_VL_DATEN['spiegelkugel'][1], 'effekte', 1, KJO_VL_DATEN['spiegelkugel'][3], KJO_VL_DATEN['spiegelkugel'][4] ),
		array( 'nebelmaschine', 'Showlite SN-1200 Nebelmaschine (1.200 W)', 'effekte', 1, 'Macht Lichtstrahlen sichtbar. Bitte vorher mit der Location wegen der Rauchmelder abstimmen.', array() ),
		array( 'led-laufschrift', 'Eurolite ESN LED-Laufschrift (65 cm)', 'effekte', 1, 'Eure Namen, „Happy Birthday“ oder euer Hashtag in Leuchtschrift.', array() ),
		array( 'traverse-3m', 'Stageworx LB-3 Traversen-Set 3 m', 'buehne', 1, 'Lichttraverse mit zwei Ständern – die Bühne für Scheinwerfer und Laufschrift.', array( 'lieferumfang' => "2 Lichtstative\n2 Traversenaufnehmer\n2 Traversenstücke (je 1,5 m, 3-Punkt)\n8 Haken (Hookclamps, je bis 15 kg) mit Sicherungsseilen\n2 Transporttaschen (Traverse, Stative)" ) ),
		array( 'lautsprecherstativ', 'Roadworx Speakerstand 2 Lautsprecherstativ', 'buehne', 2, 'Stabiles Alu-Stativ mit Gasdruckfeder für Aktivlautsprecher, 1,30–2,00 m hoch.', array() ),
	);
	$p = array(
		array( 'paket-s', 'Paket S – „Sound & Party“', 'Für bis zu ca. 50 Gäste', 'bspw. Geburtstag, Gartenparty, Sektempfang, etc.', "2× Bose S1 Pro Aktivlautsprecher (mit Bluetooth) auf Stativen\nPartylicht: Eurolite KLS Laser Bar mit Laser, Derbys, Spots und Strobe, auf eigenem Lichtstativ\nShure Beta 58A Mikrofon mit Kabel\nAudiokabel für Handy oder Laptop", 'Zubehör: DJ-Pult 20 €, Pioneer DDJ-FLX10 60 € pro Tag. Transport: passt in einen Kombi (Lightbar 1,30 m).' ),
		array( 'paket-m', 'Paket M – „Party“', 'Für bis zu ca. 100 Gäste', 'bspw. runder Geburtstag, Vereinsfeier, Firmenfeier, etc.', "Alles aus Paket S, dazu der Bose Sub1 Subwoofer\nFunkmikrofon-Set mit 4 Handmikros statt Kabelmikro\n3-m-Lichttraverse mit 4 LED-PARs und Cameo Thunder Wash (Wash, Blinder, Strobe)\nLED-Laufschrift, z. B. mit euren Namen\nNebelmaschine\nDJ-Pult (Roadworx DJ Table, schwarz)", 'Zubehör: Pioneer DDJ-FLX10 60 € pro Tag. Transport: Van oder Transporter (Traversentasche 1,54 m) – oder ich liefere und baue auf.' ),
		array( 'paket-l', 'Paket L – „Komplett-Show“', 'Für bis zu ca. 250 Gäste', 'bspw. Hochzeit, Abiball, Sommerfest, etc. Die komplette Bühne aus meinem Beispiel-Lichtplan.', "Pronomic PA: 2× C-215 MA (15\") auf 2× C-118SA (18\"-Subwoofer), mit Distanzstangen und Kabelsatz\n3-m-Lichttraverse mit 2 Moving Heads, 4 LED-PARs, Thunder Wash und LED-Laufschrift\n6 kabellose Akku-Scheinwerfer (IP65), auch für draußen\nKLS Laser Bar, UV-Schwarzlicht, Spiegelkugel, Nebelmaschine\nFunkmikrofon-Set (4 Handmikros), DJ-Pult", 'Nur mit Lieferung, Aufbau und Abbau durch mich – wegen der Moving Heads und der 40-kg-Subwoofer. Zubehör: Pioneer DDJ-FLX10 60 € pro Tag, Bose-Set für Sektempfang oder Nebenraum 99 € pro Tag.' ),
		array( 'paket-bose-zusatz', 'Zusatz zu Paket L: Bose-Set für Sektempfang & Nebenraum', 'Zweites Soundsystem', 'bspw. Sektempfang, Außenbereich, Nebenraum, Dinner', "2× Bose S1 Pro Aktivlautsprecher (mit Bluetooth) auf Stativen\nBose Sub1 Subwoofer\nAudiokabel für Handy oder Laptop", 'Nur zusammen mit Paket L buchbar.' ),
	);
	return array( $g, $p );
}
function kjo_vl_seed() {
	list( $g, $p ) = kjo_vl_seed_data();
	foreach ( $g as $i => $r ) {
		if ( get_page_by_path( $r[0], OBJECT, 'kjo_geraet' ) ) {
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'kjo_geraet', 'post_status' => 'publish', 'post_title' => $r[1], 'post_name' => $r[0], 'menu_order' => $i + 1 ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_kjo_kat', $r[2] );
		update_post_meta( $id, '_kjo_bestand', (string) $r[3] );
		update_post_meta( $id, '_kjo_kurz', $r[4] );
		update_post_meta( $id, '_kjo_mietbar', '1' );
		foreach ( $r[5] as $k => $v ) {
			update_post_meta( $id, '_kjo_' . $k, $v );
		}
	}
	foreach ( $p as $i => $r ) {
		if ( get_page_by_path( $r[0], OBJECT, 'kjo_paket' ) ) {
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'kjo_paket', 'post_status' => 'publish', 'post_title' => $r[1], 'post_name' => $r[0], 'menu_order' => $i + 1 ) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_kjo_gaeste', $r[2] );
		update_post_meta( $id, '_kjo_anlaesse', $r[3] );
		update_post_meta( $id, '_kjo_inhalt', $r[4] );
		update_post_meta( $id, '_kjo_hinweis', $r[5] );
		update_post_meta( $id, '_kjo_bild', str_replace( '-', '_', $r[0] ) );
	}
}
add_action(
	'admin_init',
	function () {
		if ( current_user_can( 'edit_pages' ) && '1' !== get_option( 'kjo_vl_seed' ) ) {
			update_option( 'kjo_vl_seed', '1', false );
			kjo_vl_seed();
		}
	},
	30
);

/* Recherchierte Gerätedaten (thomann.de, Stand 11.10.2026): Slug => array( alter Titel, neuer Titel, alte Kurzbeschreibung, neue Kurzbeschreibung, Felder ).
 * Beim Aktualisieren werden Titel/Kurztext nur ersetzt, wenn sie noch dem alten Platzhalter entsprechen, und nur leere Felder befüllt. */
const KJO_VL_DATEN = array(
	'shure-beta-58a' => array(
		'',
		'Shure Beta 58A Gesangsmikrofon mit Kabel',
		'',
		'Bühnenklassiker für Reden und Gesang – robust, klar und unempfindlich gegen Rückkopplungen.',
		array(
			'beschr'       => "Dynamisches Gesangsmikrofon mit Supernieren-Charakteristik: nimmt vor allem die Stimme auf und unterdrückt Umgebungsgeräusche und Rückkopplungen. Robust und ohne Batterie – einfach einstecken und loslegen.",
			'anschluesse'  => 'XLR (3-polig)',
			'lieferumfang' => 'Mikrofonkabel (XLR)',
			'wetter'       => 'innen',
			'wert'         => '185',
			'wert_quelle'  => 'Kirstein 185 € (UVP 214,20 €), Stand 11.10.2026',
			'hersteller'   => 'https://www.thomann.de/de/shure_beta_58_a.htm',
		),
	),
	'moving-head' => array(
		'Moving Head',
		'Varytec Hero Spot 60 Moving Head',
		'Bewegte Lichtstrahlen für echte Club-Momente.',
		'LED-Spot-Moving-Head mit Farbrad, rotierenden Gobos und Prisma – bewegte Lichtstrahlen für echte Club-Momente.',
		array(
			'beschr'       => "60-W-LED mit 10° Abstrahlwinkel, Farbrad mit 7 Farben plus Weiß, 5 rotierende Gobos plus offen (davon ein Glas-Gobo) mit Gobo Flow und Gobo Shake, 3-Facetten-Prisma, motorischer Fokus.\nPan 540° und Tilt 190° (16 Bit), Dimmer 0–100 %, Shutter 0–20 Hz. Steuerung per DMX (8 oder 14 Kanäle), Master/Slave, 8 Automatikprogramme und Musiksteuerung.",
			'breite'       => '214',
			'laenge'       => '144',
			'hoehe'        => '355',
			'gewicht'      => '4,6 kg',
			'leistung'     => '60-W-LED, 30.250 lx @ 1 m, 9.700 K',
			'anschluesse'  => 'DMX In/Out (XLR 3-polig), Stromeingang Power Twist',
			'strom'        => '100–240 V, 50/60 Hz, 100 W Leistungsaufnahme',
			'lieferumfang' => "Haltebügel\nNetzkabel\nDMX-Kabel",
			'anleitung'    => 'https://fast-images.static-thomann.de/pics/atg/atgdata/document/manual/c_449221_v5_de_online.pdf',
			'wetter'       => 'innen',
			'wert'         => '299',
			'wert_quelle'  => 'thomann.de 299 € inkl. MwSt. (Stand 11.10.2026), Art.-Nr. 449221',
			'hersteller'   => 'https://www.thomann.de/de/varytec_hero_spot_60.htm',
		),
		array(
			'anleitung' => array( 'https://images.thomann.de/pics/atg/atgdata/document/manual/c_449221_481679_v5_de_online.pdf', 'https://fast-images.static-thomann.de/pics/atg/atgdata/document/manual/c_449221_v5_de_online.pdf' ),
		),
	),
	'uv-strahler'  => array(
		'UV-Strahler',
		'Stairville Wild Wash 9x3W LED UV',
		'Schwarzlicht-Effekt für leuchtende Outfits und Deko.',
		'UV-Fluter (Schwarzlicht) mit 9 × 3-W-LEDs – lässt weiße Outfits und Neon-Deko leuchten.',
		array(
			'beschr'      => "9 UV-LEDs à 3 W, Wellenlänge 398 nm, Abstrahlwinkel 110°, LEDs einzeln ansteuerbar.\nSteuerung per DMX (2 oder 11 Kanäle), interne Programme und Musiksteuerung. Lüfterlos und damit geräuschlos.",
			'breite'      => '350',
			'laenge'      => '75',
			'hoehe'       => '125',
			'masse'       => 'mit Bügel',
			'gewicht'     => '1,3 kg',
			'leistung'    => '9 × 3-W-UV-LED, 398 nm, 110°',
			'anschluesse' => 'DMX In/Out (XLR 3-polig), Stromeingang IEC (Kaltgeräte)',
			'strom'       => '100–240 V, 50/60 Hz, 34 W Leistungsaufnahme',
			'wetter'      => 'innen',
			'wert'        => '89',
			'wert_quelle' => 'thomann.de 89 € inkl. MwSt. (Stand 11.10.2026), Art.-Nr. 426835',
			'hersteller'  => 'https://www.thomann.de/de/stairville_wild_wash_9x3w_led_uv_bk_dmx.htm',
			'anleitung'   => 'https://fast-images.static-thomann.de/pics/atg/atgdata/document/manual/c_426835_426836_v3_en_online.pdf',
		),
	),
	'pronomic-c-215-ma' => array(
		'Pronomic C-215 MA 15"-Aktivbox',
		'Pronomic C-215 MA 15"-Aktivboxen (Set mit 2 Boxen)',
		'2-Wege-Aktivbox mit 15"-Tieftöner und Bluetooth – kraftvoller Sound für große Säle und Festzelte.',
		'Set aus zwei 2-Wege-Aktivboxen mit 15"-Tieftöner und Bluetooth – kraftvoller Sound für große Säle und Festzelte.',
		array(
			'beschr'      => "Set aus zwei aktiven 2-Wege-Boxen mit je 15\"-Tieftöner und 1,75\"-Titan-Hochtöner: 400 W + 100 W RMS (800 W + 200 W Peak) pro Box, max. 123 dB SPL, Frequenzgang 55 Hz – 20 kHz.\nZwei Eingänge (XLR/Klinke-Combo) mit getrennter Lautstärke plus Bluetooth, DSP-Presets und ein schaltbarer XLR-Ausgang (Summe oder Kanal 1). Abstrahlwinkel 90° × 60°, Strom-Ausgang zum Durchschleifen, robustes Kunststoffgehäuse mit Monitorschräge.",
			'breite'      => '450',
			'laenge'      => '390',
			'hoehe'       => '695',
			'gewicht'     => '25,2 kg pro Box',
			'leistung'    => '400 W + 100 W RMS, max. 123 dB',
			'anschluesse' => '2 × XLR/Klinke-Combo, Bluetooth, XLR-Ausgang',
			'gaeste'      => 'bis ca. 250 Gäste (als Paar mit Subwoofern)',
			'wert'        => '269',
			'wert_quelle' => 'Kirstein: Stereo-Set (2 Boxen) 538 € → 269 € pro Box (Stand 11.10.2026)',
			'strom'       => '230 V, Strom-Ausgang zum Durchschleifen (max. 7 A)',
			'lieferumfang' => "Netzkabel\nDistanzstange (M20) für den Subwoofer",
			'hersteller'  => 'https://www.kirstein.de/Aktivboxen/Pronomic-C-215-MA-15-Aktivbox-1000-Watt.html',
		),
		array(
			'wert'        => array( '289', '269' ),
			'wert_quelle' => array( 'Musikhaus Kirstein ca. 289–295 € pro Box, 2 Boxen + 2 Stative als Set 588 € (Geizhals/heise, Stand 11.10.2026); nicht bei Thomann', 'Kirstein: Stereo-Set (2 Boxen) 538 € → 269 € pro Box (Stand 11.10.2026)' ),
		),
	),
	'pioneer-ddj-flx10' => array(
		'Pioneer DJ DDJ-FLX10',
		'Pioneer DJ DDJ-FLX10',
		'',
		'',
		array(
			'hersteller' => 'https://support.alphatheta.com/de/products/16715825715481',
			'wert'        => '1500',
			'wert_quelle' => 'Kaufpreis laut Kolja: 1.500 € (Stand 11.10.2026)',
			'notiz' => "Firmware: aktuell Ver. 1.14 (29.07.2025, u. a. djay-Pro-Unterstützung). Vor jeder Vermietung prüfen/aktualisieren:\nhttps://support.alphatheta.com/de/products/16715825715481",
		),
		array(
			'anleitung' => array( 'https://www.pioneerdj.com/de/product/dj-controllers/ddj-flx10/', 'https://support.alphatheta.com/de/articles/16716272919193' ),
		),
	),
	'bose-s1-pro' => array(
		'Bose S1 Pro Aktivlautsprecher',
		'Bose S1 Pro Aktivlautsprecher',
		'',
		'',
		array(
			'beschr'      => "Kompakter Aktivlautsprecher mit eingebautem Akku (bis zu 11 Stunden) und Bluetooth – ideal für Reden, Sektempfang, Garten und kleinere Feiern bis ca. 50 Gäste pro Paar.\nAufstellbar stehend, liegend oder auf einem Stativ. Mit Sub1 als Bassergänzung für Partys bis ca. 100 Gäste.",
			'anschluesse' => '2 × XLR/Klinke-Combo (Mikro/Line), AUX 3,5 mm, Bluetooth, Line-Out XLR',
			'strom'       => 'Akku (bis zu 11 h) oder 230 V',
			'wetter'      => 'trocken',
			'wert'        => '631',
			'wert_quelle' => 'Kaufpreis Thomann „Bose S1 Pro Stand Bundle“ 666 € (Art.-Nr. 431261, inkl. Roadworx Speakerstand 2 à 35 €) → 631 € für die Box',
		),
		array(
			'wert'        => array( '650', '631' ),
			'wert_quelle' => array( 'S1 Pro wird nicht mehr gebaut; Ersatz S1 Pro+ ca. 608–699 € (Geizhals/Baur, Stand 11.10.2026)', 'Kaufpreis Thomann „Bose S1 Pro Stand Bundle“ 666 € (Art.-Nr. 431261, inkl. Roadworx Speakerstand 2 à 35 €) → 631 € für die Box' ),
		),
	),
	'bose-subwoofer' => array(
		'Bose Subwoofer',
		'Bose Sub1 Aktiv-Subwoofer',
		'',
		'',
		array(
			'beschr'      => "Kompakter Aktiv-Subwoofer (7 × 13\"-Treiber, 480 W, 40–180 Hz) – bringt hörbar mehr Druck auf die Tanzfläche.\nPasst per Line-Ausgang an die Bose S1 Pro und reicht das Signal über zwei XLR-Ausgänge weiter.",
			'breite'      => '250',
			'laenge'      => '530',
			'hoehe'       => '530',
			'gewicht'     => '16,1 kg',
			'leistung'    => '480 W Class-D, max. 124,6 dB (Spitze), 40–180 Hz',
			'anschluesse' => 'Line-In XLR/Klinke-Combo, SubMatch-In/Thru, 2 × XLR-Ausgang (volle Bandbreite)',
			'strom'       => '230 V',
			'wetter'      => 'innen',
			'wert'        => '885',
			'wert_quelle' => 'ca. 743 € netto / 885 € brutto (Volt Music Store); USA 899 $ (CCI) – Stand 11.10.2026, Maße laut CCI',
		),
	),
	'stairville-tri-flat-par' => array(
		'Stairville Tri Flat PAR Profile 5 × 3 W',
		'Stairville Tri Flat PAR Profile 5 × 3 W (40°)',
		'',
		'',
		array(
			'beschr'      => "Flacher LED-Scheinwerfer mit 5 × 3-W-RGB-LEDs und 40° Abstrahlwinkel für farbiges Party- und Flächenlicht, z. B. an der Traverse oder als Wandfluter.\nSteuerung per DMX (3, 5 oder 7 Kanäle), Musiksteuerung und Automatik.",
			'leistung'    => '5 × 3-W-RGB-LED, 40°, 15 W',
			'strom'       => '100–240 V, 50/60 Hz',
			'wetter'      => 'innen',
			'wert'        => '71',
			'wert_quelle' => 'thomann.de 71 € (45°/40°-Version, Art.-Nr. 414495; Preis laut Suche, live prüfen)',
			'hersteller'  => 'https://www.thomann.de/de/stairville_tri_flat_par_profile_5x3w_40.htm',
		),
	),
	'pronomic-c-118sa' => array(
		'Pronomic C-118SA 18"-Aktiv-Subwoofer',
		'Pronomic C-118SA 18"-Aktiv-Subwoofer',
		'',
		'',
		array(
			'beschr'      => "18\"-Aktiv-Subwoofer mit 3\"-Schwingspule: 800 W RMS (1.600 W Spitze), 30–200 Hz, max. 126 dB – Bass, den man spürt.\n4 DSP-Presets (Live, Music, DJ, C-Serie), Trennfrequenz 80–150 Hz, Delay 0–16 ms, 2 Combo-Eingänge und 2 XLR-Ausgänge zum Weiterleiten an die Tops, robustes Holzgehäuse mit M20-Flansch für die Distanzstange.",
			'breite'      => '550',
			'laenge'      => '695',
			'hoehe'       => '600',
			'gewicht'     => '39,5 kg',
			'leistung'    => '800 W RMS / 1.600 W Spitze (Class D), 30–200 Hz, max. 126 dB',
			'wetter'      => 'innen',
			'wert'        => '498',
			'wert_quelle' => 'Kirstein: 2er-Set 916 € statt einzeln 996 € → 498 € pro Stück (Stand 11.10.2026)',
			'hersteller'  => 'https://www.kirstein.de/Aktiv-Subwoofer/Pronomic-C-118SA-Aktiv-Subwoofer.html',
		),
	),
	'funkmikrofon-set' => array(
		'Funkmikrofon-Set',
		'Omnitronic UHF-604 Funkmikrofon-Set (4 Kanäle)',
		'Für Reden, Spiele und Ansagen – ohne Kabelsalat.',
		'Vier Funk-Handmikrofone für Reden, Spiele und Ansagen – ohne Kabelsalat.',
		array(
			'beschr'       => "4-Kanal-Funksystem mit vier Handmikrofonen und True-Diversity-Empfänger – stabile Übertragung bis ca. 60 m bei Sichtverbindung.\nIdeal für Trauzeugen-Reden, Spiele und Moderation. Jedes Mikrofon hat einen eigenen Ausgang, zusätzlich gibt es einen Summenausgang.",
			'breite'       => '485',
			'laenge'       => '210',
			'hoehe'        => '44',
			'masse'        => 'Empfänger, 19"-Rackformat 1 HE',
			'gewicht'      => 'ca. 3,3 kg (Set)',
			'leistung'     => 'UHF 520–554 MHz, Reichweite bis 60 m',
			'anschluesse'  => '4 × XLR-Ausgang (je Mikrofon), Summenausgang 6,3-mm-Klinke, BNC-Antennenanschlüsse',
			'strom'        => 'Empfänger 230 V (Netzteil), Mikrofone je 2 × AA',
			'lieferumfang' => "Empfänger\n4 Handmikrofone\nNetzteil\nim Original-Karton (kein Koffer)\nBatterien (AA) bringe ich frisch mit",
			'wetter'       => 'trocken',
			'wert'         => '329',
			'wert_quelle'  => 'Steinigke/Omnitronic 329 € (520–554-MHz-Version, Art.-Nr. 13063605); Musikhaus Korn 329 € (Stand 11.10.2026)',
			'hersteller'   => 'https://www.kirstein.de/Drahtlos-Komplett-Sets/Omnitronic-UHF-604-520-554-MHz.html',
		),
	),
	'algam-akku-par' => array(
		'ALGAM Akku-Parlight',
		'ALGAM EVENTPAR412-HEX-IP Akku-Scheinwerfer',
		'Kabelloses Ambientelicht für Wände, Säulen, Garten und Terrasse.',
		'Kabelloses, wetterfestes Akku-Licht (IP65) für Wände, Säulen, Garten und Terrasse.',
		array(
			'beschr'      => "Kabelloser Akku-Scheinwerfer mit 4 × 12-W-LEDs (RGBWA+UV, 40°) – setzt Wände, Säulen, Bäume und Deko in jede Farbe, drinnen wie draußen (IP65).\nSteuerung per Funk-DMX oder IR-Fernbedienung, Akku bis zu 20 Stunden bei fester Farbe. Geliefert im Lade-Case für alle 6 Scheinwerfer oder im kompakten Koffer.",
			'breite'      => '131',
			'laenge'      => '131',
			'hoehe'       => '163',
			'gewicht'     => '2,8 kg pro Scheinwerfer',
			'leistung'    => '4 × 12-W-LED RGBWA+UV, 40°',
			'anschluesse' => 'Funk-DMX (6 oder 10 Kanäle), IR-Fernbedienung',
			'wetter'      => 'wetterfest',
			'wetter_ip'   => 'IP65',
			'wert'        => '219',
			'wert_quelle' => 'Kirstein 219 € pro Stück (UVP 229 €); Tourset 6 Stück + Lade-Case 1.599 € (Stand 11.10.2026)',
			'hersteller'  => 'https://www.kirstein.de/Licht-Sparsets/Algam-Lighting-EVENTPAR412-HEX-IP-Akku-LED-Tourset.html',
		),
		array(
			'bestand'      => array( '8', '6' ),
			'strom'        => array( 'Akku', 'Akku 12,6 V / 7,8 Ah: bis 4 h (alle Farben), bis 20 h (feste Farbe/Automatik)' ),
			'lieferumfang' => array( 'Ladegerät', "Lade-Case mit Ladestation (für 6 Scheinwerfer) oder kompakter Koffer\nIR-Fernbedienung" ),
		),
	),
	'dj-pult' => array(
		'DJ-Pult, beleuchtet',
		'Roadworx DJ Table (schwarz, mit Zwischenboden)',
		'Beleuchtetes DJ-Pult mit zwei Zusatzplatten – eine saubere Bühne für Controller und Laptop.',
		'Schwarzes, faltbares DJ-Pult mit Zwischenboden und Stoffverkleidung – eine saubere Bühne für Controller und Laptop.',
		array(
			'beschr'      => "Faltbarer DJ-Tisch aus Vierkantstahlrohr, Aufbau ohne Werkzeug. Große Arbeitsfläche (1.137 × 570 mm) in 932 mm Höhe für Controller, Mixer und Laptop, belastbar bis 50 kg.\nMit Zwischenboden für Taschen und Technik und schwarzer Stoffverkleidung (2 Front- und 2 Seitenteile, waschbar), die Kabel und Kisten verdeckt.",
			'breite'      => '1203',
			'laenge'      => '605',
			'hoehe'       => '1125',
			'gewicht'     => '27,8 kg (Tisch 21 kg, Zwischenboden 6,5 kg, Verkleidung 0,3 kg)',
			'wetter'      => 'innen',
			'wert'        => '315',
			'wert_quelle' => 'thomann.de 315 € (Set „DJ Table Black Shelf“: Tisch, Zwischenboden, Stoffverkleidung; Art.-Nr. 546794, Stand 11.10.2026)',
			'hersteller'  => 'https://www.thomann.de/de/roadworx_dj_table_black_shelf.htm',
		),
		array(
			'lieferumfang' => array( '2 Zusatzplatten', "Zwischenboden\nStoffverkleidung (schwarz, 4 Teile)\nTragetasche" ),
		),
	),
	'lautsprecherstativ' => array(
		'Lautsprecherstativ',
		'Roadworx Speakerstand 2 Lautsprecherstativ',
		'Stabiles Stativ für Aktivlautsprecher.',
		'Stabiles Alu-Stativ mit Gasdruckfeder für Aktivlautsprecher, 1,30–2,00 m hoch.',
		array(
			'beschr'      => "Dreibein-Stativ aus Aluminium mit 35-mm-Aufnahme für Aktivlautsprecher mit Hochständerflansch.\nHöhe 1,30–2,00 m, die Gasdruckfeder (Pneumatik) federt das Gewicht beim Verstellen ab. Belastbar bis 20 kg.",
			'hoehe'       => '2000',
			'gewicht'     => '2,1 kg',
			'leistung'    => 'belastbar bis 20 kg, Aufnahme 35 mm, Höhe 1.300–2.000 mm',
			'wert'        => '35',
			'wert_quelle' => 'thomann.de 35 € pro Stativ (Art.-Nr. 442423, Stand 11.10.2026)',
			'hersteller'  => 'https://www.thomann.de/de/roadworx_speakerstand_2.htm',
		),
	),
	'led-laufschrift' => array(
		'LED-Laufschrift (ca. 70 cm)',
		'Eurolite ESN LED-Laufschrift (65 cm)',
		'',
		'',
		array(
			'beschr'       => "LED-Laufschrift mit 560 SMD-LEDs (Rot/Grün, bis 16 Farbtöne), Anzeigefläche 60 × 5,5 cm: eine Zeile mit 13 sichtbaren Zeichen, die durchlaufen – für eure Namen, „Happy Birthday“, den Firmennamen oder euren Hashtag. Auch kleine Bilder wie Herzen (7 × 80 Pixel), 7 Schriftarten.\nProgrammierung per IR-Fernbedienung oder PC-Software (USB oder Netzwerk). Lautlos, ohne Lüfter.",
			'breite'       => '647',
			'laenge'       => '97',
			'hoehe'        => '35',
			'gewicht'      => '1,25 kg',
			'leistung'     => '560 SMD-LEDs (RG), 7 × 80 Pixel, 12 W',
			'anschluesse'  => 'USB (Micro-USB), Netzwerk (RJ-45, TCP/IP)',
			'strom'        => 'Netzteil 100–240 V, 50/60 Hz (Bar: 5 V DC, 3 A)',
			'lieferumfang' => "IR-Fernbedienung\nNetzteil (1,8-m-Kabel)\nNetzwerkkabel\nUSB-Kabel",
			'wetter'       => 'innen',
			'wetter_ip'    => 'IP20',
			'wert'         => '245',
			'wert_quelle'  => 'thomann.de 245 € (Art.-Nr. 523364, Stand 11.10.2026)',
			'hersteller'   => 'https://www.thomann.de/de/eurolite_esn_moving_message.htm',
		),
	),
	'traverse-3m' => array(
		'Traversen-Set 3 m',
		'Stageworx LB-3 Traversen-Set 3 m',
		'',
		'',
		array(
			'beschr'      => "Lichtstativ-Set mit 3-Punkt-Traverse: 3 m breit, Höhe 1,5–3,1 m einstellbar, Platz für bis zu 8 Scheinwerfer oder Effekte, belastbar mit 60 kg (gleichmäßig verteilt). Von einer Person schnell aufgebaut.\nStative aus Stahl (Rohr 28 mm, Standfläche Ø 1,2 m), Traverse 18-mm-Rohr. Transport in zwei Taschen (154 × 34 cm und 127 × 26 cm).",
			'breite'      => '3000',
			'laenge'      => '1200',
			'hoehe'       => '3100',
			'gewicht'     => '21 kg (Set ohne Taschen und Haken)',
			'leistung'    => 'belastbar bis 60 kg (gleichmäßig verteilt), 8 Haken je bis 15 kg',
			'wetter'      => 'innen',
			'wert'        => '219',
			'wert_quelle' => 'thomann.de 219 € (Bundle mit Taschen und 4 Hookclamps, Art.-Nr. 306960, Stand 11.10.2026)',
			'hersteller'  => 'https://www.thomann.de/de/stairville_lb_3_lighting_stand_set_bundle.htm',
		),
		array(
			'lieferumfang' => array( "2 Traversenständer\n2 Adapter Traverse/Ständer\n2 Traversenstücke\nVerbinder, Schrauben und Sicherungen", "2 Lichtstative\n2 Traversenaufnehmer\n2 Traversenstücke (je 1,5 m, 3-Punkt)\n8 Haken (Hookclamps, je bis 15 kg) mit Sicherungsseilen\n2 Transporttaschen (Traverse, Stative)" ),
		),
	),
	'kls-lightbar' => array(
		'KLS-Lightbar mit Lichtstativ',
		'Eurolite KLS Laser Bar FX-Set mit Lichtstativ',
		'Partylicht auf eigenem Stativ, das die ganze Tanzfläche ausleuchtet.',
		'Lichteffekt-Leiste mit Laser, Derbys, Spots und Strobe auf eigenem Stativ – leuchtet die ganze Tanzfläche aus.',
		array(
			'beschr'      => "4-in-1-Lichteffektleiste: 2 rotierende LED-Derbys (Spiegelkugel-Effekt), 2 LED-Spots in Rot/Grün/Blau, Strobe mit 4 weißen LEDs und ein Laser mit hunderten roten und grünen Strahlen. 37 Showprogramme im Auto- und Musikmodus, bedienbar per Funk-Fußschalter oder IR-Fernbedienung, optional per DMX.\nLaserklasse 2M: keine Abnahme und kein Laserschutzbeauftragter nötig. Auf dem Stativ in 1,50–3,10 m Höhe.",
			'breite'      => '1320',
			'laenge'      => '145',
			'hoehe'       => '320',
			'gewicht'     => '7,3 kg (Leiste), Stativ 5,6 kg',
			'leistung'    => 'Spots 2 × 3 × 3 W RGB (13°), Derbys 2 × 3 × 1 W RGB, Strobe 4 × 1 W weiß, Laser 100 mW rot / 30 mW grün (Klasse 2M); Stativ belastbar bis 30 kg',
			'anschluesse' => 'DMX In/Out (XLR 3-polig, 3/5/9/27 Kanäle), Kaltgeräte-Strom In/Out, Stativaufnahme 36 mm',
			'strom'       => '100–240 V, 50/60 Hz, 50 W',
			'wetter'      => 'innen',
			'wert'        => '366',
			'wert_quelle' => 'thomann.de: Eurolite LED KLS Laser Bar FX-Set 321 € (UVP 379 €, Art.-Nr. 396055) + Stageworx BLS-315 Pro Lighting Stand B 45 € (Stand 11.10.2026)',
			'hersteller'  => 'https://www.thomann.de/de/eurolite_led_kls_laser_bar_fx_set.htm',
			'notiz'       => "Lichtstativ: Stageworx BLS-315 Pro Lighting Stand B, Höhe 150–310 cm, Standfläche Ø 120 cm, belastbar bis 30 kg, Transportlänge 124 cm:\nhttps://www.thomann.de/de/stairville_bls315_pro_lighting_stand_b.htm",
		),
		array(
			'lieferumfang' => array( 'Lichtstativ', "Lichtstativ Stageworx BLS-315 Pro (1,50–3,10 m)\nTransporttasche\nFunk-Fußschalter\nIR-Fernbedienung" ),
		),
	),
	'nebelmaschine' => array(
		'Nebelmaschine',
		'Showlite SN-1200 Nebelmaschine (1.200 W)',
		'',
		'',
		array(
			'beschr'       => "Nebelmaschine mit 1.200 W und 350 m³/min Nebelausstoß – macht Lichtstrahlen von Moving Heads und Laser sichtbar, für kleine bis mittlere Säle und Bühnen.\n2-Liter-Tank, Bedienung per Kabelfernbedienung (4 m) oder Funkfernbedienung (bis 10 m). Bitte vorher mit der Location wegen der Rauchmelder abstimmen.",
			'leistung'     => '1.200 W, 350 m³/min, Tank 2 l',
			'lieferumfang' => "Kabelfernbedienung (4 m)\nFunkfernbedienung (bis 10 m)",
			'wetter'       => 'innen',
			'wert'         => '120',
			'wert_quelle'  => 'Kirstein 119,90 € regulär (bis 31.10.2026 reduziert auf 113 €; Set mit 5 l Fluid 132,90 €), Stand 11.10.2026',
			'hersteller'   => 'https://www.kirstein.de/Nebelmaschinen/Showlite-SN-1200-Nebelmaschine-1200W-inkl-Fernbedienung.html',
		),
	),
	'wash-scheinwerfer' => array(
		'Wash-/Color-Scheinwerfer',
		'Cameo Thunder Wash 100 RGB (Wash, Blinder, Strobe)',
		'Zusätzliche farbige Flächenbeleuchtung.',
		'3-in-1-Effekt: farbiges Flächenlicht, Blinder und Strobe für die Höhepunkte auf der Tanzfläche.',
		array(
			'beschr'      => "LED-Leiste mit 132 RGB-SMD-LEDs (je 0,2 W) und 80° Abstrahlwinkel: taucht Flächen in Farbe, blendet als Blinder ins Publikum oder blitzt als Strobe (1–30 Blitze pro Sekunde).\nSteuerung per DMX (8 Modi), Master/Slave oder eigenständig mit eingebautem Mikrofon zur Musiksteuerung. Kompaktes Metallgehäuse mit Montagebügel.",
			'leistung'    => '132 × 0,2-W-RGB-SMD-LED, 80°, Strobe 1–30 Hz',
			'anschluesse' => 'DMX In/Out (XLR 3-polig)',
			'lieferumfang' => 'Montagebügel',
			'wetter'      => 'innen',
			'wert'        => '99',
			'wert_quelle' => 'thomann.de 99 € (Cameo Thunder Wash 100 RGB, CLTW100RGB, Art.-Nr. 376242, Stand 11.10.2026)',
			'hersteller'  => 'https://www.thomann.de/de/cameo_thunder_wash_100rgb.htm',
		),
	),
	'spiegelkugel' => array(
		'',
		'Eurolite Spiegelkugel 30 cm mit Motor',
		'',
		'Der Disco-Klassiker: Die drehende Spiegelkugel verteilt das Licht eines Spots als funkelnde Punkte im ganzen Raum.',
		array(
			'beschr'       => "Spiegelkugel mit 30 cm Durchmesser am Motor MD-1515, der sie langsam dreht (ca. 1,5 Umdrehungen pro Minute). Angestrahlt von einem Spot oder Moving Head wandern Lichtpunkte über Wände, Decke und Tanzfläche.\nAufhängung an Decke oder Traverse; der Motor trägt bis 4 kg.",
			'breite'       => '300',
			'laenge'       => '300',
			'hoehe'        => '385',
			'masse'        => 'Kugel Ø 30 cm, Höhe mit Motor (8,5 cm)',
			'gewicht'      => '2,4 kg (Kugel 2 kg, Motor 0,4 kg)',
			'leistung'     => 'Motor 4 W, ca. 1,5 U/min, Traglast 4 kg',
			'strom'        => '230 V, 50 Hz, Schukostecker',
			'lieferumfang' => "Motor MD-1515 mit Netzkabel\nKette\n4 Karabiner\nSchrauben und Dübel",
			'wetter'       => 'innen',
			'wert'         => '71',
			'wert_quelle'  => 'Steinigke-Listenpreis 71,28 € (Art.-Nr. 42109225); Kirstein ab 59,90 € (Stand 11.10.2026)',
			'hersteller'   => 'https://www.kirstein.de/Spiegelkugeln/Eurolite-Spiegelkugel-30cm-mit-Motor-MD-1515.html',
		),
	),
);
/* Aktualisierung bestehender Geräte (läuft einmal je Stand von KJO_VL_MIG – bei neuen Recherchedaten hochzählen): Maße in Breite/Länge/Höhe aufteilen, KJO_VL_DATEN ergänzen. Nur leere Felder werden befüllt. */
/* Mietpreise (Tag, Wochenende Fr 15 – Mo 21 Uhr, Kaution) je Stück bzw. Paket, Vorschlag vom 11.10.2026. Werden nur in leere Felder geschrieben. */
const KJO_VL_PREISE = array(
	'kjo_geraet' => array(
		'bose-s1-pro'            => array( 35, 50, 150 ),
		'bose-subwoofer'         => array( 45, 65, 250 ),
		'pronomic-c-215-ma'      => array( 30, 45, 100 ),
		'pronomic-c-118sa'       => array( 40, 60, 150 ),
		'pioneer-ddj-flx10'      => array( 60, 90, 500 ),
		'funkmikrofon-set'       => array( 30, 45, 100 ),
		'moving-head'            => array( 25, 35, 100 ),
		'kls-lightbar'           => array( 30, 45, 100 ),
		'algam-akku-par'         => array( 10, 15, 50 ),
		'stairville-tri-flat-par' => array( 7, 10, 20 ),
		'traverse-3m'            => array( 25, 35, 80 ),
		'led-laufschrift'        => array( 20, 30, 80 ),
		'dj-pult'                => array( 20, 30, 80 ),
		'nebelmaschine'          => array( 15, 25, 50 ),
		'uv-strahler'            => array( 10, 15, 30 ),
		'wash-scheinwerfer'      => array( 10, 15, 30 ),
		'spiegelkugel'           => array( 10, 15, 30 ),
		'lautsprecherstativ'     => array( 5, 8, 20 ),
		'shure-beta-58a'         => array( 10, 15, 50 ),
	),
	'kjo_paket'  => array(
		'paket-s'           => array( 89, 129, 300 ),
		'paket-m'           => array( 209, 299, 600 ),
		'paket-l'           => array( 329, 479, 300 ),
		'paket-bose-zusatz' => array( 99, 149, 400 ),
	),
);
const KJO_VL_OPT_DEFAULT = array(
	'wochenende' => 'Freitag ab 15 Uhr bis Montag 21 Uhr. Sobald Samstag oder Sonntag im Mietzeitraum liegt, gilt der Wochenendpreis.',
	'lieferung'  => 'Bringen und Holen ab Fellheim: bis 15 km 40 €, bis 30 km 70 €, bis 50 km (z. B. Ulm, Kempten, Kaufbeuren) 110 €, darüber 0,60 € je gefahrenem km.',
	'aufbau'     => 'Auf- und Abbau: Paket S 40 €, Paket M 100 €, Paket L 180 €, sonst 40 € pro Stunde; Abbau nach 23 Uhr +50 %. Die Einweisung ist immer kostenlos.',
);
const KJO_VL_PAKETE_V = 2; // Pakete S/M/L neu (12.10.2026): einmalig vollständig überschreiben
const KJO_VL_MIG      = 19;
add_action(
	'admin_init',
	function () {
		if ( ! current_user_can( 'edit_pages' ) || (int) get_option( 'kjo_vl_mig', 0 ) >= KJO_VL_MIG ) {
			return;
		}
		update_option( 'kjo_vl_mig', KJO_VL_MIG, false );
		foreach ( get_posts( array( 'post_type' => 'kjo_geraet', 'post_status' => 'any', 'numberposts' => -1 ) ) as $g ) {
			$m = kjo_vl_get( $g->ID, 'masse' );
			if ( '' === kjo_vl_get( $g->ID, 'breite' ) && preg_match( '/^\s*([\d.,]+)\s*[×x]\s*([\d.,]+)\s*[×x]\s*([\d.,]+)\s*mm\s*(?:\((.*)\))?\s*$/u', $m, $r ) ) {
				// Altes Format B × H × T.
				update_post_meta( $g->ID, '_kjo_breite', (string) kjo_vl_num( $r[1] ) );
				update_post_meta( $g->ID, '_kjo_hoehe', (string) kjo_vl_num( $r[2] ) );
				update_post_meta( $g->ID, '_kjo_laenge', (string) kjo_vl_num( $r[3] ) );
				if ( empty( $r[4] ) ) {
					delete_post_meta( $g->ID, '_kjo_masse' );
				} else {
					update_post_meta( $g->ID, '_kjo_masse', $r[4] );
				}
			}
		}
		// Pakete einmalig auf den neuen Stand bringen (Texte und Preise), fehlende anlegen.
		if ( (int) get_option( 'kjo_vl_pakete_v', 1 ) < KJO_VL_PAKETE_V ) {
			update_option( 'kjo_vl_pakete_v', KJO_VL_PAKETE_V, false );
			list( , $pk ) = kjo_vl_seed_data();
			foreach ( $pk as $i => $r ) {
				$g  = get_page_by_path( $r[0], OBJECT, 'kjo_paket' );
				$id = $g ? $g->ID : wp_insert_post( array( 'post_type' => 'kjo_paket', 'post_status' => 'publish', 'post_title' => $r[1], 'post_name' => $r[0], 'menu_order' => $i + 1 ) );
				if ( ! $id || is_wp_error( $id ) ) {
					continue;
				}
				if ( $g ) {
					wp_update_post( array( 'ID' => $id, 'post_title' => $r[1] ) );
				} else {
					update_post_meta( $id, '_kjo_bild', str_replace( '-', '_', $r[0] ) );
				}
				update_post_meta( $id, '_kjo_gaeste', $r[2] );
				update_post_meta( $id, '_kjo_anlaesse', $r[3] );
				update_post_meta( $id, '_kjo_inhalt', $r[4] );
				update_post_meta( $id, '_kjo_hinweis', $r[5] );
				if ( isset( KJO_VL_PREISE['kjo_paket'][ $r[0] ] ) ) {
					foreach ( array( 'preis', 'preis_we', 'kaution' ) as $n => $k ) {
						update_post_meta( $id, '_kjo_' . $k, (string) KJO_VL_PREISE['kjo_paket'][ $r[0] ][ $n ] );
					}
				}
			}
		}
		// Lieferumfang-Korrekturen (nur wenn noch eine der bekannten Altfassungen drinsteht).
		$fix = array(
			'traverse-3m'       => array( array( "2 Lichtstative\n2 Traversenaufnehmer\n2 Traversenstücke (je 1,5 m, 3-Punkt)\n4 Haken (Hookclamps, je bis 15 kg)\n2 Transporttaschen (Traverse, Stative)" ), "2 Lichtstative\n2 Traversenaufnehmer\n2 Traversenstücke (je 1,5 m, 3-Punkt)\n8 Haken (Hookclamps, je bis 15 kg) mit Sicherungsseilen\n2 Transporttaschen (Traverse, Stative)" ),
			'pronomic-c-215-ma' => array( array( 'Netzkabel' ), "Netzkabel\nDistanzstange (M20) für den Subwoofer" ),
		);
		foreach ( $fix as $slug => $f ) {
			$g = get_page_by_path( $slug, OBJECT, 'kjo_geraet' );
			if ( $g && in_array( kjo_vl_get( $g->ID, 'lieferumfang' ), $f[0], true ) ) {
				update_post_meta( $g->ID, '_kjo_lieferumfang', $f[1] );
			}
		}
		// Mietpreise und Texte nur dort eintragen, wo noch nichts steht.
		foreach ( KJO_VL_PREISE as $type => $rows ) {
			foreach ( $rows as $slug => $r ) {
				$g = get_page_by_path( $slug, OBJECT, $type );
				if ( ! $g ) {
					continue;
				}
				foreach ( array( 'preis', 'preis_we', 'kaution' ) as $i => $k ) {
					if ( '' === trim( (string) get_post_meta( $g->ID, '_kjo_' . $k, true ) ) ) {
						update_post_meta( $g->ID, '_kjo_' . $k, (string) $r[ $i ] );
					}
				}
			}
		}
		$o = get_option( 'kjo_verleih', array() );
		$o = is_array( $o ) ? $o : array();
		if ( isset( $o['aufbau'] ) && false !== strpos( $o['aufbau'], 'Paket M 80 €' ) ) {
			$o['aufbau'] = str_replace( 'Paket M 80 €', 'Paket M 100 €', $o['aufbau'] );
		}
		foreach ( KJO_VL_OPT_DEFAULT as $k => $v ) {
			if ( empty( $o[ $k ] ) ) {
				$o[ $k ] = $v;
			}
		}
		update_option( 'kjo_verleih', $o );
		// Neu hinzugekommene Geräte anlegen (nur diese Slugs, damit gelöschte Geräte nicht zurückkommen).
		list( $seed ) = kjo_vl_seed_data();
		foreach ( $seed as $i => $r ) {
			if ( in_array( $r[0], array( 'spiegelkugel', 'shure-beta-58a' ), true ) && ! get_page_by_path( $r[0], OBJECT, 'kjo_geraet' ) ) {
				$id = wp_insert_post( array( 'post_type' => 'kjo_geraet', 'post_status' => 'publish', 'post_title' => $r[1], 'post_name' => $r[0], 'menu_order' => $i + 1 ) );
				if ( $id && ! is_wp_error( $id ) ) {
					update_post_meta( $id, '_kjo_kat', $r[2] );
					update_post_meta( $id, '_kjo_bestand', (string) $r[3] );
					update_post_meta( $id, '_kjo_kurz', $r[4] );
					update_post_meta( $id, '_kjo_mietbar', '1' );
				}
			}
		}
		foreach ( KJO_VL_DATEN as $slug => $d ) {
			$g = get_page_by_path( $slug, OBJECT, 'kjo_geraet' );
			if ( ! $g ) {
				continue;
			}
			if ( $d[0] === $g->post_title ) {
				wp_update_post( array( 'ID' => $g->ID, 'post_title' => $d[1] ) );
			}
			if ( $d[2] === kjo_vl_get( $g->ID, 'kurz' ) ) {
				update_post_meta( $g->ID, '_kjo_kurz', $d[3] );
			}
			foreach ( $d[4] as $k => $v ) {
				if ( '' === kjo_vl_get( $g->ID, $k ) ) {
					update_post_meta( $g->ID, '_kjo_' . $k, $v );
				}
			}
			foreach ( isset( $d[5] ) ? $d[5] : array() as $k => $ab ) { // Feld ersetzen, solange noch der alte Wert drinsteht
				if ( $ab[0] === kjo_vl_get( $g->ID, $k ) ) {
					update_post_meta( $g->ID, '_kjo_' . $k, $ab[1] );
				}
			}
		}
		// Textkorrekturen in Paketen (nur exakte Altformulierung wird ersetzt).
		foreach ( array( 'paket-m', 'paket-l' ) as $slug ) {
			$pk = get_page_by_path( $slug, OBJECT, 'kjo_paket' );
			if ( $pk ) {
				$t = (string) get_post_meta( $pk->ID, '_kjo_inhalt', true );
				$n = str_replace( array( '8× Akku-Parlights mit Ladegerät', '8 Akku-Parlights', 'beleuchtetes DJ-Pult mit Zusatzplatten' ), array( '6 Akku-Scheinwerfer (IP65) im Lade-Case', '6 Akku-Scheinwerfer (IP65)', 'DJ-Pult mit Zwischenboden' ), $t );
				if ( $n !== $t ) {
					update_post_meta( $pk->ID, '_kjo_inhalt', $n );
				}
			}
		}
		if ( function_exists( 'kjm_purge_caches' ) ) {
			kjm_purge_caches();
		}
	},
	31
);

/* ---------------------------------------------------------------
 * Bearbeiten-Maske
 * ------------------------------------------------------------- */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_vl_daten', 'Daten für die Website', 'kjo_vl_box', array( 'kjo_geraet', 'kjo_paket' ), 'normal', 'high' );
		add_meta_box( 'kjo_vl_intern', 'Interne Inventardaten (nicht öffentlich)', 'kjo_vl_box_intern', 'kjo_geraet', 'normal', 'default' );
		add_meta_box( 'kjo_vl_anfrage', 'Mietanfrage', 'kjo_vl_box_anfrage', 'kjo_mietanfrage', 'normal', 'high' );
	}
);
function kjo_vl_box( $post ) {
	wp_nonce_field( 'kjo_vl_save', 'kjo_vl_nonce' );
	$rep = (int) get_post_meta( $post->ID, '_kjo_anl_gemeldet', true );
	if ( $rep ) {
		echo '<div class="notice notice-error inline"><p><strong>Link gemeldet</strong> am ' . esc_html( wp_date( 'd.m.Y, H:i', $rep ) ) . ' Uhr: Ein Besucher hat den Anleitungs-Link als fehlerhaft gemeldet. Bitte prüfen und speichern – dann verschwindet der Hinweis.</p></div>';
	}
	echo '<table class="form-table kjo-vl-form" role="presentation">';
	foreach ( kjo_vl_fields( $post->post_type ) as $k => $f ) {
		$v    = kjo_vl_get( $post->ID, $k );
		$name = 'kjo_vl[' . $k . ']';
		echo '<tr><th><label for="kjo_vl_' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		switch ( $f[1] ) {
			case 'textarea':
				echo '<textarea id="kjo_vl_' . esc_attr( $k ) . '" name="' . esc_attr( $name ) . '" rows="4" class="large-text">' . esc_textarea( $v ) . '</textarea>';
				break;
			case 'check':
				echo '<label><input type="checkbox" id="kjo_vl_' . esc_attr( $k ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', $v, false ) . '> ja, im Equipment-Browser zeigen</label>';
				break;
			case 'kat':
				echo '<select id="kjo_vl_kat" name="' . esc_attr( $name ) . '">';
				foreach ( kjo_vl_kats() as $kk => $kl ) {
					echo '<option value="' . esc_attr( $kk ) . '"' . selected( $kk, $v, false ) . '>' . esc_html( $kl ) . '</option>';
				}
				echo '</select>';
				break;
			case 'wetter':
				echo '<select id="kjo_vl_wetter" name="' . esc_attr( $name ) . '">';
				foreach ( kjo_vl_wetter() as $kk => $kl ) {
					echo '<option value="' . esc_attr( $kk ) . '"' . selected( $kk, $v, false ) . '>' . esc_html( $kl[2] . ' ' . $kl[0] ) . '</option>';
				}
				echo '</select>';
				break;
			case 'anleitung':
				echo '<input type="url" id="kjo_vl_anleitung" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '" class="large-text" placeholder="' . esc_attr( $f[2] ) . '"> ';
				echo '<p><button type="button" class="button" id="kjo-vl-pdf">PDF aus der Mediathek wählen</button> ';
				if ( $v ) {
					echo '<a href="' . esc_url( $v ) . '" target="_blank" rel="noopener">Link testen ↗</a>';
				}
				echo '</p><p class="description">Öffentlich: Erscheint in den Gerätedetails als „Bedienungsanleitung (PDF, Deutsch)“ mit dem Knopf „Link defekt? Melden“.</p>';
				break;
			case 'medien':
				$ids = array_filter( array_map( 'absint', explode( ',', $v ) ) );
				echo '<input type="hidden" id="kjo_vl_medien" name="' . esc_attr( $name ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '"><ul id="kjo-vl-medien" class="kjo-vl-medien">';
				foreach ( $ids as $aid ) {
					$th = wp_attachment_is( 'video', $aid ) ? '' : wp_get_attachment_image_url( $aid, 'thumbnail' );
					echo '<li data-id="' . (int) $aid . '">' . ( $th ? '<img src="' . esc_url( $th ) . '" alt="">' : '<span class="kjo-vl-vid">▶ Video</span>' ) . '<button type="button" class="kjo-vl-mx" aria-label="Entfernen">×</button></li>';
				}
				echo '</ul><p><button type="button" class="button" id="kjo-vl-medien-add">Bilder/Videos aus der Mediathek wählen</button></p><p class="description">Mehrere auf einmal wählbar. Reihenfolge per Ziehen ändern – das erste Bild ist das Vorschaubild. Der Dateiname ist egal.</p>';
				break;
			case 'bild':
				$auto = kjo_vl_bild( (object) array( 'ID' => 0, 'post_type' => $post->post_type, 'post_name' => $post->post_name, 'post_title' => $post->post_title ) );
				echo '<input type="text" id="kjo_vl_bild" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '" class="regular-text" placeholder="' . esc_attr( $auto ) . '">';
				$key = $v ? $v : $auto;
				$n   = count( kjo_vl_media( $key ) );
				echo '<p class="description">Nur nötig, wenn ihr Bilder lieber über den Dateinamen zuordnet: <code>' . esc_html( $key ) . '.jpg</code>, <code>' . esc_html( $key ) . '_1.jpg</code> … (Videos: <code>.mp4</code>). Über den Namen gefunden: <strong>' . (int) $n . '</strong>. Leer lassen = Vorschlag übernehmen.</p>';
				break;
			default:
				$type = 'number' === $f[1] ? 'number' : 'text';
				if ( 'price' === $f[1] || 'mm' === $f[1] ) {
					$v = str_replace( '.', ',', $v ); // 30.5 → 30,5
				}
				echo '<input type="' . esc_attr( $type ) . '" id="kjo_vl_' . esc_attr( $k ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '" class="' . ( 'text' === $type && 'mm' !== $f[1] ? 'large-text' : 'small-text' ) . '" placeholder="' . esc_attr( $f[2] ) . '"' . ( 'number' === $type ? ' min="0" step="1"' : '' ) . '>';
				if ( 'price' === $f[1] ) {
					echo ' <span class="description">Nur Zahl, z. B. 89 oder 89,50. Leer = Ersatztext ohne Preis.</span>';
				}
				if ( 'mm' === $f[1] ) {
					echo ' mm';
					if ( 'hoehe' === $k ) {
						echo '<p class="description">Breite × Länge × Höhe erscheinen zusammen als „Maße“; ist das Transportvolumen leer, wird es daraus berechnet.</p>';
					}
				}
		}
		echo '</td></tr>';
	}
	echo '</table>';
	?>
<style>.kjo-vl-medien{display:flex;flex-wrap:wrap;gap:8px;margin:0}.kjo-vl-medien li{position:relative;width:96px;height:96px;margin:0;border:1px solid #c3c4c7;background:#f6f7f7;cursor:move;display:flex;align-items:center;justify-content:center}.kjo-vl-medien img{width:100%;height:100%;object-fit:cover}.kjo-vl-mx{position:absolute;top:2px;right:2px;width:22px;height:22px;border-radius:50%;border:0;background:#d63638;color:#fff;cursor:pointer;line-height:1}.kjo-vl-vid{font-size:12px}</style>
<script>
window.addEventListener("load",function(){var b=document.getElementById("kjo-vl-pdf");if(!b||!window.wp||!wp.media)return;var fr;
b.addEventListener("click",function(e){e.preventDefault();if(!fr){fr=wp.media({title:"Bedienungsanleitung wählen",library:{type:"application/pdf"},button:{text:"Übernehmen"},multiple:false});
fr.on("select",function(){var a=fr.state().get("selection").first().toJSON();document.getElementById("kjo_vl_anleitung").value=a.url;});}fr.open();});});
window.addEventListener("load",function(){var ul=document.getElementById("kjo-vl-medien"),inp=document.getElementById("kjo_vl_medien"),add=document.getElementById("kjo-vl-medien-add");if(!ul||!inp||!add||!window.wp||!wp.media)return;var fm;
function sync(){inp.value=[].map.call(ul.querySelectorAll("li"),function(li){return li.getAttribute("data-id")}).join(",");}
function item(a){var li=document.createElement("li");li.setAttribute("data-id",a.id);var th=a.type==="image"?((a.sizes&&a.sizes.thumbnail&&a.sizes.thumbnail.url)||a.url):"";li.innerHTML=(th?'<img alt="">':'<span class="kjo-vl-vid">▶ Video</span>')+'<button type="button" class="kjo-vl-mx" aria-label="Entfernen">×</button>';if(th)li.querySelector("img").src=th;ul.appendChild(li);}
add.addEventListener("click",function(e){e.preventDefault();if(!fm){fm=wp.media({title:"Bilder & Videos wählen",library:{type:["image","video"]},button:{text:"Übernehmen"},multiple:"add"});
fm.on("select",function(){var have=inp.value.split(",");fm.state().get("selection").each(function(m){var a=m.toJSON();if(have.indexOf(String(a.id))<0)item(a);});sync();});}fm.open();});
ul.addEventListener("click",function(e){var b=e.target.closest(".kjo-vl-mx");if(b){b.parentNode.remove();sync();}});
if(window.jQuery&&jQuery.fn.sortable)jQuery(ul).sortable({update:sync});});
</script>
	<?php
}
function kjo_vl_box_intern( $post ) {
	echo '<table class="form-table" role="presentation">';
	foreach ( kjo_vl_intern_fields() as $k => $l ) {
		$v = kjo_vl_get( $post->ID, $k );
		echo '<tr><th><label for="kjo_vl_' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td>';
		if ( 'notiz' === $k ) {
			echo '<textarea id="kjo_vl_' . esc_attr( $k ) . '" name="kjo_vl[' . esc_attr( $k ) . ']" rows="3" class="large-text">' . esc_textarea( $v ) . '</textarea>';
		} else {
			echo '<input type="text" id="kjo_vl_' . esc_attr( $k ) . '" name="kjo_vl[' . esc_attr( $k ) . ']" value="' . esc_attr( $v ) . '" class="large-text">';
		}
		echo '</td></tr>';
	}
	echo '</table><p class="description">Diese Angaben sieht nur ihr im Admin – sie erscheinen nie auf der Website.</p>';
}
function kjo_vl_box_anfrage( $post ) {
	echo '<pre style="white-space:pre-wrap;font:14px/1.6 system-ui,sans-serif;margin:0">' . esc_html( (string) get_post_meta( $post->ID, '_kjo_text', true ) ) . '</pre>';
}
add_action(
	'admin_enqueue_scripts',
	function () {
		$s = get_current_screen();
		if ( $s && in_array( $s->post_type, array( 'kjo_geraet', 'kjo_paket' ), true ) && 'post' === $s->base ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}
);
add_action(
	'save_post',
	function ( $id, $post ) {
		if ( ! in_array( $post->post_type, array( 'kjo_geraet', 'kjo_paket' ), true ) || wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( ! isset( $_POST['kjo_vl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_vl_nonce'] ) ), 'kjo_vl_save' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$in   = isset( $_POST['kjo_vl'] ) && is_array( $_POST['kjo_vl'] ) ? wp_unslash( $_POST['kjo_vl'] ) : array(); // phpcs:ignore
		$keys = kjo_vl_fields( $post->post_type );
		if ( 'kjo_geraet' === $post->post_type ) {
			foreach ( kjo_vl_intern_fields() as $k => $l ) {
				$keys[ $k ] = array( $l, 'notiz' === $k ? 'textarea' : 'text' );
			}
		}
		foreach ( $keys as $k => $f ) {
			$v = isset( $in[ $k ] ) ? (string) $in[ $k ] : '';
			if ( 'textarea' === $f[1] ) {
				$v = sanitize_textarea_field( $v );
			} elseif ( 'anleitung' === $f[1] || 'url' === $f[1] ) {
				$v = esc_url_raw( trim( $v ) );
			} elseif ( 'check' === $f[1] ) {
				$v = '1' === $v ? '1' : '0';
			} elseif ( 'number' === $f[1] ) {
				$v = '' === trim( $v ) ? '' : (string) absint( $v );
			} elseif ( 'price' === $f[1] || 'mm' === $f[1] ) {
				$n = kjo_vl_num( $v );
				$v = null === $n ? '' : rtrim( rtrim( number_format( $n, 2, '.', '' ), '0' ), '.' );
			} elseif ( 'medien' === $f[1] ) {
				$v = implode( ',', array_filter( array_map( 'absint', explode( ',', $v ) ) ) );
			} elseif ( 'bild' === $f[1] ) {
				$v = preg_replace( '/[^a-z0-9_]/', '', strtolower( $v ) );
			} else {
				$v = sanitize_text_field( $v );
			}
			if ( '' === $v ) {
				delete_post_meta( $id, '_kjo_' . $k );
			} else {
				update_post_meta( $id, '_kjo_' . $k, $v );
			}
		}
		delete_post_meta( $id, '_kjo_anl_gemeldet' ); // Gespeichert = geprüft.
		if ( function_exists( 'kjm_purge_caches' ) ) {
			kjm_purge_caches();
		}
	},
	10,
	2
);

/* Spalten in den Listen */
add_filter(
	'manage_kjo_geraet_posts_columns',
	function ( $c ) {
		return array(
			'cb'      => $c['cb'],
			'title'   => 'Gerät',
			'kat'     => 'Kategorie',
			'bestand' => 'Bestand',
			'miet'    => 'Verleih',
			'preis'   => 'Preis/Tag',
			'kaution' => 'Kaution',
			'wetter'  => 'Wetter',
			'anl'     => 'Anleitung (DE)',
			'bild'    => 'Bildname',
		);
	}
);
add_filter(
	'manage_kjo_paket_posts_columns',
	function ( $c ) {
		return array(
			'cb'      => $c['cb'],
			'title'   => 'Paket',
			'gaeste'  => 'Gäste',
			'preis'   => 'Preis/Tag',
			'kaution' => 'Kaution',
			'bild'    => 'Bildname',
		);
	}
);
function kjo_vl_column( $col, $id ) {
	$p = get_post( $id );
	switch ( $col ) {
		case 'kat':
			$k = kjo_vl_kats();
			echo esc_html( isset( $k[ kjo_vl_get( $id, 'kat' ) ] ) ? $k[ kjo_vl_get( $id, 'kat' ) ] : '–' );
			break;
		case 'bestand':
		case 'gaeste':
			echo esc_html( kjo_vl_get( $id, $col ) ? kjo_vl_get( $id, $col ) : '–' );
			break;
		case 'miet':
			echo '1' === kjo_vl_get( $id, 'mietbar' ) ? '✔' : '–';
			break;
		case 'preis':
		case 'kaution':
			$n = kjo_vl_num( kjo_vl_get( $id, $col ) );
			echo null === $n ? '<span style="color:#888">auf Anfrage</span>' : esc_html( kjo_vl_eur( $n ) );
			break;
		case 'wetter':
			$w = kjo_vl_wetter();
			$x = kjo_vl_get( $id, 'wetter' );
			echo esc_html( isset( $w[ $x ] ) ? $w[ $x ][2] . ' ' . $w[ $x ][0] : '' );
			break;
		case 'anl':
			$u = kjo_vl_get( $id, 'anleitung' );
			if ( get_post_meta( $id, '_kjo_anl_gemeldet', true ) ) {
				echo '<span style="color:#d63638;font-weight:600">⚠ Link gemeldet</span><br>';
			}
			echo $u ? '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">öffnen ↗</a>' : '–';
			break;
		case 'bild':
			$key = kjo_vl_bild( $p );
			echo '<code>' . esc_html( $key ) . '</code> (' . count( kjo_vl_media_post( $p ) ) . ')';
			break;
	}
}
add_action( 'manage_kjo_geraet_posts_custom_column', 'kjo_vl_column', 10, 2 );
add_action( 'manage_kjo_paket_posts_custom_column', 'kjo_vl_column', 10, 2 );
foreach ( array( 'kjo_geraet', 'kjo_paket' ) as $kjo_pt ) {
	add_action(
		'pre_get_posts',
		function ( $q ) use ( $kjo_pt ) {
			if ( is_admin() && $q->is_main_query() && $kjo_pt === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
				$q->set( 'orderby', 'menu_order title' );
				$q->set( 'order', 'ASC' );
			}
		}
	);
}
/* Hinweis im Menü, wenn ein Anleitungs-Link gemeldet wurde. */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		$ids = get_posts( array( 'post_type' => 'kjo_geraet', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 50, 'meta_key' => '_kjo_anl_gemeldet' ) ); // phpcs:ignore
		if ( $ids ) {
			$l = array();
			foreach ( $ids as $i ) {
				$l[] = '<a href="' . esc_url( get_edit_post_link( $i ) ) . '">' . esc_html( get_the_title( $i ) ) . '</a>';
			}
			echo '<div class="notice notice-warning"><p><strong>Verleih:</strong> Anleitungs-Link als fehlerhaft gemeldet bei ' . wp_kses_post( implode( ', ', $l ) ) . '.</p></div>';
		}
	}
);

/* ---------------------------------------------------------------
 * Einstellungen
 * ------------------------------------------------------------- */
add_action(
	'admin_init',
	function () {
		register_setting(
			'kjo_verleih',
			'kjo_verleih',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $v ) {
					$v = is_array( $v ) ? $v : array();
					return array(
						'mail'            => is_email( isset( $v['mail'] ) ? trim( $v['mail'] ) : '' ) ? trim( $v['mail'] ) : '',
						'wochenende'      => sanitize_text_field( isset( $v['wochenende'] ) ? $v['wochenende'] : '' ),
						'sorglos_prozent' => sanitize_text_field( isset( $v['sorglos_prozent'] ) ? $v['sorglos_prozent'] : '' ),
						'sorglos_max'     => sanitize_text_field( isset( $v['sorglos_max'] ) ? $v['sorglos_max'] : '' ),
						'lieferung'       => sanitize_text_field( isset( $v['lieferung'] ) ? $v['lieferung'] : '' ),
						'aufbau'          => sanitize_text_field( isset( $v['aufbau'] ) ? $v['aufbau'] : '' ),
					);
				},
			)
		);
	}
);
function kjo_vl_options_page() {
	$f = array(
		'mail'            => array( 'E-Mail für Mietanfragen und Link-Meldungen', 'Leer = ' . KJO_VL_MAIL ),
		'wochenende'      => array( 'Wochenendtarif (Freitag bis Montag)', 'z. B. 1,5-facher Tagespreis – leer = „Fragt nach unserem Wochenendtarif.“' ),
		'sorglos_prozent' => array( 'Sorglos-Option: Aufpreis in %', 'z. B. 10 – nur wenn beide Felder ausgefüllt sind, stehen Zahlen auf der Seite' ),
		'sorglos_max'     => array( 'Sorglos-Option: Selbstbeteiligung höchstens (€)', 'z. B. 250' ),
		'lieferung'       => array( 'Lieferung und Abholung (Text)', 'z. B. Zonen ab Fellheim – leer = „gegen Gebühr laut Angebot“' ),
		'aufbau'          => array( 'Auf- und Abbau (Text)', 'z. B. Paket S 40 €, M 80 €, L 180 € – leer = „gegen Gebühr laut Angebot“' ),
	);
	echo '<div class="wrap"><h1>Verleih – Einstellungen</h1><form method="post" action="options.php">';
	settings_fields( 'kjo_verleih' );
	echo '<table class="form-table" role="presentation">';
	foreach ( $f as $k => $l ) {
		echo '<tr><th><label for="kjo_vlo_' . esc_attr( $k ) . '">' . esc_html( $l[0] ) . '</label></th><td><input type="text" class="regular-text" id="kjo_vlo_' . esc_attr( $k ) . '" name="kjo_verleih[' . esc_attr( $k ) . ']" value="' . esc_attr( kjo_vl_opt( $k ) ) . '"><p class="description">' . esc_html( $l[1] ) . '</p></td></tr>';
	}
	echo '</table>';
	submit_button( 'Speichern' );
	echo '</form><p>Preise und Kaution tragt ihr direkt bei den <a href="' . esc_url( admin_url( 'edit.php?post_type=kjo_geraet' ) ) . '">Geräten</a> und <a href="' . esc_url( admin_url( 'edit.php?post_type=kjo_paket' ) ) . '">Paketen</a> ein. Fehlt ein Preis, zeigt die Seite automatisch einen Text ohne Preis.</p></div>';
}
add_action( 'update_option_kjo_verleih', function () { if ( function_exists( 'kjm_purge_caches' ) ) { kjm_purge_caches(); } } );

/* ---------------------------------------------------------------
 * Daten für die Website
 * ------------------------------------------------------------- */
function kjo_vl_items( $type ) {
	$args = array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'menu_order title', 'order' => 'ASC' );
	if ( 'kjo_geraet' === $type ) {
		$args['meta_query'] = array( array( 'key' => '_kjo_mietbar', 'value' => '1' ) ); // phpcs:ignore
	}
	return get_posts( $args );
}
function kjo_vl_img_html( $post, $alt, $cls ) {
	$m = kjo_vl_media_post( $post );
	foreach ( $m as $e ) {
		if ( ! empty( $e['img'] ) ) {
			return '<img class="' . esc_attr( $cls ) . '" src="' . esc_url( $e['img'] ) . '"' . ( ! empty( $e['full'] ) ? ' data-full="' . esc_url( $e['full'] ) . '"' : '' ) . ' alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
		}
	}
	return '<span class="' . esc_attr( $cls ) . ' kjo-vl-ph" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm0 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></span>';
}

/* [kjo_pakete] */
/** Begriffe in Paketzeilen, die auf ein Gerät verlinken (längere zuerst). */
const KJO_VL_LINKS = array(
	'Eurolite KLS Laser Bar' => 'kls-lightbar',
	'KLS Laser Bar'          => 'kls-lightbar',
	'Cameo Thunder Wash'     => 'wash-scheinwerfer',
	'Thunder Wash'           => 'wash-scheinwerfer',
	'Shure Beta 58A'         => 'shure-beta-58a',
	'Bose S1 Pro'            => 'bose-s1-pro',
	'Bose Sub1'              => 'bose-subwoofer',
	'Funkmikrofon-Set'       => 'funkmikrofon-set',
	'3-m-Lichttraverse'      => 'traverse-3m',
	'LED-PARs'               => 'stairville-tri-flat-par',
	'LED-Laufschrift'        => 'led-laufschrift',
	'Nebelmaschine'          => 'nebelmaschine',
	'DJ-Pult'                => 'dj-pult',
	'C-215 MA'               => 'pronomic-c-215-ma',
	'C-118SA'                => 'pronomic-c-118sa',
	'Moving Heads'           => 'moving-head',
	'Akku-Scheinwerfer'      => 'algam-akku-par',
	'UV-Schwarzlicht'        => 'uv-strahler',
	'Spiegelkugel'           => 'spiegelkugel',
	'Stativen'               => 'lautsprecherstativ',
);
/** Paketzeile als HTML: bekannte Gerätenamen werden zu Info-Buttons (Hover/Klick zeigt das Gerät). */
function kjo_vl_zeile_html( $l ) {
	static $ids = array();
	$keys = array_keys( KJO_VL_LINKS );
	usort( $keys, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );
	$re = '/' . implode( '|', array_map( function ( $k ) { return preg_quote( esc_html( $k ), '/' ); }, $keys ) ) . '/u';
	return preg_replace_callback(
		$re,
		function ( $m ) use ( &$ids ) {
			$slug = KJO_VL_LINKS[ html_entity_decode( $m[0], ENT_QUOTES, 'UTF-8' ) ];
			if ( ! isset( $ids[ $slug ] ) ) {
				$g            = get_page_by_path( $slug, OBJECT, 'kjo_geraet' );
				$ids[ $slug ] = ( $g && 'publish' === $g->post_status ) ? $g->ID : 0;
			}
			if ( ! $ids[ $slug ] ) {
				return $m[0];
			}
			$GLOBALS['kjo_vl_gi'][ $ids[ $slug ] ] = 1;
			return '<button type="button" class="kjo-vl-gl" data-g="' . (int) $ids[ $slug ] . '" aria-label="' . esc_attr( $m[0] ) . ': Gerät ansehen">' . $m[0] . '</button>';
		},
		esc_html( $l )
	);
}
/** Kurzinfos der verlinkten Geräte für das Info-Fenster. */
function kjo_vl_geraet_info( $id ) {
	$g = get_post( $id );
	if ( ! $g ) {
		return null;
	}
	$img = '';
	foreach ( kjo_vl_media_post( $g ) as $e ) {
		if ( ! empty( $e['img'] ) ) {
			$img = $e['img'];
			break;
		}
	}
	$wet  = kjo_vl_wetter();
	$w    = kjo_vl_get( $id, 'wetter' );
	$rows = array_filter(
		array(
			'Leistung'     => kjo_vl_get( $id, 'leistung' ),
			'Maße (B × L × H)' => kjo_vl_masse( $id ),
			'Gewicht'      => kjo_vl_get( $id, 'gewicht' ),
			'Wetter'       => isset( $wet[ $w ] ) ? $wet[ $w ][2] . ' ' . $wet[ $w ][1] : '',
		)
	);
	$txt = kjo_vl_get( $id, 'beschr' ) ? kjo_vl_get( $id, 'beschr' ) : kjo_vl_get( $id, 'kurz' );
	return array(
		'n' => $g->post_title,
		'i' => $img,
		't' => wp_trim_words( str_replace( "\n", ' ', $txt ), 40, ' …' ),
		'r' => $rows,
		'h' => kjo_vl_get( $id, 'hersteller' ),
	);
}
add_action(
	'wp_footer',
	function () {
		if ( empty( $GLOBALS['kjo_vl_gi'] ) ) {
			return;
		}
		$d = array();
		foreach ( array_keys( $GLOBALS['kjo_vl_gi'] ) as $id ) {
			$i = kjo_vl_geraet_info( $id );
			if ( $i ) {
				$d[ $id ] = $i;
			}
		}
		echo '<script id="kjo-vl-gi">window.KJO_GI=' . wp_json_encode( $d, JSON_HEX_TAG | JSON_HEX_AMP ) . ';' . KJO_VL_GI_JS . '</script>' . "\n"; // phpcs:ignore
	},
	41
);
define(
	'KJO_VL_GI_JS',
	<<<'KJOGI'
(function(){var D=window.KJO_GI||{},P=null,cur=null,pin=false,tm=0;
function mk(){P=document.createElement("div");P.className="kjo-vl-pop";P.setAttribute("role","dialog");P.hidden=true;document.body.appendChild(P);
 P.addEventListener("mouseenter",function(){clearTimeout(tm);});P.addEventListener("mouseleave",function(){if(!pin)hideSoon();});}
function show(b,p){var d=D[b.getAttribute("data-g")];if(!d)return;if(!P)mk();clearTimeout(tm);cur=b;pin=!!p;P.innerHTML="";
 var x=document.createElement("button");x.type="button";x.className="kjo-vl-pop-x";x.setAttribute("aria-label","Schließen");x.textContent="×";x.onclick=hide;P.appendChild(x);
 if(d.i){var im=document.createElement("img");im.src=d.i;im.alt=d.n;im.loading="lazy";P.appendChild(im);}
 var h=document.createElement("strong");h.textContent=d.n;P.appendChild(h);
 if(d.t){var t=document.createElement("p");t.textContent=d.t;P.appendChild(t);}
 var dl=document.createElement("dl");Object.keys(d.r||{}).forEach(function(k){var dt=document.createElement("dt"),dd=document.createElement("dd");dt.textContent=k;dd.textContent=d.r[k];dl.appendChild(dt);dl.appendChild(dd);});if(dl.childNodes.length)P.appendChild(dl);
 if(d.h){var a=document.createElement("a");a.href=d.h;a.target="_blank";a.rel="noopener nofollow";a.textContent="🔗 Herstellerseite";P.appendChild(a);}
 P.hidden=false;var r=b.getBoundingClientRect(),w=Math.min(320,window.innerWidth-16);P.style.width=w+"px";
 var l=Math.max(8,Math.min(r.left,window.innerWidth-w-8)),top=r.bottom+8;if(top+P.offsetHeight>window.innerHeight-8&&r.top-P.offsetHeight-8>8)top=r.top-P.offsetHeight-8;
 P.style.left=(l+window.scrollX)+"px";P.style.top=(top+window.scrollY)+"px";b.setAttribute("aria-expanded","true");}
function hide(){if(P)P.hidden=true;if(cur)cur.setAttribute("aria-expanded","false");cur=null;pin=false;}
function hideSoon(){clearTimeout(tm);tm=setTimeout(hide,250);}
var fine=window.matchMedia&&window.matchMedia("(hover:hover) and (pointer:fine)").matches;
document.addEventListener("click",function(e){var b=e.target.closest(".kjo-vl-gl");if(b){e.preventDefault();if(cur===b&&pin)hide();else show(b,true);return;}if(P&&!P.hidden&&!e.target.closest(".kjo-vl-pop"))hide();});
if(fine){document.addEventListener("mouseover",function(e){var b=e.target.closest&&e.target.closest(".kjo-vl-gl");if(b&&!pin&&cur!==b)show(b,false);});
 document.addEventListener("mouseout",function(e){var b=e.target.closest&&e.target.closest(".kjo-vl-gl");if(b&&!pin&&!(e.relatedTarget&&e.relatedTarget.closest&&e.relatedTarget.closest(".kjo-vl-pop")))hideSoon();});}
document.addEventListener("keydown",function(e){if(e.key==="Escape")hide();});
window.addEventListener("resize",hide);})();
KJOGI
);

add_shortcode(
	'kjo_pakete',
	function ( $atts ) {
		// modus="dj": Technik, die ich zur DJ-Buchung mitbringe – ohne Mietpreise und Mietkorb, mit Anfrage-Link.
		$dj = is_array( $atts ) && isset( $atts['modus'] ) && 'dj' === $atts['modus'];
		$h  = '<div class="kjo-vl-pakete">';
		foreach ( kjo_vl_items( 'kjo_paket' ) as $p ) {
			$pr  = kjo_vl_num( kjo_vl_get( $p->ID, 'preis' ) );
			$ka  = kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) );
			$h  .= '<article class="kjo-vl-paket">' . kjo_vl_img_html( $p, $p->post_title, 'kjo-vl-pimg' );
			$h  .= '<h3>' . esc_html( $p->post_title ) . '</h3>';
			$gae = trim( kjo_vl_get( $p->ID, 'gaeste' ) . ': ' . kjo_vl_get( $p->ID, 'anlaesse' ), ': ' );
			if ( $gae ) {
				$h .= '<p class="kjo-vl-gaeste">' . esc_html( $gae ) . '</p>';
			}
			$lines = array_filter( array_map( 'trim', explode( "\n", kjo_vl_get( $p->ID, 'inhalt' ) ) ) );
			if ( $lines ) {
				$h .= '<ul>';
				foreach ( $lines as $l ) {
					if ( $dj && 0 === strpos( $l, 'Audiokabel' ) ) {
						continue;
					}
					$h .= '<li>' . kjo_vl_zeile_html( $l ) . '</li>';
				}
				$h .= '</ul>';
			}
			if ( $dj ) {
				$kurz = trim( preg_replace( '/[–:].*$/u', '', $p->post_title ) );
				$url  = add_query_arg( 'paket', rawurlencode( $p->post_title ), home_url( '/kontakt/' ) ) . '#formular';
				$h   .= '<a class="kjo-vl-btn" href="' . esc_url( $url ) . '">' . esc_html( 0 === strpos( $p->post_title, 'Paket' ) ? 'Mit ' . $kurz . ' anfragen' : 'Dazu anfragen' ) . '</a></article>';
				continue;
			}
			$we = kjo_vl_we( $p->ID );
			$h .= '<p class="kjo-vl-preis">' . ( null === $pr ? 'Preis auf Anfrage: ihr bekommt ihn mit dem Angebot' : '<strong>' . esc_html( kjo_vl_eur( $pr ) ) . '</strong> pro Tag · <strong>' . esc_html( kjo_vl_eur( $we ) ) . '</strong> am Wochenende (Fr 15 – Mo 21 Uhr)' );
			$h .= ' · ' . ( null === $ka ? 'Kaution laut Angebot' : 'Kaution ' . esc_html( kjo_vl_eur( $ka ) ) ) . '</p>';
			if ( kjo_vl_get( $p->ID, 'hinweis' ) ) {
				$h .= '<p class="kjo-vl-hinweis">' . esc_html( kjo_vl_get( $p->ID, 'hinweis' ) ) . '</p>';
			}
			$h .= '<button type="button" class="kjo-vl-add kjo-vl-btn" data-k="p' . (int) $p->ID . '">In den Mietkorb</button></article>';
		}
		if ( $dj ) {
			return $h . '</div>' . kjo_vl_assets();
		}
		$w  = kjo_vl_opt( 'wochenende' );
		$h .= '</div><p class="kjo-vl-wochenende">' . ( $w ? 'Wochenendmiete: ' . esc_html( $w ) : 'Ihr feiert das ganze Wochenende? Fragt nach unserem Wochenendtarif.' ) . '</p>';
		return $h . kjo_vl_cart_html() . kjo_vl_assets(); // Mietkorb auch auf Seiten ohne Geräteliste (z. B. Equipment)
	}
);

/* [kjo_verleih] – Equipment-Browser, Mietkorb und Anfrage */
add_shortcode(
	'kjo_verleih',
	function () {
		$kats  = kjo_vl_kats();
		$wet   = kjo_vl_wetter();
		$items = kjo_vl_items( 'kjo_geraet' );
		$used  = array();
		foreach ( $items as $g ) {
			$used[ kjo_vl_get( $g->ID, 'kat' ) ] = 1;
		}
		$h  = '<div class="kjo-vl" id="kjo-vl">';
		$h .= '<div class="kjo-vl-tools"><input type="search" class="kjo-vl-q" placeholder="Gerät suchen, z. B. Bose, Subwoofer, Licht …" aria-label="Gerät suchen"><div class="kjo-vl-chips" role="group" aria-label="Kategorie"><button type="button" class="kjo-vl-chip on" data-kat="">Alle</button>';
		foreach ( $kats as $k => $l ) {
			if ( isset( $used[ $k ] ) ) {
				$h .= '<button type="button" class="kjo-vl-chip" data-kat="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</button>';
			}
		}
		$h .= '</div></div><ul class="kjo-vl-list">';
		foreach ( $items as $g ) {
			$id   = $g->ID;
			$pr   = kjo_vl_num( kjo_vl_get( $id, 'preis' ) );
			$ka   = kjo_vl_num( kjo_vl_get( $id, 'kaution' ) );
			$kat  = kjo_vl_get( $id, 'kat' );
			$w    = kjo_vl_get( $id, 'wetter' );
			$w    = isset( $wet[ $w ] ) ? $w : '';
			$ip   = kjo_vl_get( $id, 'wetter_ip' );
			$wtxt = $wet[ $w ][2] . ' ' . $wet[ $w ][1] . ( $ip && in_array( $w, array( 'spritz', 'wetterfest' ), true ) ? ' (' . $ip . ')' : '' );
			$kurz = array_filter( array( kjo_vl_get( $id, 'gewicht' ), kjo_vl_get( $id, 'leistung' ), kjo_vl_get( $id, 'gaeste' ) ) );
			$key  = kjo_vl_bild( $g );
			$hay  = strtolower( $g->post_title . ' ' . ( isset( $kats[ $kat ] ) ? $kats[ $kat ] : '' ) . ' ' . kjo_vl_get( $id, 'kurz' ) . ' ' . kjo_vl_get( $id, 'beschr' ) );
			$h   .= '<li class="kjo-vl-item" data-kat="' . esc_attr( $kat ) . '" data-q="' . esc_attr( $hay ) . '">';
			$h   .= '<div class="kjo-vl-row"><button type="button" class="kjo-vl-open" aria-expanded="false" aria-controls="kjo-vl-d' . (int) $id . '">' . kjo_vl_img_html( $g, $g->post_title, 'kjo-vl-thumb' );
			$h   .= '<span class="kjo-vl-main"><span class="kjo-vl-name">' . esc_html( $g->post_title ) . '</span>';
			$h   .= '<span class="kjo-vl-meta">' . esc_html( implode( ' · ', $kurz ? $kurz : array( kjo_vl_get( $id, 'kurz' ) ) ) ) . '</span>';
			$h   .= '<span class="kjo-vl-wet">' . esc_html( $wtxt ) . '</span></span></button>';
			$h   .= '<span class="kjo-vl-price">' . ( null === $pr ? 'auf Anfrage' : esc_html( kjo_vl_eur( $pr ) ) . '<small> / Tag</small><small class="kjo-vl-we">' . esc_html( kjo_vl_eur( kjo_vl_we( $id ) ) ) . ' / WE</small>' ) . '</span>';
			$h   .= '<button type="button" class="kjo-vl-add kjo-vl-plus" data-k="g' . (int) $id . '" aria-label="' . esc_attr( $g->post_title . ' in den Mietkorb' ) . '">+</button></div>';
			/* Details */
			$h .= '<div class="kjo-vl-det" id="kjo-vl-d' . (int) $id . '" hidden>';
			$gal = '';
			foreach ( kjo_vl_media_post( $g ) as $e ) {
				if ( ! empty( $e['vid'] ) ) {
					$gal .= '<video src="' . esc_url( $e['vid'] ) . '" controls preload="none" playsinline' . ( ! empty( $e['img'] ) ? ' poster="' . esc_url( $e['img'] ) . '"' : '' ) . '></video>';
				} elseif ( ! empty( $e['img'] ) ) {
					$gal .= '<img src="' . esc_url( $e['img'] ) . '"' . ( ! empty( $e['full'] ) ? ' data-full="' . esc_url( $e['full'] ) . '"' : '' ) . ' alt="' . esc_attr( $g->post_title ) . '" loading="lazy" decoding="async">';
				}
			}
			if ( $gal ) {
				$h .= '<div class="kjo-vl-gal">' . $gal . '</div>';
			}
			$txt = kjo_vl_get( $id, 'beschr' ) ? kjo_vl_get( $id, 'beschr' ) : kjo_vl_get( $id, 'kurz' );
			if ( $txt ) {
				$h .= '<p>' . nl2br( esc_html( $txt ) ) . '</p>';
			}
			$rows = array(
				'Geeignet für'     => kjo_vl_get( $id, 'gaeste' ),
				'Maße (B × L × H)' => kjo_vl_masse( $id ),
				'Gewicht'          => kjo_vl_get( $id, 'gewicht' ),
				'Transportvolumen' => kjo_vl_volumen( $id ),
				'Leistung / Pegel' => kjo_vl_get( $id, 'leistung' ),
				'Anschlüsse'       => kjo_vl_get( $id, 'anschluesse' ),
				'Strom'            => kjo_vl_get( $id, 'strom' ),
				'Wetterfestigkeit' => $wtxt,
				'Lieferumfang'     => str_replace( "\n", ', ', kjo_vl_get( $id, 'lieferumfang' ) ),
				'Miete'            => null === $pr ? '' : kjo_vl_eur( $pr ) . ' pro Tag · ' . kjo_vl_eur( kjo_vl_we( $id ) ) . ' am Wochenende (Fr 15 – Mo 21 Uhr)',
				'Kaution'          => null === $ka ? 'laut Angebot' : kjo_vl_eur( $ka ),
			);
			$h .= '<dl class="kjo-vl-dl">';
			foreach ( $rows as $l => $v ) {
				if ( '' !== trim( $v ) ) {
					$h .= '<dt>' . esc_html( $l ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
				}
			}
			$h  .= '</dl>';
			$anl = kjo_vl_get( $id, 'anleitung' );
			if ( $anl ) {
				$h .= '<p class="kjo-vl-anl"><a href="' . esc_url( $anl ) . '" target="_blank" rel="noopener">📄 Bedienungsanleitung (PDF, Deutsch)</a> <button type="button" class="kjo-vl-report" data-id="' . (int) $id . '">Link defekt? Melden</button></p>';
			}
			$her = kjo_vl_get( $id, 'hersteller' );
			if ( $her ) {
				$h .= '<p class="kjo-vl-anl"><a href="' . esc_url( $her ) . '" target="_blank" rel="noopener nofollow">🔗 Herstellerseite</a></p>';
			}
			$h .= '</div></li>';
		}
		$h .= '</ul><p class="kjo-vl-none" hidden>Kein Gerät gefunden. Fragt einfach nach – vieles ist auf Anfrage möglich.</p></div>';
		return $h . kjo_vl_cart_html() . kjo_vl_assets();
	}
);

/* [kjo_verleih_hinweise] – „Gut zu wissen“ mit Preis-Ersatztexten */
add_shortcode(
	'kjo_verleih_hinweise',
	function () {
		$any = false;
		foreach ( array_merge( kjo_vl_items( 'kjo_paket' ), kjo_vl_items( 'kjo_geraet' ) ) as $p ) {
			if ( null !== kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) ) ) {
				$any = true;
				break;
			}
		}
		$sp = kjo_vl_num( kjo_vl_opt( 'sorglos_prozent' ) );
		$sm = kjo_vl_num( kjo_vl_opt( 'sorglos_max' ) );
		$l  = array(
			array( 'Kaution', ( $any ? 'Die Höhe steht bei jedem Paket und Gerät.' : 'Die Höhe steht im Angebot.' ) . ' Bar bei der Abholung oder vorab per Überweisung. Ihr bekommt sie nach Prüfung der Rückgabe innerhalb von 3 Werktagen zurück.' ),
			array( 'Wochenende', kjo_vl_opt( 'wochenende' ) ? kjo_vl_opt( 'wochenende' ) : 'Freitag bis Montag zum Wochenendpreis.' ),
			array( 'Lieferung', kjo_vl_opt( 'lieferung' ) ? kjo_vl_opt( 'lieferung' ) : 'Nach Verfügbarkeit gegen Gebühr, der Preis steht im Angebot.' ),
			array( 'Aufbau', kjo_vl_opt( 'aufbau' ) ? kjo_vl_opt( 'aufbau' ) : 'Nach Verfügbarkeit gegen Gebühr, der Preis steht im Angebot.' ),
			array( 'Mitbringen', 'Personalausweis. Mieten ab 18 Jahren.' ),
			array( 'Passt das in mein Auto?', 'Paket S passt in einen Kombi (die Lightbar ist 1,30 m lang). Für M braucht ihr einen Van oder Transporter (Traversentasche 1,54 m). Paket L liefere und baue ich selbst auf; auf Wunsch auch S und M, nach Verfügbarkeit und gegen Gebühr.' ),
			array( 'Akku-Scheinwerfer', 'Ich übergebe sie vollgeladen im Lade-Case – zum Nachladen einfach das Case einstecken.' ),
			array( 'Nebel', 'Bitte vorher mit der Location klären, wegen der Rauchmelder.' ),
			array( 'Strom', 'Normale 230-V-Steckdosen. Verteiler und Verlängerungen bekommt ihr mit.' ),
			array( 'Sorglos-Option', ( null !== $sp && null !== $sm ) ? 'Für ' . str_replace( '.', ',', (string) $sp ) . ' % Aufpreis haftet ihr bei Schäden höchstens mit ' . kjo_vl_eur( $sm ) . ', außer bei Vorsatz oder grober Fahrlässigkeit.' : 'Auf Wunsch könnt ihr euer Haftungsrisiko gegen Aufpreis begrenzen. Die Details stehen im Angebot.' ),
		);
		$h = '<ul class="kjo-vl-tips">';
		foreach ( $l as $r ) {
			$h .= '<li><strong>' . esc_html( $r[0] ) . ':</strong> ' . esc_html( $r[1] ) . '</li>';
		}
		return $h . '</ul>' . kjo_vl_assets();
	}
);

/* Mietkorb (Leiste + Fenster mit Anfrageformular) – einmal pro Seite */
function kjo_vl_cart_html() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	$cat  = array();
	foreach ( kjo_vl_items( 'kjo_geraet' ) as $g ) {
		$cat[ 'g' . $g->ID ] = array( 'n' => $g->post_title, 'p' => kjo_vl_num( kjo_vl_get( $g->ID, 'preis' ) ), 'w' => kjo_vl_we( $g->ID ), 'k' => kjo_vl_num( kjo_vl_get( $g->ID, 'kaution' ) ) );
	}
	foreach ( kjo_vl_items( 'kjo_paket' ) as $p ) {
		$cat[ 'p' . $p->ID ] = array( 'n' => $p->post_title, 'p' => kjo_vl_num( kjo_vl_get( $p->ID, 'preis' ) ), 'w' => kjo_vl_we( $p->ID ), 'k' => kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) ) );
	}
	$api = esc_url_raw( rest_url( 'kjo/v1/' ) );
	ob_start();
	?>
<div class="kjo-vl-bar" hidden><button type="button" class="kjo-vl-bar-btn"><span class="kjo-vl-bar-txt"></span><span class="kjo-vl-bar-go">Anfrage senden →</span></button></div>
<div class="kjo-vl-modal" hidden role="dialog" aria-modal="true" aria-labelledby="kjo-vl-mt"><div class="kjo-vl-panel">
<button type="button" class="kjo-vl-x" aria-label="Mietkorb schließen">×</button>
<h2 id="kjo-vl-mt">Euer Mietkorb</h2>
<ul class="kjo-vl-cart"></ul>
<p class="kjo-vl-sum"></p>
<form class="kjo-vl-form" novalidate>
<h3>Mietanfrage – kostenlos und unverbindlich</h3>
<div class="kjo-vl-grid">
<label>Abholung bzw. Beginn * <small class="kjo-vl-hint">Datum wählen</small><input type="date" name="von" required></label>
<label>Rückgabe bzw. Ende * <small class="kjo-vl-hint">Datum wählen</small><input type="date" name="bis" required></label>
</div>
<fieldset><legend>Übergabe *</legend>
<label class="kjo-vl-r"><input type="radio" name="uebergabe" value="Abholung in Fellheim" checked> Abholung in Fellheim (mit Einweisung)</label>
<label class="kjo-vl-r"><input type="radio" name="uebergabe" value="Lieferung und Abholung"> Lieferung und Abholung (nach Verfügbarkeit, gegen Gebühr)</label>
<label class="kjo-vl-r"><input type="radio" name="uebergabe" value="Lieferung, Aufbau/Verkabelung, Abbau und Abholung"> Lieferung, Aufbau/Verkabelung, Abbau und Abholung (nach Verfügbarkeit, gegen Gebühr)</label>
</fieldset>
<label class="kjo-vl-ort" hidden>Ort der Feier (für die Lieferung) *<input type="text" name="ort" autocomplete="address-level2"></label>
<div class="kjo-vl-grid">
<label>Anlass<input type="text" name="anlass" placeholder="z. B. 40. Geburtstag"></label>
<label>Gästezahl<input type="number" name="gaeste" min="1" inputmode="numeric"></label>
</div>
<label>Name *<input type="text" name="name" required autocomplete="name"></label>
<div class="kjo-vl-grid">
<label>E-Mail *<input type="email" name="email" required autocomplete="email"></label>
<label>Telefon<input type="tel" name="telefon" autocomplete="tel"></label>
</div>
<label>Nachricht<textarea name="nachricht" rows="3" placeholder="Fragen, Wünsche, Location …"></textarea></label>
<label class="kjo-vl-hp" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
<p class="kjo-vl-small">Eure Angaben nutze ich nur für eure Anfrage, siehe <a href="/datenschutz/">Datenschutzerklärung</a>. Verbindlich wird die Miete erst mit Angebot und Mietvertrag.</p>
<p class="kjo-vl-err" role="alert" hidden></p>
<button type="submit" class="kjo-vl-btn kjo-vl-send">Mietanfrage senden</button>
</form>
<div class="kjo-vl-ok" hidden><h3>Danke für eure Anfrage!</h3><p>Ich prüfe die Verfügbarkeit und melde mich innerhalb von 24 Stunden mit Angebot und Mietbedingungen bei euch. Eine Bestätigung ist per E-Mail unterwegs.</p><button type="button" class="kjo-vl-btn kjo-vl-x2">Schließen</button></div>
</div></div>
	<?php
	$GLOBALS['kjo_vl_data'] = array( 'api' => $api, 'cat' => (object) $cat, 'ts' => time() );
	return (string) ob_get_clean();
}

/* CSS + JS – einmal pro Seite */
function kjo_vl_assets() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	$GLOBALS['kjo_vl_js'] = true; // Skript kommt in den Footer – so verändern Inhaltsfilter (z. B. „&“ → „&#038;“) es nicht.
	return '<style id="kjo-vl-css">' . KJO_VL_CSS . '</style>';
}
add_action(
	'wp_footer',
	function () {
		if ( empty( $GLOBALS['kjo_vl_js'] ) ) {
			return;
		}
		$d = isset( $GLOBALS['kjo_vl_data'] ) ? $GLOBALS['kjo_vl_data'] : array();
		echo '<script id="kjo-vl-js">window.KJO_VL=' . wp_json_encode( $d ) . ';' . KJO_VL_JS . '</script>' . "\n"; // phpcs:ignore
	},
	40
);

define(
	'KJO_VL_CSS',
	<<<'KJOCSS'
.kjo-vl [hidden],.kjo-vl-modal[hidden],.kjo-vl-modal [hidden],.kjo-vl-bar[hidden]{display:none!important}
.kjo-vl button,.kjo-vl-pakete button,.kjo-vl-bar button,.kjo-vl-modal button{all:unset;box-sizing:border-box;cursor:pointer;-webkit-tap-highlight-color:transparent}
.kjo-vl button:focus-visible,.kjo-vl-pakete button:focus-visible,.kjo-vl-bar button:focus-visible,.kjo-vl-modal button:focus-visible{outline:2px solid #B29D75;outline-offset:2px}
.kjo-vl,.kjo-vl-pakete,.kjo-vl-tips,.kjo-vl-modal{font-family:"Fira Sans",sans-serif;color:#F3F1E9;text-align:left}
.kjo-vl-pakete .kjo-vl-btn,.kjo-vl-modal .kjo-vl-btn{display:inline-block;background:#B29D75;color:#0F0C07;border:0;border-radius:2px;padding:14px 26px;font:500 14px/1 "Fira Sans",sans-serif;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:.2s}
.kjo-vl-pakete .kjo-vl-btn:hover,.kjo-vl-modal .kjo-vl-btn:hover{background:#C4B08A}.kjo-vl-pakete .kjo-vl-btn{align-self:flex-start}.kjo-vl-pakete a.kjo-vl-btn{color:#0F0C07!important;text-decoration:none!important}
.kjo-vl-gl{background:none;border:0;border-bottom:1px dotted #B29D75;padding:0;margin:0;color:inherit;font:inherit;line-height:inherit;letter-spacing:inherit;text-transform:none;cursor:help;border-radius:0}.kjo-vl-gl:hover,.kjo-vl-gl[aria-expanded=true]{color:#B29D75;background:none}
.kjo-vl-pop{position:absolute;z-index:99990;background:#1A1712;border:1px solid #B29D75;border-radius:4px;padding:16px;color:#F3F1E9;font:14px/1.5 "Fira Sans",sans-serif;box-shadow:0 18px 50px rgba(0,0,0,.55);box-sizing:border-box}.kjo-vl-pop[hidden]{display:none}
.kjo-vl-pop img{display:block;width:100%;height:150px;object-fit:contain;background:#0F0C07;border-radius:3px;margin:0 0 10px}.kjo-vl-pop strong{display:block;font-size:15px;font-weight:500;margin:0 22px 6px 0}.kjo-vl-pop p{margin:0 0 8px;color:#D9D5CA}
.kjo-vl-pop dl{display:grid;grid-template-columns:auto 1fr;gap:2px 10px;margin:0 0 8px;font-size:13px}.kjo-vl-pop dt{color:#A39E93}.kjo-vl-pop dd{margin:0}.kjo-vl-pop a{color:#B29D75;font-size:13px}
.kjo-vl-pop-x{position:absolute;top:6px;right:8px;background:none;border:0;color:#A39E93;font-size:22px;line-height:1;cursor:pointer;padding:2px 6px}
.kjo-vl-pakete{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;width:100%;max-width:1200px}
.kjo-vl-paket{background:#1A1712;border-top:2px solid #B29D75;padding:30px 28px;display:flex;flex-direction:column;gap:12px}
.kjo-vl-paket h3{margin:0;font:400 24px/1.25 "Fira Sans",sans-serif;color:#F3F1E9}
.kjo-vl-paket ul{margin:4px 0;padding:0;list-style:none}.kjo-vl-paket li{position:relative;margin:0;padding:5px 0 5px 22px;color:#F3F1E9;font-size:16px;font-weight:300;line-height:1.5}
.kjo-vl-paket li:before{content:"✓";position:absolute;left:0;color:#B29D75}
.kjo-vl-gaeste{margin:0;color:#B29D75;font-size:15px}.kjo-vl-preis{margin:auto 0 0;color:#F3F1E9;font-size:16px}.kjo-vl-preis strong{color:#B29D75;font-weight:500;font-size:20px}
.kjo-vl-hinweis{margin:0;color:#A39E93;font-size:14px;line-height:1.6;border-left:2px solid rgba(178,157,117,.4);padding-left:12px}
.kjo-vl-pimg{width:100%;aspect-ratio:16/10;object-fit:cover;border:1px solid #B29D75;display:block}
.kjo-vl-pimg.kjo-vl-ph{display:none}
.kjo-vl-wochenende{margin:18px 0 0;color:#A39E93;text-align:center;font-family:"Fira Sans",sans-serif}
.kjo-vl{width:100%;max-width:1000px}
.kjo-vl-tools{display:flex;flex-direction:column;gap:14px;margin-bottom:18px}
.kjo-vl-q{width:100%;box-sizing:border-box;background:#0F0C07;border:1px solid rgba(178,157,117,.5);color:#F3F1E9;padding:14px 16px;font:16px "Fira Sans",sans-serif;border-radius:2px}
.kjo-vl-q:focus{outline:none;border-color:#B29D75}
.kjo-vl-chips{display:flex;flex-wrap:wrap;gap:8px}
.kjo-vl .kjo-vl-chip{background:transparent;border:1px solid rgba(178,157,117,.45);color:#F3F1E9;border-radius:40px;padding:8px 14px;font:14px "Fira Sans",sans-serif;cursor:pointer}
.kjo-vl .kjo-vl-chip.on,.kjo-vl .kjo-vl-chip:hover{background:#B29D75;color:#0F0C07;border-color:#B29D75}
.kjo-vl-list{list-style:none;margin:0;padding:0;border-top:1px solid rgba(178,157,117,.25)}
.kjo-vl-item{border-bottom:1px solid rgba(178,157,117,.25)}
.kjo-vl-row{display:flex;align-items:center;gap:12px;padding:12px 0}
.kjo-vl .kjo-vl-open{flex:1;display:flex;align-items:center;gap:14px;background:none;border:0;color:inherit;text-align:left;cursor:pointer;padding:0;min-width:0;font:inherit}
.kjo-vl-thumb{width:64px;height:64px;flex:none;object-fit:cover;border:1px solid #B29D75;display:block}
.kjo-vl-ph{display:flex;align-items:center;justify-content:center;color:#B29D75;background:#1A1712}.kjo-vl-ph svg{width:28px;height:28px}
.kjo-vl-main{display:flex;flex-direction:column;gap:3px;min-width:0}
.kjo-vl-name{font-size:17px;color:#F3F1E9}.kjo-vl-meta{font-size:14px;color:#A39E93}.kjo-vl-wet{font-size:13px;color:#A39E93}
.kjo-vl-price{flex:none;color:#B29D75;font-size:15px;white-space:nowrap}.kjo-vl-price small{color:#A39E93}.kjo-vl-price .kjo-vl-we{display:block;text-align:right;font-size:12px}
.kjo-vl .kjo-vl-plus{display:flex;align-items:center;justify-content:center;flex:none;width:44px;height:44px;border-radius:50%;border:1px solid #B29D75;background:transparent;color:#B29D75;font:300 26px/1 "Fira Sans",sans-serif;cursor:pointer}
.kjo-vl .kjo-vl-plus:hover{background:#B29D75;color:#0F0C07}
.kjo-vl-det{padding:4px 0 22px 78px;color:#A39E93;line-height:1.7}
.kjo-vl-det p{margin:0 0 12px}
.kjo-vl-gal{display:flex;gap:10px;overflow-x:auto;margin-bottom:14px}.kjo-vl-gal img,img.kjo-vl-pimg{cursor:zoom-in}.kjo-vl-lb{position:fixed;inset:0;z-index:99995;background:rgba(8,6,4,.94);display:flex;align-items:center;justify-content:center;flex-direction:column;padding:56px 64px 24px;box-sizing:border-box}.kjo-vl-lb[hidden],.kjo-vl-lb [hidden]{display:none!important}.kjo-vl-lb img{max-width:100%;max-height:calc(100vh - 120px);width:auto;height:auto;object-fit:contain;border:1px solid #B29D75;background:#000;box-shadow:0 20px 50px rgba(0,0,0,.6)}.kjo-vl-lb p{margin:14px 0 0;font:400 15px/1.4 "Fira Sans",sans-serif;color:#C9C3B5;text-align:center}.kjo-vl-lb button{all:unset;box-sizing:border-box;cursor:pointer;position:absolute;display:flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:50%;border:1px solid rgba(178,157,117,.7);color:#F3F1E9;background:rgba(22,19,16,.85);font:300 30px/1 "Fira Sans",sans-serif;-webkit-tap-highlight-color:transparent}.kjo-vl-lb button:hover,.kjo-vl-lb button:focus-visible{background:#B29D75;color:#0F0C07;outline:none}.kjo-vl-lb .kjo-vl-lbx{top:12px;right:12px}.kjo-vl-lb .kjo-vl-lbp{left:10px;top:50%;margin-top:-23px}.kjo-vl-lb .kjo-vl-lbn{right:10px;top:50%;margin-top:-23px}@media(max-width:600px){.kjo-vl-lb{padding:56px 8px 16px}.kjo-vl-lb .kjo-vl-lbp,.kjo-vl-lb .kjo-vl-lbn{top:auto;bottom:14px;margin:0}.kjo-vl-lb .kjo-vl-lbp{left:calc(50% - 56px)}.kjo-vl-lb .kjo-vl-lbn{right:calc(50% - 56px)}.kjo-vl-lb img{max-height:calc(100vh - 170px)}}.kjo-vl-gal img,.kjo-vl-gal video{height:180px;width:auto;border:1px solid #B29D75;flex:none;background:#000}
.kjo-vl-dl{display:grid;grid-template-columns:max-content 1fr;gap:6px 18px;margin:0 0 12px}.kjo-vl-dl dt{color:#F3F1E9}.kjo-vl-dl dd{margin:0}
.kjo-vl-anl a{color:#B29D75}.kjo-vl .kjo-vl-report{background:none;border:0;color:#A39E93;font:13px "Fira Sans",sans-serif;text-decoration:underline;cursor:pointer;margin-left:8px;padding:4px}
.kjo-vl-tips{list-style:none;margin:0;padding:0;width:100%;max-width:860px}.kjo-vl-tips li{padding:12px 0;border-bottom:1px solid rgba(178,157,117,.2);color:#A39E93;line-height:1.7}.kjo-vl-tips strong{color:#F3F1E9;font-weight:500}
.kjo-vl-bar{position:fixed;left:0;right:0;bottom:0;max-width:none!important;margin:0!important;box-sizing:border-box;z-index:9990;padding:10px 16px calc(10px + env(safe-area-inset-bottom));background:rgba(15,12,7,.96);border-top:1px solid #B29D75}
.kjo-vl-bar .kjo-vl-bar-btn{width:100%;max-width:1000px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:12px;background:none;border:0;color:#F3F1E9;font:15px "Fira Sans",sans-serif;cursor:pointer;padding:6px 0;text-align:left}
.kjo-vl-bar-go{flex:none;background:#B29D75;color:#0F0C07;padding:12px 16px;border-radius:2px;font-weight:500;letter-spacing:1px;text-transform:uppercase;font-size:13px}
.kjo-vl-modal{position:fixed;inset:0;z-index:99990;background:rgba(0,0,0,.75);display:flex;align-items:flex-start;justify-content:center;overflow-y:auto;padding:24px 12px}
.kjo-vl-panel{position:relative;background:#1A1712;border-top:2px solid #B29D75;width:100%;max-width:640px;padding:32px 24px;box-sizing:border-box}
.kjo-vl-panel h2{margin:0 0 14px;font:300 30px/1.2 "Fira Sans",sans-serif;color:#F3F1E9}.kjo-vl-panel h3{margin:20px 0 12px;font:400 20px/1.3 "Fira Sans",sans-serif;color:#F3F1E9}
.kjo-vl-modal .kjo-vl-x{position:absolute;top:8px;right:10px;background:none;border:0;color:#F3F1E9;font-size:32px;line-height:1;cursor:pointer;padding:6px 10px}
.kjo-vl-cart{list-style:none;margin:0;padding:0}.kjo-vl-cart li{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid rgba(178,157,117,.2)}
.kjo-vl-cart .n{flex:1;min-width:0}.kjo-vl-cart .p{color:#B29D75;font-size:14px;white-space:nowrap}
.kjo-vl-modal .kjo-vl-cart button{display:flex;align-items:center;justify-content:center;flex:none;width:32px;height:32px;border:1px solid rgba(178,157,117,.6);background:none;color:#F3F1E9;border-radius:50%;cursor:pointer;font-size:17px;line-height:1}
.kjo-vl-cart .q{min-width:22px;text-align:center}
.kjo-vl-sum{color:#A39E93;font-size:14px;line-height:1.6;margin:12px 0 0}.kjo-vl-sum strong{color:#F3F1E9}
.kjo-vl-form label{display:block;color:#F3F1E9;font-size:14px;margin:0 0 12px}
.kjo-vl-form input[type=text],.kjo-vl-form input[type=email],.kjo-vl-form input[type=tel],.kjo-vl-form input[type=date],.kjo-vl-form input[type=number],.kjo-vl-form textarea{display:block;width:100%;box-sizing:border-box;margin-top:6px;background:#0F0C07;border:1px solid rgba(178,157,117,.45);color:#F3F1E9;padding:11px 12px;font:16px "Fira Sans",sans-serif;border-radius:2px;color-scheme:dark}
.kjo-vl-form input:focus,.kjo-vl-form textarea:focus{outline:none;border-color:#B29D75}
.kjo-vl-form input[type=date]:invalid{color:#7d776c;font-style:italic}
.kjo-vl-form input[type=date]::-webkit-datetime-edit{color:inherit}
.kjo-vl-hint{color:#A39E93;font-size:12px;font-weight:400}
.kjo-vl-form label:has(input[type=date]:valid) .kjo-vl-hint{display:none}
.kjo-vl-form input:-webkit-autofill{-webkit-box-shadow:0 0 0 40px #0F0C07 inset!important;-webkit-text-fill-color:#F3F1E9!important;caret-color:#F3F1E9}
.kjo-vl-form .kjo-vl-bad{border-color:#ff9b8a!important;box-shadow:0 0 0 1px #ff9b8a}
.kjo-vl-form input.kjo-vl-bad:-webkit-autofill{-webkit-box-shadow:0 0 0 40px #0F0C07 inset,0 0 0 1px #ff9b8a!important}
.kjo-vl-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}
.kjo-vl-form fieldset{border:0;margin:0 0 12px;padding:0}.kjo-vl-form legend{color:#F3F1E9;font-size:14px;margin-bottom:6px}
.kjo-vl-r{display:flex!important;gap:8px;align-items:flex-start;color:#A39E93!important;margin:0 0 6px!important}.kjo-vl-r input{margin-top:4px;accent-color:#B29D75}
.kjo-vl-hp{position:absolute!important;left:-9999px!important;height:1px;overflow:hidden}
.kjo-vl-small{color:#A39E93;font-size:13px;line-height:1.6}.kjo-vl-small a{color:#B29D75}
.kjo-vl-err{color:#ff9b8a;font-size:14px}
.kjo-vl-ok p{color:#A39E93;line-height:1.7}
.kjo-vl-empty{color:#A39E93}
body.kjo-vl-has-bar{padding-bottom:84px}
@media(max-width:600px){.kjo-vl-det{padding-left:0}.kjo-vl-thumb{width:52px;height:52px}.kjo-vl-price{font-size:13px}.kjo-vl-grid{grid-template-columns:1fr}.kjo-vl-gal img,.kjo-vl-gal video{height:140px}.kjo-vl-dl{grid-template-columns:1fr}.kjo-vl-dl dd{margin-bottom:6px}}
KJOCSS
);

define(
	'KJO_VL_JS',
	<<<'KJOJS'
(function(){
var V={},C={},K="kjo_mietkorb",cart=[];
function load(){try{var j=JSON.parse(localStorage.getItem(K)||"[]");cart=(Array.isArray(j)?j:[]).filter(function(e){return e&&C[e.k]&&e.q>0;});}catch(e){cart=[];}}
function save(){try{localStorage.setItem(K,JSON.stringify(cart));}catch(e){}}
function eur(n){return (Math.round(n*100)/100).toLocaleString("de-DE",{minimumFractionDigits:n%1?2:0,maximumFractionDigits:2})+" €";}
function q$(s,r){return (r||document).querySelector(s);}
function all(s,r){return [].slice.call((r||document).querySelectorAll(s));}
function count(){return cart.reduce(function(a,e){return a+e.q;},0);}
function add(k){if(!C[k])return;var e=cart.filter(function(x){return x.k===k;})[0];if(e)e.q++;else cart.push({k:k,q:1});save();render();toast(C[k].n+" liegt im Mietkorb");}
function toast(t){var d=document.createElement("div");d.textContent=t;d.setAttribute("role","status");d.style.cssText="position:fixed;left:50%;bottom:96px;transform:translateX(-50%);z-index:99995;background:#1A1712;color:#F3F1E9;border:1px solid #B29D75;border-radius:4px;padding:10px 16px;font:15px/1.4 'Fira Sans',sans-serif;max-width:90vw;text-align:center";document.body.appendChild(d);setTimeout(function(){d.remove();},1900);}
function tarif(){var f=q$(".kjo-vl-form");if(!f||!f.elements.von.value)return null;var a=new Date(f.elements.von.value+"T12:00:00"),b=new Date((f.elements.bis.value||f.elements.von.value)+"T12:00:00");if(isNaN(a)||isNaN(b)||b<a)return null;
  var D=864e5,ds=[],sat={},d,w,n=0;for(d=new Date(a);d<=b;d=new Date(d.getTime()+D)){w=d.getDay();ds.push([d,w]);if(w===6||w===0)sat[new Date(d.getTime()-(w===0?D:0)).toDateString()]=1;}
  var we=Object.keys(sat).length;if(!we)return{we:0,d:Math.max(1,Math.round((b-a)/D))};
  ds.forEach(function(x){var d=x[0],w=x[1];if(w===6||w===0)return;if(w===5&&sat[new Date(d.getTime()+D).toDateString()])return;if(w===1&&sat[new Date(d.getTime()-2*D).toDateString()])return;n++;});return{we:we,d:n};}
function tTxt(t){var p=[];if(t.we)p.push(t.we+(t.we===1?" Wochenende":" Wochenenden"));if(t.d)p.push(t.d+(t.d===1?" Tag":" Tage"));return p.join(" + ");}
function iPrice(c,t){if(c.p==null)return null;if(!t)return c.p;return t.we*(c.w!=null?c.w:Math.ceil(c.p*1.5))+t.d*c.p;}
function sums(){var t=tarif(),p=0,k=0,pk=true,kk=true;cart.forEach(function(e){var c=C[e.k],ip=iPrice(c,t);if(ip==null)pk=false;else p+=ip*e.q;if(c.k==null)kk=false;else k+=c.k*e.q;});return{p:pk?p:null,k:kk?k:null,t:t};}
var AV={};
function stTxt(k){var v=AV[k];return v==="frei"?["frei","✓ verfügbar"]:v==="vor"?["vor","◐ vorreserviert, gerne trotzdem anfragen"]:v==="belegt"?["belegt","✕ in diesem Zeitraum vergeben"]:null;}
function avail(){var f=q$(".kjo-vl-form"),K=window.KJO_KAL;if(!f||!K||!window.fetch)return;var v=f.elements.von.value,b=f.elements.bis.value;if(!v){AV={};render();return;}
  fetch(K.api+(K.api.indexOf("?")>-1?"&":"?")+"von="+v+"&bis="+(b||v),{credentials:"omit"}).then(function(r){return r.json()}).then(function(j){if(f.elements.von.value!==v)return;AV=(j&&j.items)||{};render();}).catch(function(){});}
function render(){
  var bar=q$(".kjo-vl-bar");if(!bar)return;var n=count(),s=sums();
  bar.hidden=!n;document.body.classList.toggle("kjo-vl-has-bar",!!n);
  q$(".kjo-vl-bar-txt").textContent="🛒 "+n+(n===1?" Artikel":" Artikel")+(s.p!=null?" · ca. "+eur(s.p)+(s.t?"":" pro Tag"):"")+(s.k!=null?" · Kaution "+eur(s.k):"");
  var ul=q$(".kjo-vl-cart");ul.innerHTML="";
  if(!n){var li=document.createElement("li");li.className="kjo-vl-empty";li.textContent="Euer Mietkorb ist leer.";ul.appendChild(li);}
  cart.forEach(function(e){var c=C[e.k],li=document.createElement("li");
    li.innerHTML='<span class="n"></span><button type="button" data-d="-1" aria-label="weniger">−</button><span class="q"></span><button type="button" data-d="1" aria-label="mehr">+</button><span class="p"></span><button type="button" data-d="x" aria-label="entfernen">×</button>';
    li.querySelector(".n").textContent=c.n;var st=stTxt(e.k);if(st){var sp=document.createElement("span");sp.className="st "+st[0];sp.textContent=st[1];li.querySelector(".n").appendChild(sp);}li.querySelector(".q").textContent=e.q;li.querySelector(".p").textContent=c.p==null?"auf Anfrage":eur(iPrice(c,s.t)*e.q);
    all("button",li).forEach(function(b){b.addEventListener("click",function(){var d=b.getAttribute("data-d");if(d==="x")e.q=0;else e.q+=+d;cart=cart.filter(function(x){return x.q>0;});save();render();});});
    ul.appendChild(li);});
  var sum=q$(".kjo-vl-sum");
  var bad=cart.some(function(e){return AV[e.k]==="belegt"||AV[e.k]==="vor";});
  sum.innerHTML=!n?"":(bad?"<strong>Nicht alles ist in diesem Zeitraum frei.</strong> Fragt trotzdem an, oft finde ich eine Alternative.<br>":"")+(s.p!=null?(s.t?"Summe für "+tTxt(s.t)+": <strong>ca. "+eur(s.p)+"</strong>":"Summe: <strong>ca. "+eur(s.p)+" pro Tag</strong>")+(s.k!=null?" · Kaution "+eur(s.k):"")+"<br>"+(s.t&&s.t.we?"Sobald Samstag oder Sonntag dabei ist, gilt der Wochenendpreis (Fr 15 – Mo 21 Uhr). ":"")+"Unverbindliche Schätzung ohne Lieferung und Aufbau, der endgültige Preis kommt mit dem Angebot.":"Den Gesamtpreis bekommt ihr mit dem Angebot, kostenlos und unverbindlich.");
}
var LB=null,LBL=[],LBI=0;
function lbShow(i){LBI=(i+LBL.length)%LBL.length;var im=LBL[LBI],e=q$("img",LB),sm=im.currentSrc||im.src,big=im.getAttribute("data-full")||sm.replace(/-\d+x\d+(\.[a-z0-9]+)(\?.*)?$/i,"$1$2");e.onerror=big!==sm?function(){e.onerror=null;e.src=sm;}:null;e.src=big;e.alt=im.alt||"";q$("p",LB).textContent=(im.alt||"")+(LBL.length>1?" · "+(LBI+1)+" / "+LBL.length:"");q$(".kjo-vl-lbp",LB).hidden=q$(".kjo-vl-lbn",LB).hidden=LBL.length<2;}
function lbOpen(im){
  if(!LB){LB=document.createElement("div");LB.className="kjo-vl-lb";LB.hidden=true;LB.setAttribute("role","dialog");LB.setAttribute("aria-modal","true");LB.setAttribute("aria-label","Bildansicht");
    LB.innerHTML='<img alt=""><p></p><button type="button" class="kjo-vl-lbp" aria-label="Vorheriges Bild">\u2039</button><button type="button" class="kjo-vl-lbn" aria-label="N\u00e4chstes Bild">\u203a</button><button type="button" class="kjo-vl-lbx" aria-label="Schlie\u00dfen">\u00d7</button>';
    document.body.appendChild(LB);
    LB.addEventListener("click",function(ev){var t=ev.target;if(t.closest(".kjo-vl-lbp"))lbShow(LBI-1);else if(t.closest(".kjo-vl-lbn"))lbShow(LBI+1);else if(t.tagName!=="IMG")lbClose();});
    var x0=null;LB.addEventListener("touchstart",function(e){x0=e.touches[0].clientX;},{passive:true});
    LB.addEventListener("touchend",function(e){if(x0===null)return;var d=e.changedTouches[0].clientX-x0;x0=null;if(Math.abs(d)>50&&LBL.length>1)lbShow(LBI+(d<0?1:-1));});}
  var g=im.closest(".kjo-vl-gal");LBL=g?all("img",g):[im];
  LB.hidden=false;document.documentElement.style.overflow="hidden";lbShow(LBL.indexOf(im));q$(".kjo-vl-lbx",LB).focus();}
function lbClose(){if(!LB||LB.hidden)return false;LB.hidden=true;q$("img",LB).removeAttribute("src");if(!(q$(".kjo-vl-modal")&&!q$(".kjo-vl-modal").hidden))document.documentElement.style.overflow="";return true;}
function openM(){var m=q$(".kjo-vl-modal");if(!m)return;m.hidden=false;document.documentElement.style.overflow="hidden";var x=q$(".kjo-vl-x",m);x&&x.focus();}
function closeM(){var m=q$(".kjo-vl-modal");if(!m)return;m.hidden=true;document.documentElement.style.overflow="";}
function filter(){var box=q$("#kjo-vl");if(!box)return;var t=(q$(".kjo-vl-q",box).value||"").toLowerCase().trim().split(/\s+/).filter(Boolean),on=q$(".kjo-vl-chip.on",box),kat=on?on.getAttribute("data-kat"):"",vis=0;
  all(".kjo-vl-item",box).forEach(function(li){var h=li.getAttribute("data-q")||"",ok=(!kat||li.getAttribute("data-kat")===kat)&&t.every(function(w){return h.indexOf(w)>-1;});li.hidden=!ok;if(ok)vis++;});
  q$(".kjo-vl-none",box).hidden=!!vis;}
function track(){try{if(typeof window.kjoTrack==="function"){window.kjoTrack("generate_lead",{method:"mietanfrage"});return;}var p={method:"mietanfrage",page_path:location.pathname};if(typeof window.gtag==="function")window.gtag("event","generate_lead",p);else(window.dataLayer=window.dataLayer||[]).push(Object.assign({event:"generate_lead"},p));if(typeof window.clarity==="function")window.clarity("event","generate_lead");}catch(e){}}
function post(path,data){return fetch(V.api+path,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(data),credentials:"omit"}).then(function(r){return r.json().then(function(j){return{ok:r.ok,j:j};});});}
function init(){
  V=window.KJO_VL||{};C=V.cat||{};
  [".kjo-vl-bar",".kjo-vl-modal"].forEach(function(s){var e=q$(s);if(e&&e.parentNode!==document.body)document.body.appendChild(e);}); // fixed-Elemente nicht in transformierten Containern
  load();render();
  document.addEventListener("click",function(ev){var t=ev.target;
    var gi=t.closest&&t.closest(".kjo-vl-gal img,img.kjo-vl-pimg");if(gi){ev.preventDefault();lbOpen(gi);return;}
    var a=t.closest&&t.closest(".kjo-vl-add");if(a){ev.preventDefault();add(a.getAttribute("data-k"));return;}
    var o=t.closest&&t.closest(".kjo-vl-open");if(o){var d=document.getElementById(o.getAttribute("aria-controls"));if(d){var x=d.hidden;d.hidden=!x;o.setAttribute("aria-expanded",x?"true":"false");}return;}
    var c=t.closest&&t.closest(".kjo-vl-chip");if(c){all(".kjo-vl-chip").forEach(function(b){b.classList.toggle("on",b===c);});filter();return;}
    if(t.closest&&t.closest(".kjo-vl-bar-btn")){openM();return;}
    if(t.closest&&(t.closest(".kjo-vl-x")||t.closest(".kjo-vl-x2"))){closeM();return;}
    if(t.classList&&t.classList.contains("kjo-vl-modal")){closeM();return;}
    var r=t.closest&&t.closest(".kjo-vl-report");if(r){ev.preventDefault();report(r);}
  });
  document.addEventListener("keydown",function(e){if(LB&&!LB.hidden){if(e.key==="Escape")lbClose();else if(e.key==="ArrowLeft")lbShow(LBI-1);else if(e.key==="ArrowRight")lbShow(LBI+1);return;}if(e.key==="Escape")closeM();});
  var qi=q$(".kjo-vl-q");if(qi)qi.addEventListener("input",filter);
  var f=q$(".kjo-vl-form");if(!f)return;
  all('input[name="uebergabe"]',f).forEach(function(i){i.addEventListener("change",function(){var l=f.uebergabe.value.indexOf("Lieferung")>-1;q$(".kjo-vl-ort",f).hidden=!l;});});
  function iso(dt){return dt.getFullYear()+"-"+("0"+(dt.getMonth()+1)).slice(-2)+"-"+("0"+dt.getDate()).slice(-2);}
  var today=iso(new Date()),fv=f.elements.von,fb=f.elements.bis;
  fv.min=today;fb.min=today;
  fv.addEventListener("change",function(){if(fv.value){fb.min=fv.value;if(!fb.value||fb.value<fv.value)fb.value=fv.value;fb.classList.remove("kjo-vl-bad");}render();avail();});
  fb.addEventListener("change",function(){render();avail();});
  try{var pv=(location.search.match(/[?&]von=(\d{4}-\d{2}-\d{2})/)||[])[1];if(pv&&pv>=today){fv.value=pv;fb.value=pv;fb.min=pv;avail();}}catch(e){}
  function clearBad(el){if(!el.classList.contains("kjo-vl-bad"))return;el.classList.remove("kjo-vl-bad");if(!q$(".kjo-vl-bad",f)){var e=q$(".kjo-vl-err",f);e.hidden=true;e.textContent="";}}
  all("input,textarea",f).forEach(function(el){["input","change","blur"].forEach(function(t){el.addEventListener(t,function(){if((el.value||"").trim())clearBad(el);});});});
  fv.addEventListener("change",function(){clearBad(fb);});
  f.addEventListener("submit",function(ev){ev.preventDefault();var err=q$(".kjo-vl-err",f),b=q$(".kjo-vl-send",f);err.hidden=true;
    all(".kjo-vl-bad",f).forEach(function(e){e.classList.remove("kjo-vl-bad");});
    function fail(m,el){err.textContent=m;err.hidden=false;if(el){el.classList.add("kjo-vl-bad");try{el.scrollIntoView({block:"center",behavior:"smooth"});}catch(e){}setTimeout(function(){try{el.focus({preventScroll:true});}catch(e){el.focus();}},250);}}
    if(!cart.length)return fail("Bitte legt zuerst ein Paket oder Gerät in den Mietkorb.");
    var d={};["von","bis","uebergabe","ort","anlass","gaeste","name","email","telefon","nachricht","website"].forEach(function(n){var el=f.elements[n];d[n]=el?(el.value||"").trim():"";});
    if(!d.von)return fail("Bitte wählt das Datum für Abholung bzw. Beginn – tippt dazu auf das Feld.",fv);
    if(d.von<today)return fail("Der Beginn liegt in der Vergangenheit – bitte wählt ein Datum ab heute.",fv);
    if(!d.bis)return fail("Bitte wählt das Datum für Rückgabe bzw. Ende – tippt dazu auf das Feld.",fb);
    if(d.bis<d.von)return fail("Die Rückgabe liegt vor dem Beginn – bitte prüft die Daten.",fb);
    if(d.uebergabe.indexOf("Lieferung")>-1&&!d.ort)return fail("Bitte gebt den Ort der Feier für die Lieferung an.",f.elements.ort);
    if(!d.name)return fail("Bitte gebt euren Namen an.",f.elements.name);
    if(!d.email)return fail("Bitte gebt eure E-Mail-Adresse an. Tipp: Schlägt der Browser eine Adresse vor, bitte antippen, damit sie übernommen wird.",f.elements.email);
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email))return fail("Bitte gebt eine gültige E-Mail-Adresse an, damit ich euch antworten kann.",f.elements.email);
    d.items=cart;d.ts=V.ts;d.seite=location.href;b.disabled=true;b.textContent="wird gesendet …";
    post("mietanfrage",d).then(function(r){b.disabled=false;b.textContent="Mietanfrage senden";
      if(r.ok&&r.j&&r.j.ok){track();cart=[];save();render();f.hidden=true;q$(".kjo-vl-cart").hidden=true;q$(".kjo-vl-sum").hidden=true;q$(".kjo-vl-ok").hidden=false;}
      else fail((r.j&&r.j.message)||"Das hat leider nicht geklappt. Bitte versucht es noch einmal oder schreibt mir per WhatsApp.");
    }).catch(function(){b.disabled=false;b.textContent="Mietanfrage senden";fail("Keine Verbindung. Bitte versucht es noch einmal oder schreibt mir per WhatsApp.");});
  });
}
function report(b){var id=b.getAttribute("data-id"),k="kjo_anl_"+id,today=new Date().toISOString().slice(0,10),done=false;
  try{done=localStorage.getItem(k)===today;}catch(e){}
  function thanks(){var s=document.createElement("span");s.className="kjo-vl-report";s.style.textDecoration="none";s.textContent="Danke, ich kümmere mich darum!";b.replaceWith(s);}
  if(done){thanks();return;}
  b.disabled=true;post("anleitung-melden",{id:+id,seite:location.href}).then(function(){try{localStorage.setItem(k,today);}catch(e){}thanks();}).catch(function(){b.disabled=false;});}
if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();
})();
KJOJS
);

/* ---------------------------------------------------------------
 * REST: Mietanfrage und Link-Meldung
 * ------------------------------------------------------------- */
function kjo_vl_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 20 );
}
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/mietanfrage',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'kjo_vl_rest_anfrage',
			)
		);
		register_rest_route(
			'kjo/v1',
			'/anleitung-melden',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'kjo_vl_rest_melden',
			)
		);
	}
);
function kjo_vl_err( $m, $code = 400 ) {
	return new WP_REST_Response( array( 'ok' => false, 'message' => $m ), $code );
}
function kjo_vl_rest_anfrage( WP_REST_Request $req ) {
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
		return kjo_vl_err( 'Bitte noch einmal absenden.' );
	}
	$rk = 'kjo_vl_rl_' . kjo_vl_ip_hash();
	$rl = (int) get_transient( $rk );
	if ( $rl >= 5 ) {
		return kjo_vl_err( 'Es wurden gerade sehr viele Anfragen gesendet. Bitte versucht es später noch einmal oder schreibt mir per WhatsApp.', 429 );
	}
	$name  = $s( 'name' );
	$email = sanitize_email( $s( 'email' ) );
	$von   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s( 'von' ) ) ? $s( 'von' ) : '';
	$bis   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s( 'bis' ) ) ? $s( 'bis' ) : '';
	$ueb   = in_array( $s( 'uebergabe' ), array( 'Abholung in Fellheim', 'Lieferung und Abholung', 'Lieferung, Aufbau/Verkabelung, Abbau und Abholung' ), true ) ? $s( 'uebergabe' ) : 'Abholung in Fellheim';
	if ( ! $name || ! is_email( $email ) || ! $von || ! $bis ) {
		return kjo_vl_err( 'Bitte füllt Name, E-Mail und Mietzeitraum aus.' );
	}
	if ( $von < wp_date( 'Y-m-d' ) || $bis < $von ) {
		return kjo_vl_err( 'Bitte prüft den Mietzeitraum: Beginn ab heute, Rückgabe nicht vor dem Beginn.' );
	}
	$lines = array();
	$keys  = array();
	$sp    = 0;
	$sk    = 0;
	$pk    = true;
	$kk    = true;
	$tf = kjo_vl_tarif( $von, $bis );
	foreach ( ( isset( $d['items'] ) && is_array( $d['items'] ) ? array_slice( $d['items'], 0, 60 ) : array() ) as $it ) {
		$k = isset( $it['k'] ) ? (string) $it['k'] : '';
		$q = isset( $it['q'] ) ? max( 0, min( 99, (int) $it['q'] ) ) : 0;
		if ( ! $q || ! preg_match( '/^([gp])(\d+)$/', $k, $m ) ) {
			continue;
		}
		$p = get_post( (int) $m[2] );
		if ( ! $p || 'publish' !== $p->post_status || $p->post_type !== ( 'g' === $m[1] ? 'kjo_geraet' : 'kjo_paket' ) ) {
			continue;
		}
		$pr = kjo_vl_num( kjo_vl_get( $p->ID, 'preis' ) );
		$ka = kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) );
		if ( null === $pr ) {
			$pk = false;
		} else {
			$sp += ( $tf ? $tf['we'] * kjo_vl_we( $p->ID ) + $tf['d'] * $pr : $pr ) * $q;
		}
		if ( null === $ka ) {
			$kk = false;
		} else {
			$sk += $ka * $q;
		}
		$keys[]  = $k;
		$lines[] = $q . ' × ' . $p->post_title . ( null === $pr ? '' : ' (' . kjo_vl_eur( $pr ) . '/Tag)' );
	}
	if ( ! $lines ) {
		return kjo_vl_err( 'Euer Mietkorb ist leer.' );
	}
	set_transient( $rk, $rl + 1, HOUR_IN_SECONDS );
	$fmt  = function ( $x ) {
		$t = DateTime::createFromFormat( 'Y-m-d', $x );
		return $t ? $t->format( 'd.m.Y' ) : $x;
	};
	$text  = "Neue Mietanfrage über dj-kolja-one.de\n\n";
	$text .= "Mietkorb:\n- " . implode( "\n- ", $lines ) . "\n";
	$text .= $pk ? 'Summe laut Website: ca. ' . kjo_vl_eur( $sp ) . ( $tf ? ' für ' . kjo_vl_tarif_txt( $tf ) : ' pro Tag' ) . " (ohne Lieferung/Aufbau)\n" : "Summe: nicht alle Preise eingetragen\n";
	$text .= $kk ? 'Kaution laut Website: ' . kjo_vl_eur( $sk ) . "\n" : '';
	$text .= "\nZeitraum: " . $fmt( $von ) . ' bis ' . $fmt( $bis ) . "\n";
	$text .= 'Übergabe: ' . $ueb . "\n";
	$text .= $s( 'ort' ) ? 'Ort: ' . $s( 'ort' ) . "\n" : '';
	$text .= $s( 'anlass' ) ? 'Anlass: ' . $s( 'anlass' ) . "\n" : '';
	$text .= $s( 'gaeste' ) ? 'Gäste: ' . $s( 'gaeste' ) . "\n" : '';
	$text .= "\nName: " . $name . "\nE-Mail: " . $email . "\n";
	$text .= $s( 'telefon' ) ? 'Telefon: ' . $s( 'telefon' ) . "\n" : '';
	$text .= $s( 'nachricht', true ) ? "\nNachricht:\n" . $s( 'nachricht', true ) . "\n" : '';
	$pid   = wp_insert_post(
		array(
			'post_type'   => 'kjo_mietanfrage',
			'post_status' => 'private',
			'post_title'  => $fmt( $von ) . ' – ' . $name . ' (' . count( $lines ) . ' Positionen)',
		)
	);
	if ( $pid && ! is_wp_error( $pid ) ) {
		update_post_meta( $pid, '_kjo_text', $text );
		update_post_meta( $pid, '_kjo_von', $von ); // für „Als vorreserviert eintragen“ (Kalender)
		update_post_meta( $pid, '_kjo_bis', $bis );
		update_post_meta( $pid, '_kjo_name', $name );
		update_post_meta( $pid, '_kjo_items', $keys );
	}
	$to   = kjo_vl_mail_to();
	$hdrs = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $name ) . ' <' . $email . '>' );
	$ok   = wp_mail( $to, 'Mietanfrage: ' . $fmt( $von ) . ' – ' . $name, $text . ( $pid ? "\nIm Admin: " . admin_url( 'post.php?post=' . (int) $pid . '&action=edit' ) . "\n" : '' ), $hdrs );
	$conf = 'Hallo ' . $name . ",\n\ndanke für eure Mietanfrage! Ich prüfe die Verfügbarkeit und melde mich innerhalb von 24 Stunden mit Angebot und Mietbedingungen.\n\nEure Anfrage:\n- " . implode( "\n- ", $lines ) . "\nZeitraum: " . $fmt( $von ) . ' bis ' . $fmt( $bis ) . "\nÜbergabe: " . $ueb . "\n\nViele Grüße\nKolja – DJ KOLJA ONE\n" . $to . "\nhttps://dj-kolja-one.de\n";
	wp_mail( $email, 'Eure Mietanfrage bei DJ KOLJA ONE', $conf, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: DJ KOLJA ONE <' . $to . '>' ) );
	if ( ! $ok && ! $pid ) {
		return kjo_vl_err( 'Die Anfrage konnte nicht gesendet werden. Bitte schreibt mir per WhatsApp oder ruft an.', 500 );
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
function kjo_vl_rest_melden( WP_REST_Request $req ) {
	$id = (int) $req->get_param( 'id' );
	$p  = $id ? get_post( $id ) : null;
	if ( ! $p || 'kjo_geraet' !== $p->post_type || 'publish' !== $p->post_status ) {
		return kjo_vl_err( 'Unbekanntes Gerät.', 404 );
	}
	$tk = 'kjo_vl_anl_' . $id;
	if ( get_transient( $tk ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 ); // Höchstens eine E-Mail pro Gerät und Stunde.
	}
	set_transient( $tk, 1, HOUR_IN_SECONDS );
	update_post_meta( $id, '_kjo_anl_gemeldet', time() );
	$seite = esc_url_raw( (string) $req->get_param( 'seite' ) );
	$home  = home_url( '/' );
	if ( 0 !== strpos( $seite, $home ) ) {
		$seite = $home;
	}
	$text = 'Ein Besucher hat den Link zur Bedienungsanleitung als fehlerhaft gemeldet.' . "\n\n"
		. 'Gerät: ' . $p->post_title . "\n"
		. 'Gemeldeter Link: ' . kjo_vl_get( $id, 'anleitung' ) . "\n"
		. 'Gemeldet auf: ' . $seite . "\n"
		. 'Zeit: ' . wp_date( 'd.m.Y, H:i' ) . " Uhr\n\n"
		. 'Gerät in der Verwaltung öffnen: ' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . "\n"
		. 'Nach dem Speichern verschwindet der Hinweis „Link gemeldet“.' . "\n";
	wp_mail( kjo_vl_mail_to(), 'Defekter Anleitungs-Link: ' . $p->post_title, $text, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
