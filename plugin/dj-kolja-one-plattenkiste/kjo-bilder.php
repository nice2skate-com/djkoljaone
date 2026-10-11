<?php
/**
 * Bildoptimierung (seit 1.30.0) – ohne externe Dienste und ohne Credits.
 *
 * - Neue Uploads: Zwischengrößen werden als WebP gespeichert (wenn der Server WebP kann), zusätzlich die Größe „kjo_web“ (max. 1600 px).
 * - Website: Plugin-Bilder (KJO_MEDIA, Verleih) nutzen die passende Zwischengröße statt der Originaldatei.
 * - Medien → Bilder optimieren: erzeugt die Größen für bereits hochgeladene Bilder nach (Originale bleiben erhalten).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_IMG_MAX = 1600; // längste Seite der ausgelieferten Fotos in px

add_action(
	'after_setup_theme',
	function () {
		add_image_size( 'kjo_web', KJO_IMG_MAX, KJO_IMG_MAX, false );
	}
);

/** Kann der Server WebP schreiben? (Ergebnis wird einen Tag gemerkt.) */
function kjo_img_webp_ok() {
	static $ok = null;
	if ( null !== $ok ) {
		return $ok;
	}
	$ok = false; // Schutz gegen Rekursion über den Ausgabeformat-Filter.
	$c  = get_transient( 'kjo_img_webp' );
	if ( false === $c ) {
		$c = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? 'ja' : 'nein';
		set_transient( 'kjo_img_webp', $c, DAY_IN_SECONDS );
	}
	$ok = 'ja' === $c;
	return $ok;
}
add_filter(
	'image_editor_output_format',
	function ( $f ) {
		if ( kjo_img_webp_ok() ) {
			$f['image/jpeg'] = 'image/webp';
			$f['image/png']  = 'image/webp';
		}
		return $f;
	}
);

/** Beste Größe für die Website: eine Zwischengröße zwischen 1000 und 1600 px (WebP bevorzugt), sonst das Original. */
function kjo_img_web_size( $id ) {
	$meta = wp_get_attachment_metadata( $id );
	if ( ! is_array( $meta ) || empty( $meta['width'] ) ) {
		return 'full';
	}
	$long = max( (int) $meta['width'], (int) $meta['height'] );
	$best = '';
	$top  = 0;
	foreach ( (array) ( isset( $meta['sizes'] ) ? $meta['sizes'] : array() ) as $n => $s ) {
		$l = max( (int) $s['width'], (int) $s['height'] );
		if ( $l > KJO_IMG_MAX || $l < 1000 ) {
			continue;
		}
		$score = $l + ( isset( $s['mime-type'] ) && 'image/webp' === $s['mime-type'] ? 10000 : 0 );
		if ( $score > $top ) {
			$top  = $score;
			$best = $n;
		}
	}
	if ( '' === $best || ( $long <= KJO_IMG_MAX && 'image/webp' === get_post_mime_type( $id ) ) ) {
		return 'full';
	}
	return $best;
}
function kjo_img_web_url( $id ) {
	$u = wp_get_attachment_image_url( $id, kjo_img_web_size( $id ) );
	return $u ? $u : (string) wp_get_attachment_url( $id );
}
/** Pfad und Größe der ausgelieferten Datei (für die Übersicht). */
function kjo_img_web_info( $id ) {
	$size = kjo_img_web_size( $id );
	$file = get_attached_file( $id );
	$meta = wp_get_attachment_metadata( $id );
	if ( 'full' !== $size && ! empty( $meta['sizes'][ $size ]['file'] ) ) {
		$file = trailingslashit( dirname( $file ) ) . $meta['sizes'][ $size ]['file'];
		$w    = $meta['sizes'][ $size ]['width'];
		$h    = $meta['sizes'][ $size ]['height'];
	} else {
		$w = isset( $meta['width'] ) ? $meta['width'] : 0;
		$h = isset( $meta['height'] ) ? $meta['height'] : 0;
	}
	return array(
		'bytes' => $file && file_exists( $file ) ? (int) filesize( $file ) : 0,
		'w'     => (int) $w,
		'h'     => (int) $h,
		'name'  => $file ? wp_basename( $file ) : '',
	);
}

/** Größen für ein vorhandenes Bild neu erzeugen. Originaldateien bleiben unangetastet. */
function kjo_img_optimize( $id ) {
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) ) {
		return new WP_Error( 'kjo_img', 'Datei fehlt auf dem Server.' );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_raise_memory_limit( 'image' );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore
	}
	$old  = wp_get_attachment_metadata( $id );
	$meta = wp_generate_attachment_metadata( $id, $file );
	if ( empty( $meta['sizes'] ) && empty( $meta['width'] ) ) {
		return new WP_Error( 'kjo_img', 'Konnte nicht verarbeitet werden – bitte verkleinert neu hochladen.' );
	}
	if ( is_array( $old ) ) {
		foreach ( array( 'original_image', 'image_meta' ) as $k ) {
			if ( ! empty( $old[ $k ] ) && empty( $meta[ $k ] ) ) {
				$meta[ $k ] = $old[ $k ];
			}
		}
	}
	wp_update_attachment_metadata( $id, $meta );
	update_post_meta( $id, '_kjo_img_opt', time() );
	if ( function_exists( 'kjo_media_flush' ) ) {
		kjo_media_flush();
	}
	return kjo_img_web_info( $id );
}

/* ---------------------------------------------------------------
 * Medien → Bilder optimieren
 * ------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_media_page( 'Bilder optimieren', 'Bilder optimieren', 'upload_files', 'kjo-bilder', 'kjo_img_admin_page' );
	}
);
function kjo_img_kb( $b ) {
	return $b >= 1048576 ? number_format_i18n( $b / 1048576, 1 ) . ' MB' : number_format_i18n( $b / 1024 ) . ' KB';
}
function kjo_img_admin_page() {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ),
			'posts_per_page' => 2000,
			'fields'         => 'ids',
		)
	);
	$rows = array();
	$sum  = 0;
	foreach ( $ids as $id ) {
		$i      = kjo_img_web_info( $id );
		$sum   += $i['bytes'];
		$rows[] = array( $id, $i );
	}
	usort(
		$rows,
		function ( $a, $b ) {
			return $b[1]['bytes'] - $a[1]['bytes'];
		}
	);
	echo '<div class="wrap"><h1>Bilder optimieren</h1>';
	echo '<p>Erzeugt für vorhandene Bilder kleinere Web-Versionen (längste Seite ' . (int) KJO_IMG_MAX . ' px' . ( kjo_img_webp_ok() ? ', Format WebP' : '' ) . '). Die Website zeigt danach automatisch die kleine Version. <strong>Originale bleiben erhalten</strong>, es werden keine Credits oder externen Dienste genutzt.</p>';
	if ( ! kjo_img_webp_ok() ) {
		echo '<div class="notice notice-warning inline"><p>Der Server kann kein WebP schreiben – es werden verkleinerte JPG/PNG-Versionen erzeugt.</p></div>';
	}
	echo '<p>Neue Uploads werden ab jetzt automatisch so verarbeitet. Tipp: Fotos vor dem Hochladen nicht größer als ca. 2500 px machen – sehr große Handyfotos (über 8000 px) kann der Server teils nicht verarbeiten.</p>';
	echo '<p><button type="button" class="button button-primary" id="kjo-img-all">Alle ' . count( $rows ) . ' Bilder optimieren</button> <span id="kjo-img-st"></span></p>';
	echo '<p>Ausgeliefert insgesamt: <strong id="kjo-img-sum">' . esc_html( kjo_img_kb( $sum ) ) . '</strong></p>';
	echo '<table class="widefat striped"><thead><tr><th>Bild</th><th>Ausgelieferte Datei</th><th>Maße</th><th>Größe</th><th>Status</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		list( $id, $i ) = $r;
		$opt = get_post_meta( $id, '_kjo_img_opt', true );
		echo '<tr data-id="' . (int) $id . '" data-b="' . (int) $i['bytes'] . '"><td>' . wp_get_attachment_image( $id, array( 48, 48 ) ) . ' ' . esc_html( get_the_title( $id ) ) . '</td><td class="n">' . esc_html( $i['name'] ) . '</td><td class="d">' . (int) $i['w'] . ' × ' . (int) $i['h'] . '</td><td class="b">' . esc_html( kjo_img_kb( $i['bytes'] ) ) . '</td><td class="s">' . ( $opt ? 'optimiert' : '–' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
	$nonce = wp_create_nonce( 'kjo_img' );
	?>
<script>
(function(){var b=document.getElementById("kjo-img-all"),st=document.getElementById("kjo-img-st"),sum=document.getElementById("kjo-img-sum");if(!b)return;
function kb(x){return x>=1048576?(x/1048576).toFixed(1).replace(".",",")+" MB":Math.round(x/1024)+" KB";}
b.addEventListener("click",function(){b.disabled=true;var rows=[].slice.call(document.querySelectorAll("tr[data-id]")),i=0,ok=0,err=0;
function next(){if(i>=rows.length){st.textContent="Fertig: "+ok+" optimiert"+(err?", "+err+" mit Fehler":"")+".";b.disabled=false;return;}
var tr=rows[i++];tr.querySelector(".s").textContent="läuft …";st.textContent=i+" / "+rows.length;
var fd=new FormData();fd.append("action","kjo_img_opt");fd.append("id",tr.getAttribute("data-id"));fd.append("_ajax_nonce","<?php echo esc_js( $nonce ); ?>");
fetch(ajaxurl,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json();}).then(function(j){
if(j&&j.success){var d=j.data,old=+tr.getAttribute("data-b");tr.querySelector(".n").textContent=d.name;tr.querySelector(".d").textContent=d.w+" × "+d.h;tr.querySelector(".b").textContent=kb(old)+" → "+kb(d.bytes);tr.querySelector(".s").textContent="optimiert";tr.setAttribute("data-b",d.bytes);ok++;}
else{tr.querySelector(".s").textContent="Fehler: "+(j&&j.data?j.data:"unbekannt");err++;}
}).catch(function(){tr.querySelector(".s").textContent="Fehler: Server hat abgebrochen (Bild zu groß?) – bitte verkleinert neu hochladen";err++;}).then(function(){
var t=0;rows.forEach(function(r){t+=+r.getAttribute("data-b");});sum.textContent=kb(t);next();});}
next();});})();
</script>
	<?php
}
add_action(
	'wp_ajax_kjo_img_opt',
	function () {
		check_ajax_referer( 'kjo_img' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id || ! current_user_can( 'upload_files' ) || ! wp_attachment_is_image( $id ) ) {
			wp_send_json_error( 'Kein Bild.' );
		}
		$r = kjo_img_optimize( $id );
		if ( is_wp_error( $r ) ) {
			wp_send_json_error( $r->get_error_message() );
		}
		wp_send_json_success( $r );
	}
);
