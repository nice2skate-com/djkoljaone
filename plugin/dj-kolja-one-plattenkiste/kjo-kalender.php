<?php
/**
 * Terminverwaltung (seit 1.25.0)
 *
 * Admin „Kalender“: Monatsansicht, Schnell-Eintrag, Terminliste. Termine (Beitragstyp kjo_termin) haben
 *  - Art:    dj (DJ-Auftritt) · privat (privat geblockt, Equipment frei) · equip (nur Equipment/Vermietung)
 *  - Status: fest · vor (vorreserviert)
 *  - Equipment: none (fremde Anlage) · all (komplettes Equipment) · select (ausgewählte Geräte)
 * Zusätzlich: Kasten „Belegt an“ bei jedem Gerät, Knopf „Als vorreserviert eintragen“ in Mietanfragen,
 * iCal-Abo der eigenen Termine (geheimer Link) und stündlicher Import von iCal-Quellen (z. B. Google „DJ-Blocker“) als „privat geblockt“.
 * Website: REST /kjo/v1/verfuegbar → Hinweise im Wunschtermin-Formular und im Mietkorb.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------
 * Grundlagen
 * ------------------------------------------------------------- */
function kjo_kal_arten() {
	return array(
		'dj'     => 'DJ-Auftritt',
		'privat' => 'Privat geblockt',
		'equip'  => 'Nur Equipment (Vermietung)',
	);
}
function kjo_kal_opt( $k, $def = '' ) {
	$o = get_option( 'kjo_kalender', array() );
	return isset( $o[ $k ] ) && '' !== $o[ $k ] ? $o[ $k ] : $def;
}
function kjo_kal_date( $v ) {
	$v = trim( (string) $v );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
}
function kjo_kal_de( $d ) {
	$t = DateTime::createFromFormat( 'Y-m-d', $d );
	return $t ? $t->format( 'd.m.Y' ) : $d;
}
function kjo_kal_today() {
	return wp_date( 'Y-m-d' );
}

add_action(
	'init',
	function () {
		register_post_type(
			'kjo_termin',
			array(
				'labels'              => array(
					'name'          => 'Termine',
					'singular_name' => 'Termin',
					'all_items'     => 'Terminliste',
					'add_new'       => 'Termin hinzufügen',
					'add_new_item'  => 'Neuer Termin',
					'edit_item'     => 'Termin bearbeiten',
					'not_found'     => 'Keine Termine',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'kjo-kalender',
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
			)
		);
	}
);

/** Alle Termine (eigene + importierte), die den Zeitraum [von, bis] berühren. */
function kjo_kal_termine( $von = null, $bis = null ) {
	static $own = null;
	if ( null === $own ) {
		$own = array();
		$ids = get_posts(
			array(
				'post_type'      => 'kjo_termin',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$t = kjo_kal_get( $id );
			if ( $t['von'] ) {
				$own[] = $t;
			}
		}
	}
	$all = $own;
	foreach ( (array) get_option( 'kjo_kal_import', array() ) as $e ) {
		$all[] = array( 'id' => 0, 'von' => $e['von'], 'bis' => $e['bis'], 'art' => 'privat', 'status' => 'fest', 'equip' => 'none', 'geraete' => array(), 'notiz' => $e['titel'], 'quelle' => $e['quelle'] );
	}
	if ( null === $von ) {
		return $all;
	}
	$bis = $bis ? $bis : $von;
	return array_values(
		array_filter(
			$all,
			function ( $t ) use ( $von, $bis ) {
				return $t['von'] <= $bis && $t['bis'] >= $von;
			}
		)
	);
}
function kjo_kal_get( $id ) {
	$g = get_post_meta( $id, '_kjo_tn_geraete', true );
	$v = kjo_kal_date( get_post_meta( $id, '_kjo_tn_von', true ) );
	$b = kjo_kal_date( get_post_meta( $id, '_kjo_tn_bis', true ) );
	return array(
		'id'      => (int) $id,
		'von'     => $v,
		'bis'     => $b && $b >= $v ? $b : $v,
		'art'     => (string) get_post_meta( $id, '_kjo_tn_art', true ),
		'status'  => 'vor' === get_post_meta( $id, '_kjo_tn_status', true ) ? 'vor' : 'fest',
		'equip'   => (string) get_post_meta( $id, '_kjo_tn_equip', true ),
		'geraete' => is_array( $g ) ? array_map( 'intval', $g ) : array(),
		'notiz'   => (string) get_post_meta( $id, '_kjo_tn_notiz', true ),
		'quelle'  => '',
	);
}
/** Termin speichern (neu oder vorhanden). */
function kjo_kal_save( $d, $id = 0 ) {
	$arten = kjo_kal_arten();
	$von   = kjo_kal_date( isset( $d['von'] ) ? $d['von'] : '' );
	if ( ! $von ) {
		return 0;
	}
	$bis = kjo_kal_date( isset( $d['bis'] ) ? $d['bis'] : '' );
	$bis = $bis && $bis >= $von ? $bis : $von;
	$art = isset( $d['art'], $arten[ $d['art'] ] ) ? $d['art'] : 'dj';
	$st  = isset( $d['status'] ) && 'vor' === $d['status'] ? 'vor' : 'fest';
	$eq  = isset( $d['equip'] ) && in_array( $d['equip'], array( 'none', 'all', 'select' ), true ) ? $d['equip'] : ( 'privat' === $art ? 'none' : 'all' );
	if ( 'privat' === $art ) {
		$eq = 'none';
	}
	$ger   = isset( $d['geraete'] ) && is_array( $d['geraete'] ) ? array_values( array_filter( array_map( 'absint', $d['geraete'] ) ) ) : array();
	$notiz = isset( $d['notiz'] ) ? sanitize_text_field( $d['notiz'] ) : '';
	$title = kjo_kal_de( $von ) . ( $bis !== $von ? ' – ' . kjo_kal_de( $bis ) : '' ) . ' · ' . $arten[ $art ] . ( 'vor' === $st ? ' (vorreserviert)' : '' ) . ( $notiz ? ' · ' . $notiz : '' );
	$arr   = array( 'post_type' => 'kjo_termin', 'post_status' => 'private', 'post_title' => $title );
	if ( $id ) {
		$arr['ID'] = $id;
		remove_action( 'save_post_kjo_termin', 'kjo_kal_on_save', 10 );
		wp_update_post( $arr );
		add_action( 'save_post_kjo_termin', 'kjo_kal_on_save', 10, 2 );
	} else {
		$id = wp_insert_post( $arr );
	}
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_kjo_tn_von', $von );
	update_post_meta( $id, '_kjo_tn_bis', $bis );
	update_post_meta( $id, '_kjo_tn_art', $art );
	update_post_meta( $id, '_kjo_tn_status', $st );
	update_post_meta( $id, '_kjo_tn_equip', $eq );
	update_post_meta( $id, '_kjo_tn_geraete', $ger );
	update_post_meta( $id, '_kjo_tn_notiz', $notiz );
	return (int) $id;
}

/* ---------------------------------------------------------------
 * Verfügbarkeit
 * ------------------------------------------------------------- */
function kjo_kal_rank( $a, $b ) {
	$r = array( 'frei' => 0, 'vor' => 1, 'belegt' => 2 );
	return $r[ $a ] >= $r[ $b ] ? $a : $b;
}
/** Status für DJ, jedes Gerät und jedes Paket im Zeitraum. */
function kjo_kal_status( $von, $bis = '' ) {
	$bis   = $bis && $bis >= $von ? $bis : $von;
	$dj    = 'frei';
	$all   = 'frei';
	$items = array();
	$ger   = function_exists( 'kjo_vl_items' ) ? kjo_vl_items( 'kjo_geraet' ) : array();
	foreach ( $ger as $g ) {
		$items[ 'g' . $g->ID ] = 'frei';
	}
	foreach ( kjo_kal_termine( $von, $bis ) as $t ) {
		$s = 'vor' === $t['status'] ? 'vor' : 'belegt';
		if ( 'dj' === $t['art'] || 'privat' === $t['art'] ) {
			$dj = kjo_kal_rank( $dj, $s );
		}
		if ( 'privat' === $t['art'] || 'none' === $t['equip'] ) {
			continue;
		}
		if ( 'all' === $t['equip'] ) {
			$all = kjo_kal_rank( $all, $s );
			foreach ( $items as $k => $v ) {
				$items[ $k ] = kjo_kal_rank( $v, $s );
			}
		} else {
			foreach ( $t['geraete'] as $gid ) {
				if ( isset( $items[ 'g' . $gid ] ) ) {
					$items[ 'g' . $gid ] = kjo_kal_rank( $items[ 'g' . $gid ], $s );
				}
			}
		}
	}
	foreach ( ( function_exists( 'kjo_vl_items' ) ? kjo_vl_items( 'kjo_paket' ) : array() ) as $p ) {
		$items[ 'p' . $p->ID ] = $all;
	}
	$vals  = array_values( array_filter( $items, function ( $k ) { return 'g' === $k[0]; }, ARRAY_FILTER_USE_KEY ) );
	$equip = ! $vals ? 'frei' : ( count( array_filter( $vals, function ( $v ) { return 'belegt' === $v; } ) ) === count( $vals ) ? 'belegt' : ( in_array( 'belegt', $vals, true ) || in_array( 'vor', $vals, true ) ? 'teil' : 'frei' ) );
	return array( 'dj' => $dj, 'equip' => $equip, 'items' => (object) $items );
}
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/verfuegbar',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) {
					$von = kjo_kal_date( $r->get_param( 'von' ) );
					$bis = kjo_kal_date( $r->get_param( 'bis' ) );
					if ( ! $von ) {
						return new WP_REST_Response( array( 'error' => 'Datum fehlt' ), 400 );
					}
					$res = new WP_REST_Response( kjo_kal_status( $von, $bis ), 200 );
					$res->header( 'Cache-Control', 'no-store' );
					return $res;
				},
			)
		);
	}
);

/* ---------------------------------------------------------------
 * Admin: Menü, Monatsansicht, Schnell-Eintrag
 * ------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Kalender', 'Kalender', 'edit_pages', 'kjo-kalender', 'kjo_kal_page', 'dashicons-calendar-alt', 25 );
		add_submenu_page( 'kjo-kalender', 'Kalender', 'Monatsansicht', 'edit_pages', 'kjo-kalender', 'kjo_kal_page' );
		add_submenu_page( 'kjo-kalender', 'Kalender – Abo & Import', 'Abo & Import', 'manage_options', 'kjo-kalender-sync', 'kjo_kal_sync_page' );
	}
);

function kjo_kal_form_fields( $t = null, $prefix = 'kjo_tn' ) {
	$t     = $t ? $t : array( 'von' => '', 'bis' => '', 'art' => 'dj', 'status' => 'fest', 'equip' => 'all', 'geraete' => array(), 'notiz' => '' );
	$arten = kjo_kal_arten();
	$ger   = get_posts( array( 'post_type' => 'kjo_geraet', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	ob_start();
	?>
<div class="kjo-tn-form">
<p><label>Von <input type="date" name="<?php echo esc_attr( $prefix ); ?>[von]" value="<?php echo esc_attr( $t['von'] ); ?>" required></label>
<label>Bis <input type="date" name="<?php echo esc_attr( $prefix ); ?>[bis]" value="<?php echo esc_attr( $t['bis'] ); ?>"></label> <span class="description">leer = eintägig</span></p>
<p><strong>Art:</strong>
<?php foreach ( $arten as $k => $l ) : ?>
<label class="kjo-tn-r"><input type="radio" name="<?php echo esc_attr( $prefix ); ?>[art]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $t['art'], $k ); ?>> <?php echo esc_html( $l ); ?></label>
<?php endforeach; ?></p>
<p><strong>Status:</strong>
<label class="kjo-tn-r"><input type="radio" name="<?php echo esc_attr( $prefix ); ?>[status]" value="fest" <?php checked( $t['status'], 'fest' ); ?>> fest</label>
<label class="kjo-tn-r"><input type="radio" name="<?php echo esc_attr( $prefix ); ?>[status]" value="vor" <?php checked( $t['status'], 'vor' ); ?>> vorreserviert</label></p>
<div class="kjo-tn-eq"><p><strong>Equipment:</strong>
<label class="kjo-tn-r"><input type="radio" name="<?php echo esc_attr( $prefix ); ?>[equip]" value="none" <?php checked( $t['equip'], 'none' ); ?>> nicht sperren (fremde Anlage)</label>
<label class="kjo-tn-r"><input type="radio" name="<?php echo esc_attr( $prefix ); ?>[equip]" value="all" <?php checked( $t['equip'], 'all' ); ?>> komplettes Equipment sperren</label>
<label class="kjo-tn-r"><input type="radio" name="<?php echo esc_attr( $prefix ); ?>[equip]" value="select" <?php checked( $t['equip'], 'select' ); ?>> nur diese Geräte:</label></p>
<div class="kjo-tn-ger">
<?php foreach ( $ger as $g ) : ?>
<label><input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[geraete][]" value="<?php echo (int) $g->ID; ?>" <?php checked( in_array( (int) $g->ID, $t['geraete'], true ) ); ?>> <?php echo esc_html( $g->post_title ); ?></label>
<?php endforeach; ?>
</div></div>
<p><label>Notiz (Kunde, Ort – erscheint nur im Admin und im privaten Abo)<br><input type="text" class="large-text" name="<?php echo esc_attr( $prefix ); ?>[notiz]" value="<?php echo esc_attr( $t['notiz'] ); ?>"></label></p>
</div>
	<?php
	return (string) ob_get_clean();
}
function kjo_kal_admin_css() {
	return '<style>.kjo-tn-r{margin-right:14px;white-space:nowrap}.kjo-tn-ger{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:4px 14px;margin:0 0 10px 20px}
.kjo-cal{border-collapse:collapse;width:100%;table-layout:fixed;background:#fff}.kjo-cal th{padding:6px;background:#f0f0f1;font-weight:600}.kjo-cal td{border:1px solid #dcdcde;vertical-align:top;height:86px;padding:4px;cursor:pointer;position:relative}
.kjo-cal td.o{background:#f6f7f7;color:#a7aaad;cursor:default}.kjo-cal td.h{outline:2px solid #2271b1;outline-offset:-2px}.kjo-cal .n{font-weight:600}.kjo-cal .e{display:block;margin-top:3px;padding:2px 5px;border-radius:3px;font-size:11px;line-height:1.35;color:#1d2327;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-decoration:none}
.kjo-cal .dj-fest{background:#f5b7b1}.kjo-cal .dj-vor,.kjo-cal .equip-vor{background:#fbe7a3}.kjo-cal .privat-fest{background:#dcdcde}.kjo-cal .equip-fest{background:#b9d7f5}.kjo-cal .imp{background:#e5e5e5;font-style:italic}
.kjo-legend span{display:inline-block;margin-right:14px}.kjo-legend i{display:inline-block;width:12px;height:12px;border-radius:2px;margin-right:4px;vertical-align:-1px}
.kjo-tn-box{background:#fff;border:1px solid #c3c4c7;padding:12px 16px;margin:16px 0}</style>';
}
function kjo_kal_admin_js() {
	return '<script>(function(){function upd(f){var a=f.querySelector("input[name$=\'[art]\']:checked"),eq=f.querySelector(".kjo-tn-eq"),g=f.querySelector(".kjo-tn-ger"),s=f.querySelector("input[name$=\'[equip]\']:checked");if(eq)eq.style.display=a&&a.value==="privat"?"none":"";if(g)g.style.display=s&&s.value==="select"?"":"none";}
document.querySelectorAll(".kjo-tn-form").forEach(function(f){f.addEventListener("change",function(){upd(f)});upd(f);});
document.querySelectorAll(".kjo-cal td[data-d]").forEach(function(td){td.addEventListener("click",function(e){if(e.target.closest("a"))return;var f=document.getElementById("kjo-tn-quick");if(!f)return;f.querySelector("input[name$=\'[von]\']").value=td.getAttribute("data-d");f.querySelector("input[name$=\'[bis]\']").value="";document.querySelectorAll(".kjo-cal td.h").forEach(function(x){x.classList.remove("h")});td.classList.add("h");f.scrollIntoView({behavior:"smooth",block:"start"});});});})();</script>';
}

function kjo_kal_page() {
	$m     = isset( $_GET['m'] ) && preg_match( '/^\d{4}-\d{2}$/', sanitize_text_field( wp_unslash( $_GET['m'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['m'] ) ) : wp_date( 'Y-m' ); // phpcs:ignore
	$first = DateTime::createFromFormat( 'Y-m-d', $m . '-01', wp_timezone() );
	$days  = (int) $first->format( 't' );
	$lead  = ( (int) $first->format( 'N' ) ) - 1;
	$prev  = ( clone $first )->modify( '-1 month' )->format( 'Y-m' );
	$next  = ( clone $first )->modify( '+1 month' )->format( 'Y-m' );
	$mon   = array( '', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );
	$von   = $m . '-01';
	$bis   = $m . '-' . sprintf( '%02d', $days );
	$tn    = kjo_kal_termine( $von, $bis );
	$base  = admin_url( 'admin.php?page=kjo-kalender' );
	echo kjo_kal_admin_css(); // phpcs:ignore
	echo '<div class="wrap"><h1 class="wp-heading-inline">Kalender</h1> <a class="page-title-action" href="#kjo-tn-quick">Termin eintragen</a>';
	if ( isset( $_GET['kjo_msg'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['kjo_msg'] ) ) ) . '</p></div>'; // phpcs:ignore
	}
	echo '<p class="kjo-legend"><span><i style="background:#f5b7b1"></i>DJ fest</span><span><i style="background:#fbe7a3"></i>vorreserviert</span><span><i style="background:#dcdcde"></i>privat</span><span><i style="background:#b9d7f5"></i>nur Equipment</span><span><i style="background:#e5e5e5"></i>importiert (DJ-Blocker)</span></p>';
	echo '<h2><a href="' . esc_url( add_query_arg( 'm', $prev, $base ) ) . '">‹</a> ' . esc_html( $mon[ (int) $first->format( 'n' ) ] . ' ' . $first->format( 'Y' ) ) . ' <a href="' . esc_url( add_query_arg( 'm', $next, $base ) ) . '">›</a> <a class="button button-small" href="' . esc_url( $base ) . '">Heute</a></h2>';
	echo '<table class="kjo-cal"><tr><th>Mo</th><th>Di</th><th>Mi</th><th>Do</th><th>Fr</th><th>Sa</th><th>So</th></tr><tr>';
	for ( $i = 0; $i < $lead; $i++ ) {
		echo '<td class="o"></td>';
	}
	$today = kjo_kal_today();
	for ( $d = 1; $d <= $days; $d++ ) {
		$ds = $m . '-' . sprintf( '%02d', $d );
		echo '<td data-d="' . esc_attr( $ds ) . '"' . ( $ds === $today ? ' style="background:#f0f6fc"' : '' ) . '><span class="n">' . (int) $d . '</span>';
		foreach ( $tn as $t ) {
			if ( $t['von'] <= $ds && $t['bis'] >= $ds ) {
				$cls = $t['quelle'] ? 'imp' : $t['art'] . '-' . $t['status'];
				$lab = $t['quelle'] ? '🔒 ' . $t['notiz'] : ( 'dj' === $t['art'] ? 'DJ' : ( 'privat' === $t['art'] ? 'Privat' : 'Verleih' ) ) . ( 'vor' === $t['status'] ? ' (vor)' : '' ) . ( $t['notiz'] ? ': ' . $t['notiz'] : '' );
				if ( $t['id'] ) {
					echo '<a class="e ' . esc_attr( $cls ) . '" href="' . esc_url( get_edit_post_link( $t['id'] ) ) . '" title="' . esc_attr( $lab ) . '">' . esc_html( $lab ) . '</a>';
				} else {
					echo '<span class="e ' . esc_attr( $cls ) . '" title="' . esc_attr( $lab ) . '">' . esc_html( $lab ) . '</span>';
				}
			}
		}
		echo '</td>';
		if ( 0 === ( $lead + $d ) % 7 && $d < $days ) {
			echo '</tr><tr>';
		}
	}
	$rest = ( 7 - ( $lead + $days ) % 7 ) % 7;
	for ( $i = 0; $i < $rest; $i++ ) {
		echo '<td class="o"></td>';
	}
	echo '</tr></table><p class="description">Tipp: Auf einen Tag klicken, um dort einen Termin einzutragen.</p>';
	echo '<div class="kjo-tn-box" id="kjo-tn-quick"><h2>Termin eintragen</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'kjo_tn_add' );
	echo '<input type="hidden" name="action" value="kjo_tn_add"><input type="hidden" name="m" value="' . esc_attr( $m ) . '">' . kjo_kal_form_fields(); // phpcs:ignore
	submit_button( 'Termin speichern' );
	echo '</form></div>';
	/* Kommende Termine */
	$up = array_filter(
		kjo_kal_termine(),
		function ( $t ) use ( $today ) {
			return $t['bis'] >= $today;
		}
	);
	usort( $up, function ( $a, $b ) { return strcmp( $a['von'], $b['von'] ); } );
	echo '<h2>Kommende Termine</h2><table class="widefat striped"><thead><tr><th>Datum</th><th>Art</th><th>Status</th><th>Equipment</th><th>Notiz</th><th></th></tr></thead><tbody>';
	$arten = kjo_kal_arten();
	foreach ( array_slice( $up, 0, 100 ) as $t ) {
		$eq = 'privat' === $t['art'] ? '–' : ( 'none' === $t['equip'] ? 'nicht gesperrt' : ( 'all' === $t['equip'] ? 'komplett gesperrt' : implode( ', ', array_map( 'get_the_title', $t['geraete'] ) ) ) );
		echo '<tr><td>' . esc_html( kjo_kal_de( $t['von'] ) . ( $t['bis'] !== $t['von'] ? ' – ' . kjo_kal_de( $t['bis'] ) : '' ) ) . '</td><td>' . esc_html( $t['quelle'] ? 'Privat (importiert)' : $arten[ $t['art'] ] ) . '</td><td>' . esc_html( 'vor' === $t['status'] ? 'vorreserviert' : 'fest' ) . '</td><td>' . esc_html( $eq ) . '</td><td>' . esc_html( $t['notiz'] ) . '</td><td>';
		if ( $t['id'] ) {
			echo '<a href="' . esc_url( get_edit_post_link( $t['id'] ) ) . '">Bearbeiten</a> · <a href="' . esc_url( get_delete_post_link( $t['id'], '', true ) ) . '" onclick="return confirm(\'Termin löschen?\')">Löschen</a>';
		}
		echo '</td></tr>';
	}
	if ( ! $up ) {
		echo '<tr><td colspan="6">Noch keine Termine eingetragen.</td></tr>';
	}
	echo '</tbody></table></div>' . kjo_kal_admin_js(); // phpcs:ignore
}
add_action(
	'admin_post_kjo_tn_add',
	function () {
		if ( ! current_user_can( 'edit_pages' ) || ! check_admin_referer( 'kjo_tn_add' ) ) {
			wp_die( 'Keine Berechtigung.' );
		}
		$d  = isset( $_POST['kjo_tn'] ) && is_array( $_POST['kjo_tn'] ) ? wp_unslash( $_POST['kjo_tn'] ) : array(); // phpcs:ignore
		$id = kjo_kal_save( $d );
		$m  = isset( $_POST['m'] ) ? sanitize_text_field( wp_unslash( $_POST['m'] ) ) : '';
		$m  = ! empty( $d['von'] ) ? substr( sanitize_text_field( $d['von'] ), 0, 7 ) : $m;
		wp_safe_redirect( add_query_arg( array( 'page' => 'kjo-kalender', 'm' => $m, 'kjo_msg' => $id ? 'Termin gespeichert.' : 'Bitte ein Datum angeben.' ), admin_url( 'admin.php' ) ) );
		exit;
	}
);

/* Bearbeiten-Maske eines Termins */
add_action(
	'add_meta_boxes_kjo_termin',
	function () {
		add_meta_box(
			'kjo_tn_daten',
			'Termin',
			function ( $post ) {
				wp_nonce_field( 'kjo_tn_edit', 'kjo_tn_nonce' );
				$t = kjo_kal_get( $post->ID );
				echo kjo_kal_admin_css() . kjo_kal_form_fields( $t['von'] ? $t : null ) . kjo_kal_admin_js(); // phpcs:ignore
				echo '<p class="description">Der Titel wird beim Speichern automatisch aus Datum, Art und Notiz gebildet.</p>';
			},
			'kjo_termin',
			'normal',
			'high'
		);
	}
);
function kjo_kal_on_save( $id, $post ) {
	if ( wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['kjo_tn_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_tn_nonce'] ) ), 'kjo_tn_edit' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$d = isset( $_POST['kjo_tn'] ) && is_array( $_POST['kjo_tn'] ) ? wp_unslash( $_POST['kjo_tn'] ) : array(); // phpcs:ignore
	kjo_kal_save( $d, $id );
}
add_action( 'save_post_kjo_termin', 'kjo_kal_on_save', 10, 2 );
/* Neue Termine aus der Terminliste sind privat (nie öffentlich). */
add_filter(
	'wp_insert_post_data',
	function ( $data ) {
		if ( 'kjo_termin' === $data['post_type'] && in_array( $data['post_status'], array( 'publish', 'future', 'pending' ), true ) ) {
			$data['post_status'] = 'private';
		}
		return $data;
	}
);

/* Kasten „Belegt an“ beim Gerät + Knopf „Als vorreserviert eintragen“ bei Mietanfragen */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'kjo_tn_geraet', 'Belegt an (Kalender)', 'kjo_kal_box_geraet', 'kjo_geraet', 'side', 'default' );
		add_meta_box( 'kjo_tn_anfrage', 'Kalender', 'kjo_kal_box_anfrage', 'kjo_mietanfrage', 'side', 'high' );
	}
);
function kjo_kal_box_geraet( $post ) {
	$today = kjo_kal_today();
	$l     = array_filter(
		kjo_kal_termine(),
		function ( $t ) use ( $post, $today ) {
			return $t['bis'] >= $today && 'privat' !== $t['art'] && ( 'all' === $t['equip'] || ( 'select' === $t['equip'] && in_array( (int) $post->ID, $t['geraete'], true ) ) );
		}
	);
	usort( $l, function ( $a, $b ) { return strcmp( $a['von'], $b['von'] ); } );
	echo '<ul style="margin:0 0 10px">';
	foreach ( $l as $t ) {
		echo '<li><a href="' . esc_url( get_edit_post_link( $t['id'] ) ) . '">' . esc_html( kjo_kal_de( $t['von'] ) . ( $t['bis'] !== $t['von'] ? '–' . kjo_kal_de( $t['bis'] ) : '' ) ) . '</a> · ' . esc_html( ( 'dj' === $t['art'] ? 'DJ' : 'Verleih' ) . ( 'vor' === $t['status'] ? ' (vor)' : '' ) . ( 'all' === $t['equip'] ? ', alles' : '' ) ) . '</li>';
	}
	if ( ! $l ) {
		echo '<li>Keine kommenden Termine.</li>';
	}
	echo '</ul><p><strong>+ Termin für dieses Gerät</strong></p>';
	echo '<p><label>Von<br><input type="date" name="kjo_tn_g[von]"></label><br><label>Bis<br><input type="date" name="kjo_tn_g[bis]"></label></p>';
	echo '<p><label><input type="radio" name="kjo_tn_g[status]" value="fest" checked> fest</label> <label><input type="radio" name="kjo_tn_g[status]" value="vor"> vorreserviert</label></p>';
	echo '<p><input type="text" name="kjo_tn_g[notiz]" placeholder="Notiz (Kunde, Ort)" style="width:100%"></p><p class="description">Wird beim Speichern des Geräts als Termin „Nur Equipment“ eingetragen. Alle Termine: <a href="' . esc_url( admin_url( 'admin.php?page=kjo-kalender' ) ) . '">Kalender</a></p>';
}
add_action(
	'save_post_kjo_geraet',
	function ( $id ) {
		if ( ! isset( $_POST['kjo_vl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_vl_nonce'] ) ), 'kjo_vl_save' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$d = isset( $_POST['kjo_tn_g'] ) && is_array( $_POST['kjo_tn_g'] ) ? wp_unslash( $_POST['kjo_tn_g'] ) : array(); // phpcs:ignore
		if ( ! empty( $d['von'] ) ) {
			kjo_kal_save( array( 'von' => $d['von'], 'bis' => isset( $d['bis'] ) ? $d['bis'] : '', 'art' => 'equip', 'status' => isset( $d['status'] ) ? $d['status'] : 'fest', 'equip' => 'select', 'geraete' => array( $id ), 'notiz' => isset( $d['notiz'] ) ? $d['notiz'] : '' ) );
		}
	},
	20
);
function kjo_kal_box_anfrage( $post ) {
	$von = kjo_kal_date( get_post_meta( $post->ID, '_kjo_von', true ) );
	$tid = (int) get_post_meta( $post->ID, '_kjo_termin', true );
	if ( $tid && get_post( $tid ) ) {
		echo '<p>✓ Im Kalender eingetragen: <a href="' . esc_url( get_edit_post_link( $tid ) ) . '">Termin öffnen</a></p>';
		return;
	}
	if ( ! $von ) {
		echo '<p>Diese Anfrage stammt aus einer älteren Version und enthält kein Datum zum Übernehmen.</p>';
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=kjo_tn_from_anfrage&id=' . $post->ID ), 'kjo_tn_from_anfrage_' . $post->ID );
	echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Als vorreserviert eintragen</a></p><p class="description">Übernimmt Zeitraum und angefragte Geräte. Bei Paketen wird das komplette Equipment gesperrt – anpassbar im Termin.</p>';
}
add_action(
	'admin_post_kjo_tn_from_anfrage',
	function () {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! check_admin_referer( 'kjo_tn_from_anfrage_' . $id ) ) {
			wp_die( 'Keine Berechtigung.' );
		}
		$items = get_post_meta( $id, '_kjo_items', true );
		$items = is_array( $items ) ? $items : array();
		$ger   = array();
		$pak   = false;
		foreach ( $items as $k ) {
			if ( preg_match( '/^g(\d+)$/', $k, $m ) ) {
				$ger[] = (int) $m[1];
			} elseif ( 0 === strpos( $k, 'p' ) ) {
				$pak = true;
			}
		}
		$tid = kjo_kal_save(
			array(
				'von'     => get_post_meta( $id, '_kjo_von', true ),
				'bis'     => get_post_meta( $id, '_kjo_bis', true ),
				'art'     => 'equip',
				'status'  => 'vor',
				'equip'   => $pak ? 'all' : 'select',
				'geraete' => $ger,
				'notiz'   => (string) get_post_meta( $id, '_kjo_name', true ),
			)
		);
		if ( $tid ) {
			update_post_meta( $id, '_kjo_termin', $tid );
		}
		wp_safe_redirect( $tid ? get_edit_post_link( $tid, 'url' ) : get_edit_post_link( $id, 'url' ) );
		exit;
	}
);

/* ---------------------------------------------------------------
 * iCal-Abo (eigene Termine) und Import (z. B. Google „DJ-Blocker“)
 * ------------------------------------------------------------- */
function kjo_kal_token() {
	$t = kjo_kal_opt( 'token' );
	if ( ! $t ) {
		$o          = get_option( 'kjo_kalender', array() );
		$o['token'] = wp_generate_password( 32, false, false );
		update_option( 'kjo_kalender', $o, false );
		$t = $o['token'];
	}
	return $t;
}
function kjo_kal_feed_url() {
	return add_query_arg( 'kjo_kalender', kjo_kal_token(), home_url( '/' ) );
}
function kjo_kal_ics_esc( $s ) {
	return str_replace( array( '\\', ';', ',', "\r", "\n" ), array( '\\\\', '\;', '\,', '', '\n' ), (string) $s );
}
function kjo_kal_ics_fold( $line ) {
	$out = '';
	while ( strlen( $line ) > 74 ) {
		$cut = 74;
		while ( $cut > 0 && ( ord( $line[ $cut ] ) & 0xC0 ) === 0x80 ) {
			$cut--; // nicht mitten in einem UTF-8-Zeichen trennen
		}
		$out .= substr( $line, 0, $cut ) . "\r\n ";
		$line = substr( $line, $cut );
	}
	return $out . $line . "\r\n";
}
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['kjo_kalender'] ) ) { // phpcs:ignore
			return;
		}
		$tok = sanitize_text_field( wp_unslash( $_GET['kjo_kalender'] ) ); // phpcs:ignore
		if ( ! hash_equals( kjo_kal_token(), $tok ) ) {
			status_header( 404 );
			exit;
		}
		$notes = '0' !== kjo_kal_opt( 'notizen', '1' );
		$arten = kjo_kal_arten();
		$ics   = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//DJ KOLJA ONE//Kalender//DE\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n" . kjo_kal_ics_fold( 'X-WR-CALNAME:DJ KOLJA ONE – Buchungen' ) . "X-WR-TIMEZONE:Europe/Berlin\r\nREFRESH-INTERVAL;VALUE=DURATION:PT1H\r\nX-PUBLISHED-TTL:PT1H\r\n";
		$host  = wp_parse_url( home_url(), PHP_URL_HOST );
		foreach ( kjo_kal_termine() as $t ) {
			if ( ! $t['id'] ) {
				continue; // Importierte Termine nicht zurückspiegeln.
			}
			$end  = ( new DateTime( $t['bis'] ) )->modify( '+1 day' )->format( 'Ymd' );
			$what = 'dj' === $t['art'] ? 'DJ' : ( 'privat' === $t['art'] ? 'Privat geblockt' : 'Verleih' );
			if ( 'privat' !== $t['art'] && 'select' === $t['equip'] && $t['geraete'] ) {
				$eq = implode( ', ', array_map( 'get_the_title', $t['geraete'] ) );
			} else {
				$eq = 'all' === $t['equip'] ? 'komplettes Equipment' : ( 'none' === $t['equip'] && 'dj' === $t['art'] ? 'fremde Anlage' : '' );
			}
			$sum  = $what . ( $notes && $t['notiz'] ? ': ' . $t['notiz'] : '' ) . ( 'vor' === $t['status'] ? ' (vorreserviert)' : '' );
			$desc = $arten[ $t['art'] ] . ( 'vor' === $t['status'] ? ', vorreserviert' : ', fest' ) . ( $eq ? "\nEquipment: " . $eq : '' ) . ( $notes && $t['notiz'] ? "\nNotiz: " . $t['notiz'] : '' );
			$ics .= "BEGIN:VEVENT\r\n" . 'UID:termin-' . $t['id'] . '@' . $host . "\r\nDTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\nDTSTART;VALUE=DATE:" . str_replace( '-', '', $t['von'] ) . "\r\nDTEND;VALUE=DATE:" . $end . "\r\n" . kjo_kal_ics_fold( 'SUMMARY:' . kjo_kal_ics_esc( $sum ) ) . kjo_kal_ics_fold( 'DESCRIPTION:' . kjo_kal_ics_esc( $desc ) ) . 'STATUS:' . ( 'vor' === $t['status'] ? 'TENTATIVE' : 'CONFIRMED' ) . "\r\nTRANSP:OPAQUE\r\nEND:VEVENT\r\n";
		}
		$ics .= "END:VCALENDAR\r\n";
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="dj-kolja-one.ics"' );
		header( 'X-Robots-Tag: noindex' );
		echo $ics; // phpcs:ignore
		exit;
	},
	0
);

/** ICS-Text → Liste von Tagen [von, bis, titel] (nur ab gestern, max. 2 Jahre voraus; Wiederholungen werden nicht ausgerollt). */
function kjo_kal_parse_ics( $txt, $nur_ganztag = false ) {
	$txt   = preg_replace( "/\r?\n[ \t]/", '', str_replace( "\r\n", "\n", (string) $txt ) );
	$tz    = wp_timezone();
	$min   = ( new DateTime( 'yesterday', $tz ) )->format( 'Y-m-d' );
	$max   = ( new DateTime( '+2 years', $tz ) )->format( 'Y-m-d' );
	$out   = array();
	$parse = function ( $line ) use ( $tz ) {
		if ( ! preg_match( '/^(DTSTART|DTEND)([^:]*):(\d{8})(T(\d{6})(Z?))?/', $line, $m ) ) {
			return null;
		}
		if ( empty( $m[4] ) ) {
			return array( substr( $m[3], 0, 4 ) . '-' . substr( $m[3], 4, 2 ) . '-' . substr( $m[3], 6, 2 ), true );
		}
		$ptz = $tz;
		if ( 'Z' === $m[6] ) {
			$ptz = new DateTimeZone( 'UTC' );
		} elseif ( preg_match( '/TZID=([^;:]+)/', $m[2], $z ) ) {
			try {
				$ptz = new DateTimeZone( trim( $z[1], '"' ) );
			} catch ( Exception $e ) {
				$ptz = $tz;
			}
		}
		$d = DateTime::createFromFormat( 'YmdHis', $m[3] . $m[5], $ptz );
		if ( ! $d ) {
			return null;
		}
		$d->setTimezone( $tz );
		return array( $d->format( 'Y-m-d' ), false, $d );
	};
	if ( ! preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', $txt, $evs ) ) {
		return $out;
	}
	foreach ( $evs[1] as $ev ) {
		$s = null;
		$e = null;
		$t = '';
		$x = false;
		foreach ( explode( "\n", $ev ) as $line ) {
			if ( 0 === strpos( $line, 'DTSTART' ) ) {
				$s = $parse( $line );
			} elseif ( 0 === strpos( $line, 'DTEND' ) ) {
				$e = $parse( $line );
			} elseif ( 0 === strpos( $line, 'SUMMARY' ) ) {
				$t = stripcslashes( substr( $line, strpos( $line, ':' ) + 1 ) );
			} elseif ( 0 === strpos( $line, 'STATUS:CANCELLED' ) || 0 === strpos( $line, 'TRANSP:TRANSPARENT' ) ) {
				$x = true; // abgesagt oder als „frei“ markiert
			}
		}
		if ( ! $s || $x || ( $nur_ganztag && ! $s[1] ) ) {
			continue;
		}
		$von = $s[0];
		$bis = $von;
		if ( $e ) {
			if ( $e[1] ) {
				$bis = ( new DateTime( $e[0] ) )->modify( '-1 day' )->format( 'Y-m-d' ); // DTEND bei Ganztag ist exklusiv
			} else {
				$bis = '00:00' === $e[2]->format( 'H:i' ) ? ( clone $e[2] )->modify( '-1 minute' )->format( 'Y-m-d' ) : $e[0];
			}
		}
		$bis = $bis < $von ? $von : $bis;
		if ( $bis < $min || $von > $max ) {
			continue;
		}
		$out[] = array( 'von' => $von, 'bis' => $bis, 'titel' => sanitize_text_field( $t ? $t : 'Privat' ) );
	}
	return $out;
}
function kjo_kal_quellen() {
	$q = array();
	foreach ( array( 1, 2, 3 ) as $i ) {
		$u = trim( (string) kjo_kal_opt( 'url' . $i ) );
		if ( $u ) {
			$q[ $i ] = array( 'url' => $u, 'ganztag' => '1' === kjo_kal_opt( 'ganztag' . $i, '0' ), 'name' => kjo_kal_opt( 'name' . $i, 'Quelle ' . $i ) );
		}
	}
	return $q;
}
/** Alle Quellen abrufen; schlägt eine Quelle fehl, bleiben ihre bisherigen Einträge erhalten. */
function kjo_kal_import() {
	$old = (array) get_option( 'kjo_kal_import', array() );
	$new = array();
	$log = array();
	foreach ( kjo_kal_quellen() as $i => $q ) {
		$url = preg_replace( '#^webcal://#i', 'https://', $q['url'] );
		$r   = wp_remote_get( $url, array( 'timeout' => 20 ) );
		$ok  = ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) && false !== strpos( (string) wp_remote_retrieve_body( $r ), 'BEGIN:VCALENDAR' );
		if ( ! $ok ) {
			foreach ( $old as $e ) {
				if ( (string) $e['quelle'] === (string) $i ) {
					$new[] = $e;
				}
			}
			$log[ $i ] = 'Fehler: ' . ( is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $r ) . ' – Adresse prüfen' );
			continue;
		}
		$ev = kjo_kal_parse_ics( wp_remote_retrieve_body( $r ), $q['ganztag'] );
		foreach ( $ev as $e ) {
			$e['quelle'] = (string) $i;
			$new[]       = $e;
		}
		$log[ $i ] = count( $ev ) . ' Termine übernommen';
	}
	update_option( 'kjo_kal_import', $new, false );
	update_option( 'kjo_kal_import_log', array( 'zeit' => time(), 'log' => $log ), false );
	return $log;
}
add_action( 'kjo_kal_import_cron', 'kjo_kal_import' );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'kjo_kal_import_cron' ) ) {
			wp_schedule_event( time() + 120, 'hourly', 'kjo_kal_import_cron' );
		}
	}
);

function kjo_kal_sync_page() {
	if ( isset( $_POST['kjo_kal_save'] ) && check_admin_referer( 'kjo_kal_sync' ) && current_user_can( 'manage_options' ) ) {
		$o = get_option( 'kjo_kalender', array() );
		foreach ( array( 1, 2, 3 ) as $i ) {
			$o[ 'url' . $i ]     = esc_url_raw( trim( (string) wp_unslash( isset( $_POST[ 'url' . $i ] ) ? $_POST[ 'url' . $i ] : '' ) ), array( 'https', 'http', 'webcal' ) ); // phpcs:ignore
			$o[ 'name' . $i ]    = sanitize_text_field( wp_unslash( isset( $_POST[ 'name' . $i ] ) ? $_POST[ 'name' . $i ] : '' ) ); // phpcs:ignore
			$o[ 'ganztag' . $i ] = empty( $_POST[ 'ganztag' . $i ] ) ? '0' : '1';
		}
		$o['notizen'] = empty( $_POST['notizen'] ) ? '0' : '1';
		if ( ! empty( $_POST['neu_token'] ) ) {
			$o['token'] = wp_generate_password( 32, false, false );
		}
		update_option( 'kjo_kalender', $o, false );
		$log = kjo_kal_import();
		echo '<div class="notice notice-success"><p>Gespeichert und abgerufen: ' . esc_html( implode( ' · ', $log ? $log : array( 'keine Quellen' ) ) ) . '</p></div>';
	}
	$feed = kjo_kal_feed_url();
	$st   = get_option( 'kjo_kal_import_log', array() );
	echo '<div class="wrap"><h1>Kalender – Abo &amp; Import</h1><form method="post">';
	wp_nonce_field( 'kjo_kal_sync' );
	echo '<h2>1. Abo der Website-Termine (für Google, iPhone, Outlook)</h2>';
	echo '<p><input type="text" readonly class="large-text code" value="' . esc_attr( $feed ) . '" onclick="this.select()"> <button type="button" class="button" onclick="navigator.clipboard.writeText(\'' . esc_js( $feed ) . '\');this.textContent=\'Kopiert ✓\'">Abo-Link kopieren</button></p>';
	echo '<p class="description">Google Kalender: links „Weitere Kalender“ → „+“ → „Per URL“ → Link einfügen. iPhone: Einstellungen → Kalender → Accounts → Account hinzufügen → Andere → Kalenderabo. Der Link ist geheim – wer ihn hat, sieht alle Termine.</p>';
	echo '<p><label><input type="checkbox" name="notizen" value="1" ' . checked( '0' !== kjo_kal_opt( 'notizen', '1' ), true, false ) . '> Notizen (Kunde, Ort) im Abo zeigen</label> &nbsp; <label><input type="checkbox" name="neu_token" value="1"> neuen geheimen Link erzeugen (alter Link funktioniert dann nicht mehr)</label></p>';
	echo '<h2>2. Import: Diese Kalender blockieren DJ-Termine (Equipment bleibt frei)</h2><table class="form-table">';
	foreach ( array( 1, 2, 3 ) as $i ) {
		echo '<tr><th>Quelle ' . (int) $i . '</th><td><input type="text" name="name' . (int) $i . '" value="' . esc_attr( kjo_kal_opt( 'name' . $i, 1 === $i ? 'Google DJ-Blocker' : '' ) ) . '" placeholder="Name, z. B. Outlook privat" class="regular-text"><br><input type="url" name="url' . (int) $i . '" value="' . esc_attr( kjo_kal_opt( 'url' . $i ) ) . '" placeholder="https://calendar.google.com/calendar/ical/…/private-…/basic.ics" class="large-text code"><br><label><input type="checkbox" name="ganztag' . (int) $i . '" value="1" ' . checked( '1', kjo_kal_opt( 'ganztag' . $i, '0' ), false ) . '> nur ganztägige Termine übernehmen</label></td></tr>';
	}
	echo '</table><p class="description">Google „DJ-Blocker“: Kalender-Einstellungen → „Kalender integrieren“ → <strong>„Privatadresse im iCal-Format“</strong> (nicht die öffentliche Adresse) kopieren und oben bei Quelle 1 einfügen. Abruf automatisch stündlich.</p>';
	if ( $st ) {
		echo '<p>Letzter Abruf: ' . esc_html( wp_date( 'd.m.Y, H:i', (int) $st['zeit'] ) ) . ' Uhr – ' . esc_html( implode( ' · ', (array) $st['log'] ) ) . '</p>';
	}
	submit_button( 'Speichern und jetzt abrufen', 'primary', 'kjo_kal_save' );
	echo '</form></div>';
}

/* ---------------------------------------------------------------
 * Frontend: Hinweistexte (Wunschtermin-Formular + Mietkorb)
 * ------------------------------------------------------------- */
add_action(
	'wp_footer',
	function () {
		if ( is_admin() ) {
			return;
		}
		echo '<script id="kjo-kal-js">window.KJO_KAL={api:' . wp_json_encode( esc_url_raw( rest_url( 'kjo/v1/verfuegbar' ) ) ) . ',mieten:' . wp_json_encode( home_url( '/technik-mieten/' ) ) . '};</script>';
		echo '<style id="kjo-kal-css">.kjo-av{margin:10px 0 0;padding:10px 14px;border-left:3px solid #B29D75;background:rgba(178,157,117,.08);color:#F3F1E9;font:15px/1.55 "Fira Sans",sans-serif}.kjo-av strong{font-weight:500}.kjo-av a{color:#B29D75;text-decoration:underline}.kjo-av.ok{border-color:#7fb77e}.kjo-av.vor{border-color:#E0B36A}.kjo-av.no{border-color:#ff9b8a}.kjo-vl-cart .st{display:block;font-size:12px;color:#A39E93}.kjo-vl-cart .st.vor{color:#E0B36A}.kjo-vl-cart .st.belegt{color:#ff9b8a}</style>';
	},
	32
);
