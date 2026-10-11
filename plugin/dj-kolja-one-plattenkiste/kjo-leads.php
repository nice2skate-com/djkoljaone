<?php
/**
 * Lead-System für DJ-Anfragen (seit 1.33.0)
 *
 * - Status je Anfrage (Neu → In Klärung → Angebot in Arbeit → Angebot versendet → Kunde überlegt → Gebucht → Durchgeführt,
 *   abgeschlossen: Abgelehnt Kolja / Abgelehnt Kunde / Keine Rückmeldung / Spam-Test)
 * - Pipeline-Board (DJ-Anfragen → Pipeline) mit Ziehen & Ablegen, Summen je Spalte
 * - Angebotsbetrag, Wiedervorlage (fällige Leads rot), Grund bei Absage, Verlauf mit Notizen
 * - Kalender: „Gebucht“ trägt den Tag fest ein, Absage entfernt eine Vorreservierung (Papierkorb)
 * - Dashboard-Kasten und Zähler am Menü (neue + fällige Leads)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kjo_lead_stati() {
	return array(
		'neu'           => array( 'Neu', '#2271b1', true ),
		'klaerung'      => array( 'In Klärung', '#7b5cc4', true ),
		'angebot'       => array( 'Angebot in Arbeit', '#c08a00', true ),
		'versendet'     => array( 'Angebot versendet', '#d46a00', true ),
		'ueberlegt'     => array( 'Kunde überlegt', '#a8580f', true ),
		'gebucht'       => array( 'Gebucht', '#00a32a', true ),
		'durchgefuehrt' => array( 'Durchgeführt', '#006b1d', false ),
		'abg_kolja'     => array( 'Abgelehnt – Kolja', '#787c82', false ),
		'abg_kunde'     => array( 'Abgelehnt – Kunde', '#787c82', false ),
		'keine'         => array( 'Keine Rückmeldung', '#9a9da2', false ),
		'spam'          => array( 'Spam / Test', '#b4b7bb', false ),
	);
}
function kjo_lead_get( $id ) {
	$s = (string) get_post_meta( $id, '_kjo_lead_status', true );
	return isset( kjo_lead_stati()[ $s ] ) ? $s : 'neu';
}
function kjo_lead_badge( $s ) {
	$st = kjo_lead_stati();
	return '<span class="kjo-lb" style="background:' . esc_attr( $st[ $s ][1] ) . '">' . esc_html( $st[ $s ][0] ) . '</span>';
}
function kjo_lead_log( $id, $text ) {
	$log   = get_post_meta( $id, '_kjo_lead_log', true );
	$log   = is_array( $log ) ? $log : array();
	$u     = wp_get_current_user();
	$log[] = array( time(), $u && $u->exists() ? $u->display_name : 'System', $text );
	update_post_meta( $id, '_kjo_lead_log', array_slice( $log, -200 ) );
}
function kjo_lead_due( $id ) {
	$wv = (string) get_post_meta( $id, '_kjo_lead_wv', true );
	return $wv && $wv <= wp_date( 'Y-m-d' ) && kjo_lead_stati()[ kjo_lead_get( $id ) ][2] && 'gebucht' !== kjo_lead_get( $id );
}

/** Status setzen inkl. Verlauf, Wiedervorlage-Vorschlag und Kalender. */
function kjo_lead_set( $id, $neu ) {
	$st  = kjo_lead_stati();
	$alt = kjo_lead_get( $id );
	if ( ! isset( $st[ $neu ] ) || $neu === $alt ) {
		return;
	}
	update_post_meta( $id, '_kjo_lead_status', $neu );
	kjo_lead_log( $id, 'Status: ' . $st[ $alt ][0] . ' → ' . $st[ $neu ][0] );
	$wv = (string) get_post_meta( $id, '_kjo_lead_wv', true );
	$in = array( 'versendet' => 5, 'ueberlegt' => 7, 'klaerung' => 3 );
	if ( isset( $in[ $neu ] ) && ( ! $wv || $wv <= wp_date( 'Y-m-d' ) ) ) {
		update_post_meta( $id, '_kjo_lead_wv', wp_date( 'Y-m-d', time() + $in[ $neu ] * DAY_IN_SECONDS ) );
	}
	if ( ! $st[ $neu ][2] || 'gebucht' === $neu ) {
		delete_post_meta( $id, '_kjo_lead_wv' );
	}
	if ( ! function_exists( 'kjo_kal_save' ) ) {
		return;
	}
	$tid = (int) get_post_meta( $id, '_kjo_termin', true );
	$von = (string) get_post_meta( $id, '_kjo_von', true );
	if ( 'gebucht' === $neu ) {
		if ( $tid && get_post( $tid ) && 'trash' !== get_post_status( $tid ) ) {
			$t           = kjo_kal_get( $tid );
			$t['status'] = 'fest';
			kjo_kal_save( $t, $tid );
			kjo_lead_log( $id, 'Kalender: Termin auf „fest“ gesetzt' );
		} elseif ( $von ) {
			$tid = kjo_kal_save(
				array(
					'von'    => $von,
					'bis'    => $von,
					'art'    => 'dj',
					'status' => 'fest',
					'equip'  => 'all',
					'notiz'  => trim( get_post_meta( $id, '_kjo_name', true ) . ' · ' . get_post_meta( $id, '_kjo_anlass', true ) . ' · ' . get_post_meta( $id, '_kjo_ort', true ), ' ·' ),
				)
			);
			if ( $tid ) {
				update_post_meta( $id, '_kjo_termin', $tid );
				kjo_lead_log( $id, 'Kalender: DJ-Termin fest eingetragen' );
			}
		}
	} elseif ( in_array( $neu, array( 'abg_kolja', 'abg_kunde', 'keine', 'spam' ), true ) && $tid && get_post( $tid ) && 'vor' === kjo_kal_get( $tid )['status'] ) {
		wp_trash_post( $tid );
		delete_post_meta( $id, '_kjo_termin' );
		kjo_lead_log( $id, 'Kalender: Vorreservierung entfernt (Papierkorb)' );
	}
}
/* Neue Anfragen starten als „Neu“. */
add_action(
	'wp_insert_post',
	function ( $id, $post, $update ) {
		if ( ! $update && 'kjo_anfrage' === $post->post_type ) {
			update_post_meta( $id, '_kjo_lead_status', 'neu' );
		}
	},
	10,
	3
);

/* ---------------------------------------------------------------
 * Bearbeiten: Kasten „Lead“
 * ------------------------------------------------------------- */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_lead', 'Lead', 'kjo_lead_box', 'kjo_anfrage', 'side', 'high' );
		add_meta_box( 'kjo_lead_log', 'Verlauf & Notizen', 'kjo_lead_box_log', 'kjo_anfrage', 'normal', 'default' );
	}
);
function kjo_lead_box( $post ) {
	wp_nonce_field( 'kjo_lead_save', 'kjo_lead_nonce' );
	$s = kjo_lead_get( $post->ID );
	echo '<p><label for="kjo-lead-st"><strong>Status</strong></label><br><select id="kjo-lead-st" name="kjo_lead[status]" style="width:100%">';
	foreach ( kjo_lead_stati() as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $s, $k, false ) . '>' . esc_html( $v[0] ) . '</option>';
	}
	echo '</select></p>';
	$b = (string) get_post_meta( $post->ID, '_kjo_lead_betrag', true );
	echo '<p><label for="kjo-lead-b"><strong>Angebotsbetrag (€)</strong></label><br><input id="kjo-lead-b" name="kjo_lead[betrag]" type="text" inputmode="decimal" value="' . esc_attr( str_replace( '.', ',', $b ) ) . '" style="width:100%" placeholder="z. B. 1290"></p>';
	echo '<p><label for="kjo-lead-wv"><strong>Wiedervorlage</strong></label><br><input id="kjo-lead-wv" name="kjo_lead[wv]" type="date" value="' . esc_attr( (string) get_post_meta( $post->ID, '_kjo_lead_wv', true ) ) . '" style="width:100%"></p>';
	echo '<p><label for="kjo-lead-g"><strong>Grund bei Absage</strong></label><br><input id="kjo-lead-g" name="kjo_lead[grund]" type="text" value="' . esc_attr( (string) get_post_meta( $post->ID, '_kjo_lead_grund', true ) ) . '" style="width:100%" placeholder="z. B. Preis, Termin belegt, anderer DJ"></p>';
	echo '<p class="description">„Angebot versendet“ setzt die Wiedervorlage auf +5 Tage, „Kunde überlegt“ auf +7. „Gebucht“ trägt den Tag fest im Kalender ein.</p>';
}
function kjo_lead_box_log( $post ) {
	$log = get_post_meta( $post->ID, '_kjo_lead_log', true );
	$log = is_array( $log ) ? array_reverse( $log ) : array();
	echo '<p><textarea name="kjo_lead[notiz]" rows="3" class="large-text" placeholder="Notiz hinzufügen, z. B. „Telefoniert, Angebot bis Freitag“"></textarea></p>';
	echo '<ul style="margin:0">';
	foreach ( $log as $l ) {
		echo '<li style="margin:0 0 6px"><span style="color:#646970">' . esc_html( wp_date( 'd.m.Y H:i', $l[0] ) . ' · ' . $l[1] ) . '</span><br>' . nl2br( esc_html( $l[2] ) ) . '</li>';
	}
	echo '<li style="color:#646970">' . esc_html( get_the_date( 'd.m.Y H:i', $post ) ) . ' · Anfrage eingegangen</li></ul>';
}
add_action(
	'save_post_kjo_anfrage',
	function ( $id ) {
		if ( ! isset( $_POST['kjo_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_lead_nonce'] ) ), 'kjo_lead_save' ) || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ) {
			return;
		}
		$d = isset( $_POST['kjo_lead'] ) && is_array( $_POST['kjo_lead'] ) ? wp_unslash( $_POST['kjo_lead'] ) : array(); // phpcs:ignore
		$b = isset( $d['betrag'] ) && function_exists( 'kjo_vl_num' ) ? kjo_vl_num( $d['betrag'] ) : null;
		$alt_b = (string) get_post_meta( $id, '_kjo_lead_betrag', true );
		if ( null === $b ) {
			delete_post_meta( $id, '_kjo_lead_betrag' );
		} else {
			$b = rtrim( rtrim( number_format( $b, 2, '.', '' ), '0' ), '.' );
			update_post_meta( $id, '_kjo_lead_betrag', $b );
			if ( $b !== $alt_b ) {
				kjo_lead_log( $id, 'Angebotsbetrag: ' . kjo_vl_eur( (float) $b ) );
			}
		}
		$g = isset( $d['grund'] ) ? sanitize_text_field( $d['grund'] ) : '';
		if ( $g !== (string) get_post_meta( $id, '_kjo_lead_grund', true ) ) {
			update_post_meta( $id, '_kjo_lead_grund', $g );
			if ( $g ) {
				kjo_lead_log( $id, 'Grund: ' . $g );
			}
		}
		$wv = isset( $d['wv'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['wv'] ) ? $d['wv'] : '';
		if ( $wv ) {
			update_post_meta( $id, '_kjo_lead_wv', $wv );
		} else {
			delete_post_meta( $id, '_kjo_lead_wv' );
		}
		if ( ! empty( $d['notiz'] ) ) {
			kjo_lead_log( $id, sanitize_textarea_field( $d['notiz'] ) );
		}
		if ( isset( $d['status'] ) ) {
			kjo_lead_set( $id, sanitize_key( $d['status'] ) ); // nach der Wiedervorlage, damit der Vorschlag greift
		}
	},
	20
);

/* ---------------------------------------------------------------
 * Liste: Spalten, Filter nach Status, Zähler im Menü
 * ------------------------------------------------------------- */
add_filter(
	'manage_kjo_anfrage_posts_columns',
	function ( $c ) {
		$n = array();
		foreach ( $c as $k => $v ) {
			$n[ $k ] = $v;
			if ( 'title' === $k ) {
				$n['kjo_lead_st'] = 'Status';
				$n['kjo_lead_wv'] = 'Wiedervorlage';
				$n['kjo_lead_b']  = 'Angebot';
			}
		}
		return $n;
	},
	20
);
add_action(
	'manage_kjo_anfrage_posts_custom_column',
	function ( $col, $id ) {
		if ( 'kjo_lead_st' === $col ) {
			echo kjo_lead_badge( kjo_lead_get( $id ) ); // phpcs:ignore
		} elseif ( 'kjo_lead_wv' === $col ) {
			$wv = (string) get_post_meta( $id, '_kjo_lead_wv', true );
			echo $wv ? '<span' . ( kjo_lead_due( $id ) ? ' style="color:#d63638;font-weight:600"' : '' ) . '>' . esc_html( wp_date( 'd.m.Y', strtotime( $wv ) ) ) . '</span>' : '–';
		} elseif ( 'kjo_lead_b' === $col ) {
			$b = (string) get_post_meta( $id, '_kjo_lead_betrag', true );
			echo $b ? esc_html( kjo_vl_eur( (float) $b ) ) : '–';
		}
	},
	10,
	2
);
add_filter(
	'views_edit-kjo_anfrage',
	function ( $v ) {
		$cur = isset( $_GET['kjo_lead'] ) ? sanitize_key( $_GET['kjo_lead'] ) : ''; // phpcs:ignore
		$cnt = kjo_lead_counts();
		foreach ( kjo_lead_stati() as $k => $s ) {
			if ( empty( $cnt[ $k ] ) ) {
				continue;
			}
			$v[ 'kjo_' . $k ] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=kjo_anfrage&kjo_lead=' . $k ) ) . '"' . ( $cur === $k ? ' class="current"' : '' ) . '>' . esc_html( $s[0] ) . ' <span class="count">(' . (int) $cnt[ $k ] . ')</span></a>';
		}
		return $v;
	}
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'kjo_anfrage' !== $q->get( 'post_type' ) || empty( $_GET['kjo_lead'] ) ) { // phpcs:ignore
			return;
		}
		$k = sanitize_key( $_GET['kjo_lead'] ); // phpcs:ignore
		$q->set(
			'meta_query',
			'neu' === $k
				? array( 'relation' => 'OR', array( 'key' => '_kjo_lead_status', 'value' => 'neu' ), array( 'key' => '_kjo_lead_status', 'compare' => 'NOT EXISTS' ) )
				: array( array( 'key' => '_kjo_lead_status', 'value' => $k ) )
		);
	}
);
function kjo_lead_all() {
	static $all = null;
	if ( null === $all ) {
		$all = get_posts( array( 'post_type' => 'kjo_anfrage', 'post_status' => array( 'private', 'publish' ), 'posts_per_page' => 1000, 'orderby' => 'date', 'order' => 'DESC' ) );
	}
	return $all;
}
function kjo_lead_counts() {
	$c = array();
	foreach ( kjo_lead_all() as $p ) {
		$s       = kjo_lead_get( $p->ID );
		$c[ $s ] = ( isset( $c[ $s ] ) ? $c[ $s ] : 0 ) + 1;
	}
	return $c;
}
function kjo_lead_attention() {
	$n = 0;
	foreach ( kjo_lead_all() as $p ) {
		if ( 'neu' === kjo_lead_get( $p->ID ) || kjo_lead_due( $p->ID ) ) {
			++$n;
		}
	}
	return $n;
}
add_action(
	'admin_menu',
	function () {
		global $menu;
		add_submenu_page( 'edit.php?post_type=kjo_anfrage', 'Pipeline', 'Pipeline', 'edit_pages', 'kjo-pipeline', 'kjo_lead_board' );
		$n = kjo_lead_attention();
		if ( $n && is_array( $menu ) ) {
			foreach ( $menu as $i => $m ) {
				if ( isset( $m[2] ) && 'edit.php?post_type=kjo_anfrage' === $m[2] ) {
					$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>'; // phpcs:ignore
				}
			}
		}
	},
	30
);
add_action(
	'admin_head',
	function () {
		echo '<style>.kjo-lb{display:inline-block;padding:2px 9px;border-radius:10px;color:#fff;font-size:12px;line-height:18px;white-space:nowrap}</style>';
	}
);

/* ---------------------------------------------------------------
 * Pipeline-Board
 * ------------------------------------------------------------- */
function kjo_lead_board() {
	$st  = kjo_lead_stati();
	$by  = array();
	foreach ( kjo_lead_all() as $p ) {
		$by[ kjo_lead_get( $p->ID ) ][] = $p;
	}
	$card = function ( $p ) {
		$id  = $p->ID;
		$b   = (string) get_post_meta( $id, '_kjo_lead_betrag', true );
		$wv  = (string) get_post_meta( $id, '_kjo_lead_wv', true );
		$von = (string) get_post_meta( $id, '_kjo_von', true );
		$h   = '<div class="kjo-pc' . ( kjo_lead_due( $id ) ? ' due' : '' ) . '" draggable="true" data-id="' . (int) $id . '" data-b="' . esc_attr( $b ? $b : 0 ) . '">';
		$h  .= '<a href="' . esc_url( get_edit_post_link( $id ) ) . '"><strong>' . esc_html( get_post_meta( $id, '_kjo_name', true ) ? get_post_meta( $id, '_kjo_name', true ) : $p->post_title ) . '</strong></a>';
		$h  .= '<div>' . esc_html( trim( get_post_meta( $id, '_kjo_anlass', true ) . ( $von ? ' · ' . wp_date( 'd.m.Y', strtotime( $von ) ) : ' · Datum offen' ), ' ·' ) ) . '</div>';
		$h  .= '<div class="m">' . esc_html( (string) get_post_meta( $id, '_kjo_ort', true ) ) . ( $b ? ' · <b>' . esc_html( kjo_vl_eur( (float) $b ) ) . '</b>' : '' ) . '</div>';
		$h  .= $wv ? '<div class="wv">⏰ ' . esc_html( wp_date( 'd.m.', strtotime( $wv ) ) ) . '</div>' : '';
		$h  .= '<select class="kjo-pc-s" aria-label="Status ändern">';
		foreach ( kjo_lead_stati() as $k => $v ) {
			$h .= '<option value="' . esc_attr( $k ) . '"' . selected( kjo_lead_get( $id ), $k, false ) . '>' . esc_html( $v[0] ) . '</option>';
		}
		return $h . '</select></div>';
	};
	echo '<div class="wrap kjo-pl"><h1>Pipeline – DJ-Anfragen</h1><p>Karten zwischen den Spalten ziehen (oder unten auf der Karte den Status wählen). Rot = Wiedervorlage fällig. Zahlen = Anzahl und Summe der Angebote.</p>';
	foreach ( array( true, false ) as $offen ) {
		echo '<div class="kjo-pl-row' . ( $offen ? '' : ' zu' ) . '">';
		if ( ! $offen ) {
			echo '<h2>Abgeschlossen</h2>';
		}
		foreach ( $st as $k => $s ) {
			if ( $s[2] !== $offen ) {
				continue;
			}
			$l   = isset( $by[ $k ] ) ? $by[ $k ] : array();
			$sum = 0;
			foreach ( $l as $p ) {
				$sum += (float) get_post_meta( $p->ID, '_kjo_lead_betrag', true );
			}
			echo '<div class="kjo-pl-col" data-st="' . esc_attr( $k ) . '"><h3 style="border-color:' . esc_attr( $s[1] ) . '">' . esc_html( $s[0] ) . ' <span class="n">' . count( $l ) . ( $sum ? ' · ' . esc_html( kjo_vl_eur( $sum ) ) : '' ) . '</span></h3><div class="kjo-pl-drop">';
			foreach ( $offen ? $l : array_slice( $l, 0, 30 ) as $p ) {
				echo $card( $p ); // phpcs:ignore
			}
			echo '</div></div>';
		}
		echo '</div>';
	}
	echo '</div>';
	$nonce = wp_create_nonce( 'kjo_lead_board' );
	?>
<style>
.kjo-pl-row{display:flex;gap:12px;overflow-x:auto;padding-bottom:12px;align-items:flex-start}.kjo-pl-row.zu{flex-wrap:wrap;margin-top:18px}.kjo-pl-row.zu h2{flex-basis:100%;margin:0 0 4px}
.kjo-pl-col{flex:0 0 205px;background:#f0f0f1;border-radius:6px;padding:8px}.kjo-pl-row.zu .kjo-pl-col{flex-basis:200px;opacity:.9}
.kjo-pl-col h3{margin:0 0 8px;font-size:13px;padding:4px 0 6px;border-bottom:3px solid}.kjo-pl-col h3 .n{color:#646970;font-weight:400}
.kjo-pl-drop{min-height:60px}.kjo-pl-col.over .kjo-pl-drop{outline:2px dashed #2271b1;outline-offset:2px;border-radius:4px}
.kjo-pc{background:#fff;border:1px solid #dcdcde;border-left:4px solid #c3c4c7;border-radius:4px;padding:8px 9px;margin:0 0 8px;font-size:12.5px;line-height:1.45;cursor:grab}.kjo-pc.due{border-left-color:#d63638;background:#fcf0f1}
.kjo-pc a{text-decoration:none}.kjo-pc .m{color:#646970}.kjo-pc .wv{color:#646970}.kjo-pc.due .wv{color:#d63638;font-weight:600}.kjo-pc select{width:100%;margin-top:6px;font-size:12px;min-height:26px}
</style>
<script>
(function(){var drag=null;function save(c,st){var fd=new FormData();fd.append("action","kjo_lead_status");fd.append("id",c.getAttribute("data-id"));fd.append("status",st);fd.append("_ajax_nonce","<?php echo esc_js( $nonce ); ?>");
return fetch(ajaxurl,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(j){if(!j||!j.success){alert("Konnte nicht gespeichert werden.");location.reload();}else if(j.data&&j.data.reload){location.reload();}});}
function move(c,st){var col=document.querySelector('.kjo-pl-col[data-st="'+st+'"] .kjo-pl-drop');if(col)col.prepend(c);c.querySelector("select").value=st;save(c,st);}
document.addEventListener("dragstart",function(e){var c=e.target.closest&&e.target.closest(".kjo-pc");if(c){drag=c;e.dataTransfer.effectAllowed="move";try{e.dataTransfer.setData("text/plain",c.getAttribute("data-id"));}catch(x){}}});
document.addEventListener("dragover",function(e){var col=e.target.closest&&e.target.closest(".kjo-pl-col");if(col&&drag){e.preventDefault();[].forEach.call(document.querySelectorAll(".kjo-pl-col.over"),function(x){if(x!==col)x.classList.remove("over")});col.classList.add("over");}});
document.addEventListener("drop",function(e){var col=e.target.closest&&e.target.closest(".kjo-pl-col");[].forEach.call(document.querySelectorAll(".kjo-pl-col.over"),function(x){x.classList.remove("over")});if(col&&drag){e.preventDefault();var st=col.getAttribute("data-st");if(drag.querySelector("select").value!==st)move(drag,st);}drag=null;});
document.addEventListener("change",function(e){if(e.target.classList.contains("kjo-pc-s")){move(e.target.closest(".kjo-pc"),e.target.value);}});})();
</script>
	<?php
}
add_action(
	'wp_ajax_kjo_lead_status',
	function () {
		check_ajax_referer( 'kjo_lead_board' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$st = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
		if ( ! $id || 'kjo_anfrage' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || ! isset( kjo_lead_stati()[ $st ] ) ) {
			wp_send_json_error();
		}
		kjo_lead_set( $id, $st );
		wp_send_json_success( array( 'reload' => true ) ); // Summen, Wiedervorlage und Kalender neu anzeigen
	}
);

/* ---------------------------------------------------------------
 * Dashboard-Kasten
 * ------------------------------------------------------------- */
add_action(
	'wp_dashboard_setup',
	function () {
		if ( current_user_can( 'edit_pages' ) ) {
			wp_add_dashboard_widget( 'kjo_lead_dash', 'DJ-Anfragen', 'kjo_lead_dash' );
		}
	}
);
function kjo_lead_dash() {
	$st  = kjo_lead_stati();
	$cnt = kjo_lead_counts();
	$due = array_filter( kjo_lead_all(), function ( $p ) { return kjo_lead_due( $p->ID ); } );
	$pip = 0;
	$geb = 0;
	$y   = wp_date( 'Y' );
	foreach ( kjo_lead_all() as $p ) {
		$s = kjo_lead_get( $p->ID );
		$b = (float) get_post_meta( $p->ID, '_kjo_lead_betrag', true );
		if ( in_array( $s, array( 'versendet', 'ueberlegt' ), true ) ) {
			$pip += $b;
		}
		if ( in_array( $s, array( 'gebucht', 'durchgefuehrt' ), true ) && 0 === strpos( (string) get_post_meta( $p->ID, '_kjo_von', true ), $y ) ) {
			$geb += $b;
		}
	}
	echo '<p>';
	foreach ( $st as $k => $s ) {
		if ( $s[2] && ! empty( $cnt[ $k ] ) ) {
			echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=kjo_anfrage&kjo_lead=' . $k ) ) . '" style="margin-right:6px">' . kjo_lead_badge( $k ) . ' ' . (int) $cnt[ $k ] . '</a> '; // phpcs:ignore
		}
	}
	echo '</p><p>Offene Angebote: <strong>' . esc_html( kjo_vl_eur( $pip ) ) . '</strong> · Gebucht ' . esc_html( $y ) . ': <strong>' . esc_html( kjo_vl_eur( $geb ) ) . '</strong></p>';
	if ( $due ) {
		echo '<p style="color:#d63638"><strong>Wiedervorlage fällig:</strong></p><ul>';
		foreach ( $due as $p ) {
			echo '<li><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( $p->post_title ) . '</a></li>';
		}
		echo '</ul>';
	}
	echo '<p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=kjo_anfrage&page=kjo-pipeline' ) ) . '">Pipeline öffnen</a></p>';
}
