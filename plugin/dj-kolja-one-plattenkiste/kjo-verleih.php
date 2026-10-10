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
			'kaution'  => array( 'Kaution in € (leer = „Kaution laut Angebot“)', 'price', 'z. B. 200' ),
			'bild'     => array( 'Bildname in der Mediathek', 'bild', '' ),
		);
	}
	return array(
		'kat'          => array( 'Kategorie', 'kat', '' ),
		'kurz'         => array( 'Kurzbeschreibung (1 Satz)', 'text', '' ),
		'beschr'       => array( 'Beschreibung', 'textarea', '' ),
		'mietbar'      => array( 'Im Verleih anbieten', 'check', '' ),
		'bestand'      => array( 'Bestand (Stück)', 'number', '' ),
		'preis'        => array( 'Mietpreis pro Tag in € (leer = „auf Anfrage“)', 'price', 'z. B. 25' ),
		'kaution'      => array( 'Kaution in € (leer = „laut Angebot“)', 'price', '' ),
		'gaeste'       => array( 'Geeignet für', 'text', 'z. B. bis ca. 50 Gäste (als Paar)' ),
		'masse'        => array( 'Maße (B × H × T)', 'text', 'z. B. 241 × 330 × 286 mm' ),
		'gewicht'      => array( 'Gewicht', 'text', 'z. B. 7,1 kg' ),
		'volumen'      => array( 'Transportvolumen', 'text', 'z. B. 22,75 l' ),
		'leistung'     => array( 'Leistung / Pegel', 'text', '' ),
		'anschluesse'  => array( 'Anschlüsse', 'text', '' ),
		'strom'        => array( 'Stromversorgung', 'text', 'z. B. 230 V Schuko / Akku' ),
		'lieferumfang' => array( 'Lieferumfang', 'textarea', '' ),
		'wetter'       => array( 'Wetterfestigkeit (wird im Browser angezeigt)', 'wetter', '' ),
		'wetter_ip'    => array( 'Schutzart', 'text', 'z. B. IP65' ),
		'anleitung'    => array( 'Bedienungsanleitung (Deutsch): Link oder PDF aus der Mediathek', 'anleitung', 'https://…' ),
		'bild'         => array( 'Bildname in der Mediathek', 'bild', '' ),
	);
}
function kjo_vl_intern_fields() {
	return array(
		'seriennr'   => 'Seriennummer(n)',
		'kaufdatum'  => 'Kaufdatum / Rechnung',
		'wert'       => 'Wiederbeschaffungswert (€)',
		'pruefdatum' => 'Letzte Prüfung (DGUV V3)',
		'lagerort'   => 'Lagerort',
		'zustand'    => 'Zustand',
		'notiz'      => 'Interne Notiz',
	);
}
function kjo_vl_get( $id, $k ) {
	return (string) get_post_meta( $id, '_kjo_' . $k, true );
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
		array( 'bose-s1-pro', 'Bose S1 Pro Aktivlautsprecher', 'lautsprecher', 2, 'Kompakter Aktivlautsprecher mit Bluetooth: klarer Klang für Sprache und Musik, dezent im Raum.', array( 'masse' => '241 × 330 × 286 mm', 'gewicht' => '7,1 kg', 'volumen' => '22,75 l', 'anschluesse' => 'Bluetooth, Kabel-Eingänge', 'gaeste' => 'bis ca. 50 Gäste (als Paar)', 'anleitung' => $bose ) ),
		array( 'bose-subwoofer', 'Bose Subwoofer', 'subwoofer', 1, 'Bassergänzung für das Bose-System – mehr Druck auf der Tanzfläche.', array( 'gaeste' => 'bis ca. 100 Gäste (mit 2 × Bose S1 Pro)' ) ),
		array( 'pronomic-c-215-ma', 'Pronomic C-215 MA 15"-Aktivbox', 'lautsprecher', 2, '2-Wege-Aktivbox mit 15"-Tieftöner und Bluetooth – kraftvoller Sound für große Säle und Festzelte.', array( 'masse' => '450 × 695 × 390 mm', 'gewicht' => '25,2 kg', 'volumen' => 'ca. 122 l', 'leistung' => '400 W + 100 W RMS, max. 123 dB', 'anschluesse' => '2 × XLR/Klinke-Combo, Bluetooth', 'gaeste' => 'bis ca. 250 Gäste (als Paar mit Subwoofern)' ) ),
		array( 'pronomic-c-118sa', 'Pronomic C-118SA 18"-Aktiv-Subwoofer', 'subwoofer', 2, '18"-Aktiv-Subwoofer für druckvollen Bass, den man spürt.', array( 'leistung' => '800 W RMS, 30–200 Hz, max. 126 dB', 'anschluesse' => '2 × Combo-Eingang, 2 × XLR-Ausgang, M20-Flansch für Distanzstange' ) ),
		array( 'pioneer-ddj-flx10', 'Pioneer DJ DDJ-FLX10', 'dj', 1, 'Vierkanaliger DJ-Controller für rekordbox und Serato – den Laptop bringt ihr mit.', array( 'masse' => '716 × 73,4 × 400,3 mm', 'gewicht' => '6,7 kg', 'volumen' => '21,04 l', 'anschluesse' => 'USB zum Laptop (rekordbox oder Serato)', 'lieferumfang' => "Netzteil\nUSB-Kabel\nAudiokabel", 'anleitung' => 'https://www.pioneerdj.com/de/product/dj-controllers/ddj-flx10/' ) ),
		array( 'dj-pult', 'DJ-Pult, beleuchtet', 'buehne', 1, 'Beleuchtetes DJ-Pult mit zwei Zusatzplatten – eine saubere Bühne für Controller und Laptop.', array( 'lieferumfang' => '2 Zusatzplatten' ) ),
		array( 'funkmikrofon-set', 'Funkmikrofon-Set', 'mikrofone', 1, 'Für Reden, Spiele und Ansagen – ohne Kabelsalat.', array() ),
		array( 'stairville-tri-flat-par', 'Stairville Tri Flat PAR Profile 5 × 3 W', 'licht', 4, 'Flacher LED-Scheinwerfer für farbiges Party- und Flächenlicht, z. B. an der Traverse.', array( 'masse' => '165 × 165 × 105 mm (mit Bügel 255 × 235 × 105 mm)', 'gewicht' => '2,04 kg', 'leistung' => '15 W, RGB-LED', 'anschluesse' => 'DMX, Musiksteuerung, Automatik' ) ),
		array( 'moving-head', 'Moving Head', 'licht', 2, 'Bewegte Lichtstrahlen für echte Club-Momente.', array() ),
		array( 'kls-lightbar', 'KLS-Lightbar mit Lichtstativ', 'licht', 1, 'Partylicht auf eigenem Stativ, das die ganze Tanzfläche ausleuchtet.', array( 'lieferumfang' => 'Lichtstativ' ) ),
		array( 'algam-akku-par', 'ALGAM Akku-Parlight', 'licht', 8, 'Kabelloses Ambientelicht für Wände, Säulen, Garten und Terrasse.', array( 'lieferumfang' => 'Ladegerät', 'strom' => 'Akku' ) ),
		array( 'uv-strahler', 'UV-Strahler', 'effekte', 1, 'Schwarzlicht-Effekt für leuchtende Outfits und Deko.', array() ),
		array( 'wash-scheinwerfer', 'Wash-/Color-Scheinwerfer', 'licht', 1, 'Zusätzliche farbige Flächenbeleuchtung.', array() ),
		array( 'nebelmaschine', 'Nebelmaschine', 'effekte', 1, 'Macht Lichtstrahlen sichtbar. Bitte vorher mit der Location wegen der Rauchmelder abstimmen.', array() ),
		array( 'led-laufschrift', 'LED-Laufschrift (ca. 70 cm)', 'effekte', 1, 'Eure Namen, „Happy Birthday“ oder euer Hashtag in Leuchtschrift.', array() ),
		array( 'traverse-3m', 'Traversen-Set 3 m', 'buehne', 1, 'Lichttraverse mit zwei Ständern – die Bühne für Scheinwerfer und Laufschrift.', array( 'lieferumfang' => "2 Traversenständer\n2 Adapter Traverse/Ständer\n2 Traversenstücke\nVerbinder, Schrauben und Sicherungen" ) ),
		array( 'lautsprecherstativ', 'Lautsprecherstativ', 'buehne', 2, 'Stabiles Stativ für Aktivlautsprecher.', array() ),
	);
	$p = array(
		array( 'paket-s', 'Paket S – „Sound kompakt“', 'Für bis zu ca. 50 Gäste', 'bspw. Geburtstag, Gartenparty, Sektempfang, etc.', "2× Bose S1 Pro Aktivlautsprecher (mit Bluetooth)\n2× Lautsprecherstative\nAudiokabel für Handy oder Laptop\nOptional: Funkmikrofon-Set", '' ),
		array( 'paket-m', 'Paket M – „Party“', 'Für bis zu ca. 100 Gäste', 'bspw. runder Geburtstag, Vereinsfeier, Firmenfeier, etc.', "Alles aus Paket S\n1× Bose Subwoofer\nFunkmikrofon-Set\nKLS-Lightbar mit Lichtstativ\n8× Akku-Parlights mit Ladegerät", '' ),
		array( 'paket-l', 'Paket L – „Komplett-Show“', 'Für bis zu ca. 250 Gäste', 'bspw. Hochzeit, Abiball, Sommerfest, etc. Die komplette Bühne aus meinem Beispiel-Lichtplan.', "Pronomic PA: 2× C-215 MA (15\", mit Bluetooth) und 2× C-118SA (18\"-Subwoofer), inklusive Kabelsatz\nPioneer DDJ-FLX10 + beleuchtetes DJ-Pult mit Zusatzplatten\n3-m-Traverse mit Ständern und 4 LED-PARs\n2 Moving Heads, KLS-Lightbar, 8 Akku-Parlights\nLED-Laufschrift, Nebelmaschine\nFunkmikrofon-Set\nEuren Laptop mit DJ-Software (rekordbox oder Serato) bringt ihr selbst mit.", 'Empfehlung: Nach Verfügbarkeit und gegen Gebühr mit Lieferung, Aufbau/Verkabelung, Abbau und Abholung. Traverse und Moving Heads baue ich für euch sicher auf.' ),
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
			case 'bild':
				$auto = kjo_vl_bild( (object) array( 'ID' => 0, 'post_type' => $post->post_type, 'post_name' => $post->post_name, 'post_title' => $post->post_title ) );
				echo '<input type="text" id="kjo_vl_bild" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '" class="regular-text" placeholder="' . esc_attr( $auto ) . '">';
				$key = $v ? $v : $auto;
				$n   = count( kjo_vl_media( $key ) );
				echo '<p class="description">So müssen die Dateien in der Mediathek heißen: <code>' . esc_html( $key ) . '.jpg</code>, <code>' . esc_html( $key ) . '_1.jpg</code>, <code>' . esc_html( $key ) . '_2.jpg</code> … (Videos: <code>.mp4</code>). Gefunden: <strong>' . (int) $n . '</strong>. Leer lassen = Vorschlag übernehmen.</p>';
				break;
			default:
				$type = 'number' === $f[1] ? 'number' : 'text';
				if ( 'price' === $f[1] ) {
					$v = str_replace( '.', ',', $v ); // 30.5 → 30,5
				}
				echo '<input type="' . esc_attr( $type ) . '" id="kjo_vl_' . esc_attr( $k ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $v ) . '" class="' . ( 'text' === $type ? 'large-text' : 'small-text' ) . '" placeholder="' . esc_attr( $f[2] ) . '"' . ( 'number' === $type ? ' min="0" step="1"' : '' ) . '>';
				if ( 'price' === $f[1] ) {
					echo ' <span class="description">Nur Zahl, z. B. 89 oder 89,50. Leer = Ersatztext ohne Preis.</span>';
				}
		}
		echo '</td></tr>';
	}
	echo '</table>';
	?>
<script>
(function(){var b=document.getElementById("kjo-vl-pdf");if(!b||!window.wp||!wp.media)return;var fr;
b.addEventListener("click",function(e){e.preventDefault();if(!fr){fr=wp.media({title:"Bedienungsanleitung wählen",library:{type:"application/pdf"},button:{text:"Übernehmen"},multiple:false});
fr.on("select",function(){var a=fr.state().get("selection").first().toJSON();document.getElementById("kjo_vl_anleitung").value=a.url;});}fr.open();});})();
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
			} elseif ( 'anleitung' === $f[1] ) {
				$v = esc_url_raw( trim( $v ) );
			} elseif ( 'check' === $f[1] ) {
				$v = '1' === $v ? '1' : '0';
			} elseif ( 'number' === $f[1] ) {
				$v = '' === trim( $v ) ? '' : (string) absint( $v );
			} elseif ( 'price' === $f[1] ) {
				$n = kjo_vl_num( $v );
				$v = null === $n ? '' : rtrim( rtrim( number_format( $n, 2, '.', '' ), '0' ), '.' );
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
			echo '<code>' . esc_html( $key ) . '</code> (' . count( kjo_vl_media( $key ) ) . ')';
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
function kjo_vl_img_html( $key, $alt, $cls ) {
	$m = kjo_vl_media( $key );
	foreach ( $m as $e ) {
		if ( ! empty( $e['img'] ) ) {
			return '<img class="' . esc_attr( $cls ) . '" src="' . esc_url( $e['img'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
		}
	}
	return '<span class="' . esc_attr( $cls ) . ' kjo-vl-ph" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 2a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm0 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></span>';
}

/* [kjo_pakete] */
add_shortcode(
	'kjo_pakete',
	function () {
		$h = '<div class="kjo-vl-pakete">';
		foreach ( kjo_vl_items( 'kjo_paket' ) as $p ) {
			$pr  = kjo_vl_num( kjo_vl_get( $p->ID, 'preis' ) );
			$ka  = kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) );
			$h  .= '<article class="kjo-vl-paket">' . kjo_vl_img_html( kjo_vl_bild( $p ), $p->post_title, 'kjo-vl-pimg' );
			$h  .= '<h3>' . esc_html( $p->post_title ) . '</h3>';
			$gae = trim( kjo_vl_get( $p->ID, 'gaeste' ) . ': ' . kjo_vl_get( $p->ID, 'anlaesse' ), ': ' );
			if ( $gae ) {
				$h .= '<p class="kjo-vl-gaeste">' . esc_html( $gae ) . '</p>';
			}
			$lines = array_filter( array_map( 'trim', explode( "\n", kjo_vl_get( $p->ID, 'inhalt' ) ) ) );
			if ( $lines ) {
				$h .= '<ul>';
				foreach ( $lines as $l ) {
					$h .= '<li>' . esc_html( $l ) . '</li>';
				}
				$h .= '</ul>';
			}
			$h .= '<p class="kjo-vl-preis">' . ( null === $pr ? 'Preis auf Anfrage: ihr bekommt ihn mit dem Angebot' : 'ab <strong>' . esc_html( kjo_vl_eur( $pr ) ) . '</strong> pro Tag' );
			$h .= ' · ' . ( null === $ka ? 'Kaution laut Angebot' : 'Kaution ' . esc_html( kjo_vl_eur( $ka ) ) ) . '</p>';
			if ( kjo_vl_get( $p->ID, 'hinweis' ) ) {
				$h .= '<p class="kjo-vl-hinweis">' . esc_html( kjo_vl_get( $p->ID, 'hinweis' ) ) . '</p>';
			}
			$h .= '<button type="button" class="kjo-vl-add kjo-vl-btn" data-k="p' . (int) $p->ID . '">In den Mietkorb</button></article>';
		}
		$w  = kjo_vl_opt( 'wochenende' );
		$h .= '</div><p class="kjo-vl-wochenende">' . ( $w ? 'Wochenendmiete Freitag bis Montag: ' . esc_html( $w ) : 'Ihr feiert das ganze Wochenende? Fragt nach unserem Wochenendtarif.' ) . '</p>';
		return $h . kjo_vl_assets();
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
			$h   .= '<div class="kjo-vl-row"><button type="button" class="kjo-vl-open" aria-expanded="false" aria-controls="kjo-vl-d' . (int) $id . '">' . kjo_vl_img_html( $key, $g->post_title, 'kjo-vl-thumb' );
			$h   .= '<span class="kjo-vl-main"><span class="kjo-vl-name">' . esc_html( $g->post_title ) . '</span>';
			$h   .= '<span class="kjo-vl-meta">' . esc_html( implode( ' · ', $kurz ? $kurz : array( kjo_vl_get( $id, 'kurz' ) ) ) ) . '</span>';
			$h   .= '<span class="kjo-vl-wet">' . esc_html( $wtxt ) . '</span></span></button>';
			$h   .= '<span class="kjo-vl-price">' . ( null === $pr ? 'auf Anfrage' : esc_html( kjo_vl_eur( $pr ) ) . '<small> / Tag</small>' ) . '</span>';
			$h   .= '<button type="button" class="kjo-vl-add kjo-vl-plus" data-k="g' . (int) $id . '" aria-label="' . esc_attr( $g->post_title . ' in den Mietkorb' ) . '">+</button></div>';
			/* Details */
			$h .= '<div class="kjo-vl-det" id="kjo-vl-d' . (int) $id . '" hidden>';
			$gal = '';
			foreach ( kjo_vl_media( $key ) as $e ) {
				if ( ! empty( $e['vid'] ) ) {
					$gal .= '<video src="' . esc_url( $e['vid'] ) . '" controls preload="none" playsinline' . ( ! empty( $e['img'] ) ? ' poster="' . esc_url( $e['img'] ) . '"' : '' ) . '></video>';
				} elseif ( ! empty( $e['img'] ) ) {
					$gal .= '<img src="' . esc_url( $e['img'] ) . '" alt="' . esc_attr( $g->post_title ) . '" loading="lazy" decoding="async">';
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
				'Maße (B × H × T)' => kjo_vl_get( $id, 'masse' ),
				'Gewicht'          => kjo_vl_get( $id, 'gewicht' ),
				'Transportvolumen' => kjo_vl_get( $id, 'volumen' ),
				'Leistung / Pegel' => kjo_vl_get( $id, 'leistung' ),
				'Anschlüsse'       => kjo_vl_get( $id, 'anschluesse' ),
				'Strom'            => kjo_vl_get( $id, 'strom' ),
				'Wetterfestigkeit' => $wtxt,
				'Lieferumfang'     => str_replace( "\n", ', ', kjo_vl_get( $id, 'lieferumfang' ) ),
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
			array( 'Mitbringen', 'Personalausweis. Mieten ab 18 Jahren.' ),
			array( 'Passt das in mein Auto?', 'Paket S passt in jeden Kleinwagen, für M reicht ein Kombi. Für L braucht ihr einen Transporter, oder ihr bucht nach Verfügbarkeit und gegen Gebühr Lieferung, Aufbau/Verkabelung, Abbau und Abholung.' ),
			array( 'Akku-Scheinwerfer', 'Ich übergebe sie vollgeladen, das Ladegerät ist dabei.' ),
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
		$cat[ 'g' . $g->ID ] = array( 'n' => $g->post_title, 'p' => kjo_vl_num( kjo_vl_get( $g->ID, 'preis' ) ), 'k' => kjo_vl_num( kjo_vl_get( $g->ID, 'kaution' ) ) );
	}
	foreach ( kjo_vl_items( 'kjo_paket' ) as $p ) {
		$cat[ 'p' . $p->ID ] = array( 'n' => $p->post_title, 'p' => kjo_vl_num( kjo_vl_get( $p->ID, 'preis' ) ), 'k' => kjo_vl_num( kjo_vl_get( $p->ID, 'kaution' ) ) );
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
<script>window.KJO_VL={api:<?php echo wp_json_encode( $api ); ?>,cat:<?php echo wp_json_encode( (object) $cat ); ?>,ts:<?php echo (int) time(); ?>};</script>
	<?php
	return (string) ob_get_clean();
}

/* CSS + JS – einmal pro Seite */
function kjo_vl_assets() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return '<style id="kjo-vl-css">' . KJO_VL_CSS . '</style><script id="kjo-vl-js">' . KJO_VL_JS . '</script>';
}

define(
	'KJO_VL_CSS',
	<<<'KJOCSS'
.kjo-vl [hidden],.kjo-vl-modal[hidden],.kjo-vl-modal [hidden],.kjo-vl-bar[hidden]{display:none!important}
.kjo-vl button,.kjo-vl-pakete button,.kjo-vl-bar button,.kjo-vl-modal button{all:unset;box-sizing:border-box;cursor:pointer;-webkit-tap-highlight-color:transparent}
.kjo-vl button:focus-visible,.kjo-vl-pakete button:focus-visible,.kjo-vl-bar button:focus-visible,.kjo-vl-modal button:focus-visible{outline:2px solid #B29D75;outline-offset:2px}
.kjo-vl,.kjo-vl-pakete,.kjo-vl-tips,.kjo-vl-modal{font-family:"Fira Sans",sans-serif;color:#F3F1E9;text-align:left}
.kjo-vl-pakete .kjo-vl-btn,.kjo-vl-modal .kjo-vl-btn{display:inline-block;background:#B29D75;color:#0F0C07;border:0;border-radius:2px;padding:14px 26px;font:500 14px/1 "Fira Sans",sans-serif;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:.2s}
.kjo-vl-pakete .kjo-vl-btn:hover,.kjo-vl-modal .kjo-vl-btn:hover{background:#C4B08A}.kjo-vl-pakete .kjo-vl-btn{align-self:flex-start}
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
.kjo-vl-price{flex:none;color:#B29D75;font-size:15px;white-space:nowrap}.kjo-vl-price small{color:#A39E93}
.kjo-vl .kjo-vl-plus{display:flex;align-items:center;justify-content:center;flex:none;width:44px;height:44px;border-radius:50%;border:1px solid #B29D75;background:transparent;color:#B29D75;font:300 26px/1 "Fira Sans",sans-serif;cursor:pointer}
.kjo-vl .kjo-vl-plus:hover{background:#B29D75;color:#0F0C07}
.kjo-vl-det{padding:4px 0 22px 78px;color:#A39E93;line-height:1.7}
.kjo-vl-det p{margin:0 0 12px}
.kjo-vl-gal{display:flex;gap:10px;overflow-x:auto;margin-bottom:14px}.kjo-vl-gal img,.kjo-vl-gal video{height:180px;width:auto;border:1px solid #B29D75;flex:none;background:#000}
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
function sums(){var p=0,k=0,pk=true,kk=true;cart.forEach(function(e){var c=C[e.k];if(c.p==null)pk=false;else p+=c.p*e.q;if(c.k==null)kk=false;else k+=c.k*e.q;});return{p:pk?p:null,k:kk?k:null};}
function render(){
  var bar=q$(".kjo-vl-bar");if(!bar)return;var n=count(),s=sums();
  bar.hidden=!n;document.body.classList.toggle("kjo-vl-has-bar",!!n);
  q$(".kjo-vl-bar-txt").textContent="🛒 "+n+(n===1?" Artikel":" Artikel")+(s.p!=null?" · ca. "+eur(s.p)+" pro Tag":"")+(s.k!=null?" · Kaution "+eur(s.k):"");
  var ul=q$(".kjo-vl-cart");ul.innerHTML="";
  if(!n){var li=document.createElement("li");li.className="kjo-vl-empty";li.textContent="Euer Mietkorb ist leer.";ul.appendChild(li);}
  cart.forEach(function(e){var c=C[e.k],li=document.createElement("li");
    li.innerHTML='<span class="n"></span><button type="button" data-d="-1" aria-label="weniger">−</button><span class="q"></span><button type="button" data-d="1" aria-label="mehr">+</button><span class="p"></span><button type="button" data-d="x" aria-label="entfernen">×</button>';
    li.querySelector(".n").textContent=c.n;li.querySelector(".q").textContent=e.q;li.querySelector(".p").textContent=c.p==null?"auf Anfrage":eur(c.p*e.q);
    all("button",li).forEach(function(b){b.addEventListener("click",function(){var d=b.getAttribute("data-d");if(d==="x")e.q=0;else e.q+=+d;cart=cart.filter(function(x){return x.q>0;});save();render();});});
    ul.appendChild(li);});
  var sum=q$(".kjo-vl-sum");
  sum.innerHTML=!n?"":(s.p!=null?"Summe: <strong>ca. "+eur(s.p)+" pro Tag</strong>"+(s.k!=null?" · Kaution "+eur(s.k):"")+"<br>Unverbindliche Schätzung, der endgültige Preis kommt mit dem Angebot.":"Den Gesamtpreis bekommt ihr mit dem Angebot, kostenlos und unverbindlich.");
}
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
    var a=t.closest&&t.closest(".kjo-vl-add");if(a){ev.preventDefault();add(a.getAttribute("data-k"));return;}
    var o=t.closest&&t.closest(".kjo-vl-open");if(o){var d=document.getElementById(o.getAttribute("aria-controls"));if(d){var x=d.hidden;d.hidden=!x;o.setAttribute("aria-expanded",x?"true":"false");}return;}
    var c=t.closest&&t.closest(".kjo-vl-chip");if(c){all(".kjo-vl-chip").forEach(function(b){b.classList.toggle("on",b===c);});filter();return;}
    if(t.closest&&t.closest(".kjo-vl-bar-btn")){openM();return;}
    if(t.closest&&(t.closest(".kjo-vl-x")||t.closest(".kjo-vl-x2"))){closeM();return;}
    if(t.classList&&t.classList.contains("kjo-vl-modal")){closeM();return;}
    var r=t.closest&&t.closest(".kjo-vl-report");if(r){ev.preventDefault();report(r);}
  });
  document.addEventListener("keydown",function(e){if(e.key==="Escape")closeM();});
  var qi=q$(".kjo-vl-q");if(qi)qi.addEventListener("input",filter);
  var f=q$(".kjo-vl-form");if(!f)return;
  all('input[name="uebergabe"]',f).forEach(function(i){i.addEventListener("change",function(){var l=f.uebergabe.value.indexOf("Lieferung")>-1;q$(".kjo-vl-ort",f).hidden=!l;});});
  function iso(dt){return dt.getFullYear()+"-"+("0"+(dt.getMonth()+1)).slice(-2)+"-"+("0"+dt.getDate()).slice(-2);}
  var today=iso(new Date()),fv=f.elements.von,fb=f.elements.bis;
  fv.min=today;fb.min=today;
  fv.addEventListener("change",function(){if(fv.value){fb.min=fv.value;if(!fb.value||fb.value<fv.value)fb.value=fv.value;fb.classList.remove("kjo-vl-bad");}});
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
	$sp    = 0;
	$sk    = 0;
	$pk    = true;
	$kk    = true;
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
			$sp += $pr * $q;
		}
		if ( null === $ka ) {
			$kk = false;
		} else {
			$sk += $ka * $q;
		}
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
	$text .= $pk ? 'Summe laut Website: ca. ' . kjo_vl_eur( $sp ) . " pro Tag\n" : "Summe: nicht alle Preise eingetragen\n";
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
