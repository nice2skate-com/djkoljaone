<?php
/**
 * Musikwunschliste (seit 1.53.0)
 *
 * Admin „Musikwunschlisten“: Liste für ein Paar / einen Gastgeber anlegen (Name, Datum, Art) → persönlicher Link.
 * Das Paar füllt auf einer schlanken Seite (?musikliste=TOKEN) besondere Momente, Lieblingssongs, No-Gos und
 * Musikrichtungen aus; Zwischenstände werden gespeichert, „An Kolja schicken“ meldet die fertige Liste per E-Mail.
 * Listen werden 90 Tage nach dem Feiertermin automatisch gelöscht.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_ML_TAGE = 90; // Löschung nach dem Termin
const KJO_ML_MAIL = 'anfrage@dj-kolja-one.de';

/** Felder je Art: Schlüssel => array( Überschrift, Hilfetext ) */
function kjo_ml_momente( $art ) {
	if ( 'hochzeit' === $art ) {
		return array(
			'einzug'  => array( 'Einzug zur Trauung', 'Der Song, zu dem ihr (oder die Braut) einzieht' ),
			'ringe'   => array( 'Ringtausch / Unterschrift', 'Leise im Hintergrund, falls gewünscht' ),
			'auszug'  => array( 'Auszug nach der Trauung', 'Der erste Song als Ehepaar' ),
			'saal'    => array( 'Einzug in den Saal', 'Wenn ihr zum Essen oder zur Party einzieht' ),
			'tanz'    => array( 'Eröffnungstanz', 'Gern mit Hinweis, ob gekürzt oder mit Übergang zur Party' ),
			'torte'   => array( 'Tortenanschnitt', '' ),
			'letzter' => array( 'Letzter Song des Abends', '' ),
		);
	}
	return array(
		'einzug'  => array( 'Einzug / Begrüßung', 'Ein Song für euren Auftritt' ),
		'erster'  => array( 'Erster Song auf der Tanzfläche', '' ),
		'torte'   => array( 'Torte, Rede oder Überraschung', '' ),
		'letzter' => array( 'Letzter Song des Abends', '' ),
	);
}
const KJO_ML_GENRES = array( 'Party-Klassiker', 'Aktuelle Charts', '80er & 90er', '2000er & 2010er', 'Schlager', 'Deutschpop & Deutschrap', 'Rock & Pop-Rock', 'Hip-Hop & R&B', 'House & Dance', 'Latin & Reggaeton', 'Disco & Funk', 'Oldies & Rock ’n’ Roll' );
const KJO_ML_WAHL   = array( 'gern' => 'gern', 'egal' => 'passt', 'nein' => 'bitte nicht' );

add_action(
	'init',
	function () {
		register_post_type( 'kjo_musikliste', array( 'public' => false, 'show_ui' => false, 'supports' => array( 'title' ) ) );
		if ( ! wp_next_scheduled( 'kjo_ml_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kjo_ml_cron' );
		}
	}
);
add_action(
	'kjo_ml_cron',
	function () {
		$grenze = gmdate( 'Y-m-d', time() - KJO_ML_TAGE * DAY_IN_SECONDS );
		foreach ( get_posts( array( 'post_type' => 'kjo_musikliste', 'post_status' => 'private', 'numberposts' => 200, 'fields' => 'ids', 'meta_key' => '_kjo_ml_datum', 'meta_value' => $grenze, 'meta_compare' => '<' ) ) as $id ) { // phpcs:ignore
			wp_delete_post( $id, true );
		}
	}
);

function kjo_ml_by_token( $t ) {
	$t = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $t ) );
	if ( strlen( $t ) < 16 ) {
		return null;
	}
	$p = get_posts( array( 'post_type' => 'kjo_musikliste', 'post_status' => 'private', 'numberposts' => 1, 'meta_key' => '_kjo_ml_token', 'meta_value' => $t ) ); // phpcs:ignore
	return $p ? $p[0] : null;
}
function kjo_ml_url( $id ) {
	return add_query_arg( 'musikliste', get_post_meta( $id, '_kjo_ml_token', true ), home_url( '/' ) );
}
function kjo_ml_daten( $id ) {
	$d = get_post_meta( $id, '_kjo_ml_daten', true );
	return is_array( $d ) ? $d : array();
}
/** Bereinigt die Eingaben des Paares auf bekannte Felder. */
function kjo_ml_clean( $in, $art ) {
	$in  = is_array( $in ) ? $in : array();
	$s   = function ( $v, $len ) {
		return mb_substr( trim( sanitize_textarea_field( (string) $v ) ), 0, $len );
	};
	$out = array( 'm' => array(), 'g' => array() );
	foreach ( array_keys( kjo_ml_momente( $art ) ) as $k ) {
		$out['m'][ $k ] = $s( $in['m'][ $k ] ?? '', 200 );
	}
	foreach ( KJO_ML_GENRES as $i => $g ) {
		$v              = (string) ( $in['g'][ $i ] ?? '' );
		$out['g'][ $i ] = isset( KJO_ML_WAHL[ $v ] ) ? $v : '';
	}
	foreach ( array( 'mehr' => 600, 'fav' => 4000, 'nogo' => 2000, 'gaeste' => 1500, 'notiz' => 2000 ) as $k => $len ) {
		$out[ $k ] = $s( $in[ $k ] ?? '', $len );
	}
	return $out;
}

/* ---------- REST: speichern / abschicken ---------- */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/musikliste',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'kjo_ml_rest',
			)
		);
	}
);
function kjo_ml_rest( WP_REST_Request $r ) {
	$d = $r->get_json_params();
	$d = is_array( $d ) ? $d : array();
	$p = kjo_ml_by_token( $d['t'] ?? '' );
	if ( ! $p ) {
		return new WP_REST_Response( array( 'message' => 'Diese Liste gibt es nicht mehr. Bitte meldet euch bei mir.' ), 404 );
	}
	$art   = (string) get_post_meta( $p->ID, '_kjo_ml_art', true );
	$daten = kjo_ml_clean( $d['d'] ?? array(), $art );
	update_post_meta( $p->ID, '_kjo_ml_daten', $daten );
	update_post_meta( $p->ID, '_kjo_ml_t', time() );
	$fertig = ! empty( $d['fertig'] );
	if ( $fertig ) {
		update_post_meta( $p->ID, '_kjo_ml_s', 'fertig' );
		$link = admin_url( 'admin.php?page=kjo-musikliste&id=' . $p->ID );
		wp_mail( KJO_ML_MAIL, 'Musikwunschliste fertig: ' . $p->post_title, "Die Musikwunschliste „" . $p->post_title . "“ wurde abgeschickt.\n\nAnsehen: " . $link . "\n", array( 'Content-Type: text/plain; charset=UTF-8' ) );
	} elseif ( 'fertig' !== get_post_meta( $p->ID, '_kjo_ml_s', true ) ) {
		update_post_meta( $p->ID, '_kjo_ml_s', 'bearbeitet' );
	}
	return new WP_REST_Response( array( 'ok' => true, 'fertig' => $fertig ), 200 );
}

/* ---------- Seite für das Paar ---------- */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['musikliste'] ) ) { // phpcs:ignore
			return;
		}
		$p = kjo_ml_by_token( sanitize_text_field( wp_unslash( $_GET['musikliste'] ) ) ); // phpcs:ignore
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'Referrer-Policy: no-referrer', true );
		$art   = $p ? (string) get_post_meta( $p->ID, '_kjo_ml_art', true ) : '';
		$d     = $p ? kjo_ml_daten( $p->ID ) : array();
		$datum = $p ? (string) get_post_meta( $p->ID, '_kjo_ml_datum', true ) : '';
		$logo  = plugins_url( 'assets/logo-voll.png', __FILE__ );
		$e     = function ( $k, $sub = null ) use ( $d ) {
			$v = null === $sub ? ( $d[ $k ] ?? '' ) : ( $d[ $k ][ $sub ] ?? '' );
			return esc_textarea( (string) $v );
		};
		?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0F0C07">
<title>Musikwunschliste · DJ KOLJA ONE</title><link rel="icon" href="<?php echo esc_url( home_url( '/favicon.ico' ) ); ?>">
<style>
:root{--g:#B29D75;--o:#F3F1E9;--m:#A39E93;--b:#1A1712}*{box-sizing:border-box}[hidden]{display:none!important}html,body{margin:0;background:#0F0C07;color:var(--o);font:16px/1.5 "Fira Sans",system-ui,-apple-system,sans-serif}
main{max-width:640px;margin:0 auto;padding:28px 20px 120px}.top{text-align:center}.top img{display:block;width:280px;max-width:80%;height:auto;margin:6px auto 22px}
.brand{letter-spacing:6px;font-size:13px;color:var(--g);text-transform:uppercase;margin:12px 0 2px}h1{font-weight:300;font-size:30px;margin:6px 0 4px;line-height:1.2}.ev{color:var(--m);margin:0 0 6px}
.intro{color:var(--m);text-align:center;margin:0 0 26px}
fieldset{border:0;border-top:2px solid var(--g);background:var(--b);padding:22px 18px;margin:0 0 22px}legend{float:left;width:100%;padding:0;font-size:22px;font-weight:300;margin:0 0 4px}legend+*{clear:both}
.hint{color:var(--m);font-size:14px;margin:0 0 14px}
label{display:block;font-size:13px;letter-spacing:1.2px;text-transform:uppercase;color:var(--g);margin:0 0 14px}label small{display:block;text-transform:none;letter-spacing:0;color:var(--m);font-size:13px}
input,textarea{width:100%;margin-top:6px;padding:12px 13px;background:#0F0C07;border:1px solid rgba(178,157,117,.45);border-radius:3px;color:var(--o);font:16px/1.45 "Fira Sans",sans-serif}textarea{min-height:96px;resize:vertical}
input:focus,textarea:focus{outline:2px solid var(--g);outline-offset:1px}
.gr{display:grid;grid-template-columns:1fr auto;gap:8px 12px;align-items:center}.gr span{font-size:15px}
.seg{display:inline-flex;border:1px solid rgba(178,157,117,.5);border-radius:999px;overflow:hidden}.seg label{margin:0;text-transform:none;letter-spacing:0;color:var(--m);font-size:13px;cursor:pointer}
.seg input{position:absolute;opacity:0;width:1px;height:1px;margin:0}.seg span{display:block;padding:7px 11px;font-size:13px}.seg input:checked+span{background:var(--g);color:#0F0C07}.seg input.n:checked+span{background:#7a4b3e;color:var(--o)}
.seg input:focus-visible+span{outline:2px solid var(--o);outline-offset:-2px}
.bar{position:fixed;left:0;right:0;bottom:0;background:rgba(15,12,7,.96);border-top:1px solid rgba(178,157,117,.35);padding:12px 20px calc(12px + env(safe-area-inset-bottom))}
.bar div{max-width:640px;margin:0 auto;display:flex;gap:10px;align-items:center}.st{flex:1;font-size:13px;color:var(--m)}
button{padding:13px 16px;background:var(--g);color:#0F0C07;border:0;border-radius:3px;font:500 14px "Fira Sans",sans-serif;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer}button.s{background:none;border:1px solid var(--g);color:var(--g)}button:disabled{opacity:.6}
.ok{border:1px solid var(--g);padding:18px;text-align:center;margin:0 0 22px}.ok h2{font-weight:300;margin:0 0 6px}.ok p{margin:0;color:var(--m)}
.small{font-size:12px;color:var(--m);text-align:center;margin-top:26px}.small a{color:var(--m)}
@media(max-width:420px){.gr{grid-template-columns:1fr}.gr .seg{margin-bottom:8px}}
</style></head><body><main>
<div class="top"><img src="<?php echo esc_url( $logo ); ?>" alt="DJ KOLJA ONE – Jedes Event findet nur einmal statt." width="280" height="51">
<?php if ( ! $p ) : ?>
<h1>Diese Liste gibt es nicht (mehr)</h1><p class="ev">Bitte prüft den Link oder meldet euch kurz bei mir.</p></div>
<?php else : ?>
<h1>Eure Musikwunschliste</h1><p class="ev"><?php echo esc_html( $p->post_title ); ?><?php echo $datum ? ' · ' . esc_html( wp_date( 'd.m.Y', strtotime( $datum . ' 12:00' ) ) ) : ''; ?></p></div>
<p class="intro">Füllt aus, was euch wichtig ist – nichts davon ist Pflicht. Ihr könnt jederzeit speichern und später weitermachen; der Link bleibt gleich.</p>
<div class="ok" id="ok"<?php echo 'fertig' === get_post_meta( $p->ID, '_kjo_ml_s', true ) ? '' : ' hidden'; ?>><h2>Danke, eure Liste ist bei mir! 🎶</h2><p>Ihr könnt trotzdem noch Änderungen machen – einfach erneut abschicken.</p></div>
<form id="f" novalidate>
<fieldset><legend>Besondere Momente</legend><p class="hint">Songtitel und Interpret, gern mit Link (z. B. Spotify oder YouTube).</p>
<?php foreach ( kjo_ml_momente( $art ) as $k => $m ) : ?>
<label><?php echo esc_html( $m[0] ); ?><?php echo $m[1] ? '<small>' . esc_html( $m[1] ) . '</small>' : ''; ?><input name="m.<?php echo esc_attr( $k ); ?>" maxlength="200" value="<?php echo $e( 'm', $k ); // phpcs:ignore ?>" autocomplete="off"></label>
<?php endforeach; ?>
<label>Weitere Momente<small>Spiele, Überraschungen, Reden mit Musik …</small><textarea name="mehr" maxlength="600"><?php echo $e( 'mehr' ); // phpcs:ignore ?></textarea></label>
</fieldset>
<fieldset><legend>Musikrichtungen</legend><p class="hint">Was soll auf der Tanzfläche laufen?</p><div class="gr">
<?php foreach ( KJO_ML_GENRES as $i => $g ) : ?>
<span><?php echo esc_html( $g ); ?></span><span class="seg" role="radiogroup" aria-label="<?php echo esc_attr( $g ); ?>">
	<?php foreach ( KJO_ML_WAHL as $v => $t ) : ?>
<label><input type="radio" name="g.<?php echo (int) $i; ?>" value="<?php echo esc_attr( $v ); ?>"<?php echo 'nein' === $v ? ' class="n"' : ''; ?><?php checked( ( $d['g'][ $i ] ?? '' ), $v ); ?>><span><?php echo esc_html( $t ); ?></span></label>
	<?php endforeach; ?>
</span>
<?php endforeach; ?>
</div></fieldset>
<fieldset><legend>Lieblingssongs</legend><label>Unbedingt spielen<small>Ein Song pro Zeile, z. B. „Mr. Brightside – The Killers“</small><textarea name="fav" maxlength="4000" style="min-height:160px"><?php echo $e( 'fav' ); // phpcs:ignore ?></textarea></label>
<label>Bitte auf keinen Fall<small>No-Gos, ebenfalls ein Song oder Stil pro Zeile</small><textarea name="nogo" maxlength="2000"><?php echo $e( 'nogo' ); // phpcs:ignore ?></textarea></label></fieldset>
<fieldset><legend>Eure Gäste</legend><label>Wer feiert mit?<small>Alter, Herkunft, wer tanzt gern – alles, was mir hilft, die Stimmung zu lesen</small><textarea name="gaeste" maxlength="1500"><?php echo $e( 'gaeste' ); // phpcs:ignore ?></textarea></label>
<label>Sonst noch etwas?<textarea name="notiz" maxlength="2000"><?php echo $e( 'notiz' ); // phpcs:ignore ?></textarea></label></fieldset>
</form>
<div class="bar"><div><span class="st" id="st" role="status"></span><button type="button" class="s" id="sv">Speichern</button><button type="button" id="go">An Kolja schicken</button></div></div>
<script>(function(){var f=document.getElementById("f"),st=document.getElementById("st"),sv=document.getElementById("sv"),go=document.getElementById("go"),ok=document.getElementById("ok"),dirty=false,busy=false,tm=null;
function data(){var d={m:{},g:{}};Array.prototype.forEach.call(f.elements,function(el){if(!el.name)return;var p=el.name.split(".");if(el.type==="radio"){if(!el.checked)return}if(p.length>1)d[p[0]][p[1]]=el.value;else d[el.name]=el.value});return d}
function save(fertig){if(busy)return;busy=true;sv.disabled=go.disabled=true;st.textContent="wird gespeichert …";
 fetch(<?php echo wp_json_encode( esc_url_raw( rest_url( 'kjo/v1/musikliste' ) ) ); ?>,{method:"POST",headers:{"Content-Type":"application/json"},credentials:"omit",body:JSON.stringify({t:<?php echo wp_json_encode( get_post_meta( $p->ID, '_kjo_ml_token', true ) ); ?>,d:data(),fertig:fertig?1:0})})
 .then(function(r){return r.json().then(function(j){return{ok:r.ok,j:j}})}).then(function(x){busy=false;sv.disabled=go.disabled=false;
  if(!x.ok){st.textContent=(x.j&&x.j.message)||"Speichern hat nicht geklappt.";return}dirty=false;
  st.textContent="Gespeichert "+new Date().toLocaleTimeString("de-DE",{hour:"2-digit",minute:"2-digit"});
  if(fertig){ok.hidden=false;ok.scrollIntoView({behavior:"smooth",block:"center"})}})
 .catch(function(){busy=false;sv.disabled=go.disabled=false;st.textContent="Keine Verbindung – bitte noch einmal speichern."})}
f.addEventListener("input",function(){dirty=true;st.textContent="Nicht gespeichert";clearTimeout(tm);tm=setTimeout(function(){if(dirty)save(false)},4000)});
f.addEventListener("change",function(){dirty=true;clearTimeout(tm);tm=setTimeout(function(){if(dirty)save(false)},1500)});
sv.onclick=function(){save(false)};go.onclick=function(){clearTimeout(tm);save(true)};
window.addEventListener("beforeunload",function(e){if(dirty){e.preventDefault();e.returnValue=""}});})();</script>
<?php endif; ?>
<p class="small">Eure Angaben nutze ich nur für die Musikplanung eurer Feier und lösche sie <?php echo (int) KJO_ML_TAGE; ?> Tage danach. <a href="<?php echo esc_url( home_url( '/datenschutz/' ) ); ?>">Datenschutz</a> · <a href="<?php echo esc_url( home_url( '/impressum/' ) ); ?>">Impressum</a></p>
</main></body></html>
		<?php
		exit;
	},
	1
);

/* ---------- Admin ---------- */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Musikwunschlisten', 'Musikwunschlisten', 'edit_pages', 'kjo-musikliste', 'kjo_ml_page', 'dashicons-format-audio', 27 );
	}
);
function kjo_ml_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$msg = '';
	if ( isset( $_POST['kjo_ml_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_ml_nonce'] ) ), 'kjo_ml' ) ) {
		if ( isset( $_POST['neu'] ) ) {
			$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore
			$datum = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $_POST['datum'] ?? '' ) ) ? sanitize_text_field( wp_unslash( $_POST['datum'] ) ) : ''; // phpcs:ignore
			$art   = 'hochzeit' === ( $_POST['art'] ?? '' ) ? 'hochzeit' : 'feier'; // phpcs:ignore
			if ( '' === $name || '' === $datum ) {
				$msg = '<div class="notice notice-error"><p>Bitte Name und Datum angeben.</p></div>';
			} else {
				$id = wp_insert_post( array( 'post_type' => 'kjo_musikliste', 'post_status' => 'private', 'post_title' => $name ) );
				if ( $id && ! is_wp_error( $id ) ) {
					update_post_meta( $id, '_kjo_ml_token', strtolower( wp_generate_password( 24, false, false ) ) );
					update_post_meta( $id, '_kjo_ml_datum', $datum );
					update_post_meta( $id, '_kjo_ml_art', $art );
					update_post_meta( $id, '_kjo_ml_s', 'neu' );
					$msg = '<div class="notice notice-success"><p>Liste angelegt. Schick den Link unten an das Paar.</p></div>';
				}
			}
		} elseif ( isset( $_POST['del'] ) ) {
			$id = absint( $_POST['del'] );
			if ( 'kjo_musikliste' === get_post_type( $id ) ) {
				wp_delete_post( $id, true );
				$msg = '<div class="notice notice-success"><p>Liste gelöscht.</p></div>';
			}
		}
	}
	$view = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
	if ( $view && 'kjo_musikliste' === get_post_type( $view ) ) {
		kjo_ml_ansicht( $view );
		return;
	}
	$nf = wp_nonce_field( 'kjo_ml', 'kjo_ml_nonce', true, false );
	echo '<div class="wrap"><h1>Musikwunschlisten</h1>' . $msg; // phpcs:ignore
	echo '<p>Leg für jedes Paar bzw. jeden Gastgeber eine Liste an und schick den persönlichen Link (z. B. per WhatsApp). Dort tragen sie besondere Momente, Lieblingssongs, No-Gos und Musikrichtungen ein. Wird die Liste abgeschickt, bekommst du eine E-Mail. Listen werden ' . (int) KJO_ML_TAGE . ' Tage nach dem Termin automatisch gelöscht.</p>';
	echo '<form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin:16px 0 24px">' . $nf; // phpcs:ignore
	echo '<label>Name<br><input name="name" class="regular-text" placeholder="z. B. Anna &amp; Ben" required></label><label>Datum der Feier<br><input type="date" name="datum" required></label>';
	echo '<label>Art<br><select name="art"><option value="hochzeit">Hochzeit</option><option value="feier">Geburtstag / Feier / Firma</option></select></label><button class="button button-primary" name="neu" value="1">Liste anlegen</button></form>';
	$alle = get_posts( array( 'post_type' => 'kjo_musikliste', 'post_status' => 'private', 'numberposts' => 200, 'meta_key' => '_kjo_ml_datum', 'orderby' => 'meta_value', 'order' => 'ASC' ) ); // phpcs:ignore
	$sn   = array( 'neu' => 'noch nicht geöffnet', 'bearbeitet' => 'in Arbeit', 'fertig' => '✓ abgeschickt' );
	echo '<table class="widefat striped"><thead><tr><th>Feier</th><th>Datum</th><th>Stand</th><th>Link für das Paar</th><th></th></tr></thead><tbody>';
	if ( ! $alle ) {
		echo '<tr><td colspan="5">Noch keine Listen.</td></tr>';
	}
	foreach ( $alle as $p ) {
		$s   = (string) get_post_meta( $p->ID, '_kjo_ml_s', true );
		$t   = (int) get_post_meta( $p->ID, '_kjo_ml_t', true );
		$url = kjo_ml_url( $p->ID );
		echo '<tr><td><strong><a href="' . esc_url( admin_url( 'admin.php?page=kjo-musikliste&id=' . $p->ID ) ) . '">' . esc_html( $p->post_title ) . '</a></strong><br><small>' . ( 'hochzeit' === get_post_meta( $p->ID, '_kjo_ml_art', true ) ? 'Hochzeit' : 'Feier' ) . '</small></td>';
		echo '<td>' . esc_html( wp_date( 'd.m.Y', strtotime( get_post_meta( $p->ID, '_kjo_ml_datum', true ) . ' 12:00' ) ) ) . '</td>';
		echo '<td>' . esc_html( $sn[ $s ] ?? $s ) . ( $t ? '<br><small>zuletzt ' . esc_html( wp_date( 'd.m. H:i', $t ) ) . '</small>' : '' ) . '</td>';
		echo '<td><input type="text" readonly value="' . esc_attr( $url ) . '" style="width:100%;max-width:340px" onclick="this.select()"> <button type="button" class="button" onclick="var b=this;navigator.clipboard.writeText(b.previousElementSibling.value).then(function(){b.textContent=\'kopiert ✓\'})">Kopieren</button> <a class="button" target="_blank" href="https://wa.me/?text=' . rawurlencode( 'Hier ist eure Musikwunschliste – tragt ein, was euch wichtig ist: ' . $url ) . '">WhatsApp</a></td>';
		echo '<td><a class="button" href="' . esc_url( admin_url( 'admin.php?page=kjo-musikliste&id=' . $p->ID ) ) . '">Ansehen</a> <form method="post" style="display:inline" onsubmit="return confirm(\'Liste endgültig löschen?\')">' . $nf . '<button class="button-link-delete" name="del" value="' . (int) $p->ID . '">Löschen</button></form></td></tr>'; // phpcs:ignore
	}
	echo '</tbody></table></div>';
}
function kjo_ml_ansicht( $id ) {
	$p   = get_post( $id );
	$art = (string) get_post_meta( $id, '_kjo_ml_art', true );
	$d   = kjo_ml_daten( $id );
	$z   = function ( $t ) {
		return '' === trim( (string) $t ) ? '<span style="color:#8c8f94">–</span>' : nl2br( esc_html( $t ) );
	};
	echo '<div class="wrap kjo-ml"><style>.kjo-ml h2{margin-top:28px;border-bottom:2px solid #B29D75;padding-bottom:4px}.kjo-ml table{max-width:900px}.kjo-ml th{width:240px;text-align:left}@media print{#adminmenumain,#wpadminbar,#wpfooter,.kjo-ml .noprint,.notice{display:none!important}#wpcontent{margin:0!important;padding:0!important}}</style>';
	echo '<p class="noprint"><a href="' . esc_url( admin_url( 'admin.php?page=kjo-musikliste' ) ) . '">← Alle Listen</a> · <a href="#" onclick="window.print();return false">Drucken</a> · <a target="_blank" href="' . esc_url( kjo_ml_url( $id ) ) . '">Ansicht des Paares öffnen</a></p>';
	echo '<h1>' . esc_html( $p->post_title ) . ' · ' . esc_html( wp_date( 'd.m.Y', strtotime( get_post_meta( $id, '_kjo_ml_datum', true ) . ' 12:00' ) ) ) . '</h1>';
	echo '<h2>Besondere Momente</h2><table class="widefat striped"><tbody>';
	foreach ( kjo_ml_momente( $art ) as $k => $m ) {
		echo '<tr><th>' . esc_html( $m[0] ) . '</th><td>' . $z( $d['m'][ $k ] ?? '' ) . '</td></tr>'; // phpcs:ignore
	}
	echo '<tr><th>Weitere Momente</th><td>' . $z( $d['mehr'] ?? '' ) . '</td></tr></tbody></table>'; // phpcs:ignore
	$gr = array( 'gern' => array(), 'egal' => array(), 'nein' => array() );
	foreach ( KJO_ML_GENRES as $i => $g ) {
		if ( ! empty( $d['g'][ $i ] ) ) {
			$gr[ $d['g'][ $i ] ][] = $g;
		}
	}
	echo '<h2>Musikrichtungen</h2><table class="widefat striped"><tbody><tr><th>Gern</th><td>' . $z( implode( ', ', $gr['gern'] ) ) . '</td></tr><tr><th>Passt</th><td>' . $z( implode( ', ', $gr['egal'] ) ) . '</td></tr><tr><th>Bitte nicht</th><td>' . $z( implode( ', ', $gr['nein'] ) ) . '</td></tr></tbody></table>'; // phpcs:ignore
	echo '<h2>Songs</h2><table class="widefat striped"><tbody><tr><th>Unbedingt spielen</th><td>' . $z( $d['fav'] ?? '' ) . '</td></tr><tr><th>Bitte auf keinen Fall</th><td>' . $z( $d['nogo'] ?? '' ) . '</td></tr></tbody></table>'; // phpcs:ignore
	echo '<h2>Gäste &amp; Sonstiges</h2><table class="widefat striped"><tbody><tr><th>Wer feiert mit?</th><td>' . $z( $d['gaeste'] ?? '' ) . '</td></tr><tr><th>Sonst noch etwas?</th><td>' . $z( $d['notiz'] ?? '' ) . '</td></tr></tbody></table></div>'; // phpcs:ignore
}
