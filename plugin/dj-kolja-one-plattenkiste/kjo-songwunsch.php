<?php
/**
 * Songwünsche per QR-Code (seit 1.52.0)
 *
 * Admin „Songwünsche“: Abend starten (Name, z. B. „Hochzeit Anna & Ben“) → QR-Code für die Tische.
 * Gäste scannen, landen auf einer schlanken Seite (?songwunsch=CODE) und schicken Titel/Interpret, optional ihren Vornamen.
 * Gleiche Wünsche werden zusammengezählt („3× gewünscht“). Live-Liste für den DJ (aktualisiert sich alle 10 Sekunden),
 * „gespielt“ / „ausblenden“ per Klick. Wünsche werden nach 14 Tagen automatisch gelöscht, IP-Adressen nie gespeichert.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_SW_TAGE = 14;  // Aufbewahrung der Wünsche
const KJO_SW_MAX  = 20;  // Wünsche pro Anschluss und 10 Minuten (Gäste im Location-WLAN teilen sich eine IP)

add_action(
	'init',
	function () {
		register_post_type( 'kjo_wunsch', array( 'public' => false, 'show_ui' => false, 'supports' => array( 'title' ) ) );
	}
);

/** Aktiver Abend: array( code, name, start ) oder null. Endet automatisch nach 18 Stunden. */
function kjo_sw_abend() {
	$a = get_option( 'kjo_sw_abend', array() );
	if ( ! is_array( $a ) || empty( $a['code'] ) ) {
		return null;
	}
	if ( time() - (int) $a['start'] > 18 * HOUR_IN_SECONDS ) {
		delete_option( 'kjo_sw_abend' );
		return null;
	}
	return $a;
}
function kjo_sw_url( $code ) {
	return add_query_arg( 'songwunsch', $code, home_url( '/' ) );
}
function kjo_sw_norm( $s ) {
	$s = function_exists( 'mb_strtolower' ) ? mb_strtolower( $s ) : strtolower( $s );
	return trim( preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $s ) );
}
function kjo_sw_aufraeumen() {
	$alt = get_posts(
		array(
			'post_type'   => 'kjo_wunsch',
			'post_status' => 'any',
			'numberposts' => 500,
			'fields'      => 'ids',
			'date_query'  => array( array( 'before' => KJO_SW_TAGE . ' days ago' ) ),
		)
	);
	foreach ( $alt as $id ) {
		wp_delete_post( $id, true );
	}
}
add_action( 'kjo_sw_cron', 'kjo_sw_aufraeumen' );
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'kjo_sw_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kjo_sw_cron' );
		}
	}
);

/* ---------- Gäste: Wunsch abschicken ---------- */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kjo/v1',
			'/songwunsch',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'kjo_sw_rest',
			)
		);
	}
);
function kjo_sw_rest( WP_REST_Request $r ) {
	$d = $r->get_json_params();
	$d = is_array( $d ) ? $d : array();
	$s = function ( $k, $len ) use ( $d ) {
		return isset( $d[ $k ] ) ? mb_substr( trim( sanitize_text_field( (string) $d[ $k ] ) ), 0, $len ) : '';
	};
	$a = kjo_sw_abend();
	if ( ! $a || ! hash_equals( $a['code'], $s( 'e', 40 ) ) ) {
		return new WP_REST_Response( array( 'message' => 'Die Wunschliste ist gerade geschlossen.' ), 403 );
	}
	if ( '' !== $s( 'website', 50 ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 ); // Spam-Falle
	}
	$titel = $s( 'titel', 120 );
	$kuen  = $s( 'kuenstler', 120 );
	$name  = $s( 'name', 40 );
	if ( mb_strlen( $titel ) < 2 ) {
		return new WP_REST_Response( array( 'message' => 'Bitte gib einen Songtitel ein.' ), 400 );
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk = 'kjo_sw_' . substr( hash_hmac( 'sha256', $ip . $a['code'], wp_salt( 'nonce' ) ), 0, 20 ); // nur Prüfwert, keine IP
	$n  = (int) get_transient( $rk );
	if ( $n >= KJO_SW_MAX ) {
		return new WP_REST_Response( array( 'message' => 'Danke, das reicht erst mal! In ein paar Minuten kannst du weitere Wünsche schicken.' ), 429 );
	}
	set_transient( $rk, $n + 1, 10 * MINUTE_IN_SECONDS );
	$key = md5( kjo_sw_norm( $titel ) . '|' . kjo_sw_norm( $kuen ) );
	$ex  = get_posts(
		array(
			'post_type'   => 'kjo_wunsch',
			'post_status' => 'private',
			'numberposts' => 1,
			'meta_query'  => array( // phpcs:ignore
				array( 'key' => '_kjo_sw_e', 'value' => $a['code'] ),
				array( 'key' => '_kjo_sw_k', 'value' => $key ),
			),
		)
	);
	if ( $ex ) {
		$id = $ex[0]->ID;
		update_post_meta( $id, '_kjo_sw_n', (int) get_post_meta( $id, '_kjo_sw_n', true ) + 1 );
		update_post_meta( $id, '_kjo_sw_t', time() );
	} else {
		$id = wp_insert_post(
			array(
				'post_type'   => 'kjo_wunsch',
				'post_status' => 'private',
				'post_title'  => $titel . ( '' !== $kuen ? ' – ' . $kuen : '' ),
			)
		);
		if ( ! $id || is_wp_error( $id ) ) {
			return new WP_REST_Response( array( 'message' => 'Das hat nicht geklappt, bitte noch einmal versuchen.' ), 500 );
		}
		update_post_meta( $id, '_kjo_sw_e', $a['code'] );
		update_post_meta( $id, '_kjo_sw_k', $key );
		update_post_meta( $id, '_kjo_sw_n', 1 );
		update_post_meta( $id, '_kjo_sw_t', time() );
		update_post_meta( $id, '_kjo_sw_s', 'offen' );
	}
	if ( '' !== $name ) {
		$nm = (array) get_post_meta( $id, '_kjo_sw_namen', true );
		$nm = array_slice( array_unique( array_merge( array_filter( $nm ), array( $name ) ) ), 0, 6 );
		update_post_meta( $id, '_kjo_sw_namen', $nm );
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* ---------- Gäste-Seite: /?songwunsch=CODE ---------- */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET['songwunsch'] ) ) { // phpcs:ignore
			return;
		}
		$code = sanitize_key( wp_unslash( $_GET['songwunsch'] ) ); // phpcs:ignore
		$a    = kjo_sw_abend();
		$auf  = $a && hash_equals( $a['code'], $code );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
		$api  = esc_url_raw( rest_url( 'kjo/v1/songwunsch' ) );
		$ig   = 'https://www.instagram.com/djkoljaone/';
		$logo = plugins_url( 'assets/icon-192.png', __FILE__ );
		?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#0F0C07">
<title>Songwunsch · DJ KOLJA ONE</title><link rel="icon" href="<?php echo esc_url( home_url( '/favicon.ico' ) ); ?>">
<style>
:root{--g:#B29D75;--o:#F3F1E9;--m:#A39E93}*{box-sizing:border-box}[hidden]{display:none!important}html,body{margin:0;background:#0F0C07;color:var(--o);font:16px/1.5 "Fira Sans",system-ui,-apple-system,sans-serif}
main{max-width:460px;margin:0 auto;padding:28px 20px 40px}.top{text-align:center}.top img{width:64px;height:64px;border-radius:12px}
.brand{letter-spacing:6px;font-size:13px;color:var(--g);text-transform:uppercase;margin:12px 0 2px}h1{font-weight:300;font-size:30px;margin:6px 0 4px;line-height:1.2}.ev{color:var(--m);margin:0 0 22px}
form{display:flex;flex-direction:column;gap:14px}label{font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:var(--g)}label span{text-transform:none;letter-spacing:0;color:var(--m)}
input{width:100%;margin-top:6px;padding:14px;background:#1A1712;border:1px solid rgba(178,157,117,.5);border-radius:3px;color:var(--o);font:17px "Fira Sans",sans-serif}input:focus{outline:2px solid var(--g);outline-offset:1px}
button,.btn{display:block;width:100%;text-align:center;padding:16px;background:var(--g);color:#0F0C07;border:0;border-radius:3px;font:500 15px "Fira Sans",sans-serif;letter-spacing:2px;text-transform:uppercase;text-decoration:none;cursor:pointer}
.btn.s{background:none;border:1px solid var(--g);color:var(--g)}.hp{position:absolute;left:-9999px}.err{color:#ff9b8a;margin:0;min-height:1em}
.ok{text-align:center}.ok h2{font-weight:300;font-size:26px;margin:10px 0}.ok p{color:var(--m)}.links{display:flex;flex-direction:column;gap:10px;margin-top:22px}
.small{font-size:12px;color:var(--m);text-align:center;margin-top:26px}.small a{color:var(--m)}
</style></head><body><main>
<div class="top"><img src="<?php echo esc_url( $logo ); ?>" alt=""><div class="brand">DJ KOLJA ONE</div>
<?php if ( $auf ) : ?>
<h1>Dein Songwunsch</h1><p class="ev"><?php echo esc_html( $a['name'] ); ?></p></div>
<form id="f" novalidate>
<label>Songtitel *<input name="titel" required maxlength="120" autocomplete="off" placeholder="z. B. Dancing Queen"></label>
<label>Interpret <span>(falls bekannt)</span><input name="kuenstler" maxlength="120" autocomplete="off" placeholder="z. B. ABBA"></label>
<label>Dein Vorname <span>(freiwillig)</span><input name="name" maxlength="40" autocomplete="given-name"></label>
<label class="hp" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
<p class="err" id="err" role="alert"></p>
<button type="submit" id="b">Wunsch abschicken</button>
</form>
<div class="ok" id="ok" hidden><h2>Danke, ist angekommen! 🎶</h2><p>Ich schaue, wann er gut passt – versprochen ist nichts, aber die Chancen stehen gut.</p>
<div class="links"><button type="button" class="btn" id="again">Noch einen Song wünschen</button><a class="btn s" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener">DJ KOLJA ONE auf Instagram</a><a class="btn s" href="<?php echo esc_url( home_url( '/kontakt/?utm_source=qr&utm_medium=songwunsch&utm_campaign=gaeste' ) ); ?>">DJ für eure eigene Feier?</a></div></div>
<script>(function(){var f=document.getElementById("f"),ok=document.getElementById("ok"),err=document.getElementById("err"),b=document.getElementById("b");
f.addEventListener("submit",function(e){e.preventDefault();err.textContent="";var d={e:<?php echo wp_json_encode( $code ); ?>};["titel","kuenstler","name","website"].forEach(function(n){d[n]=f.elements[n].value.trim()});
 if(d.titel.length<2){err.textContent="Bitte gib einen Songtitel ein.";f.elements.titel.focus();return}
 b.disabled=true;b.textContent="wird gesendet …";
 fetch(<?php echo wp_json_encode( $api ); ?>,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(d),credentials:"omit"}).then(function(r){return r.json().then(function(j){return{ok:r.ok,j:j}})}).then(function(x){b.disabled=false;b.textContent="Wunsch abschicken";
  if(x.ok){f.hidden=true;ok.hidden=false;f.elements.titel.value="";f.elements.kuenstler.value=""}else err.textContent=(x.j&&x.j.message)||"Das hat nicht geklappt, bitte noch einmal versuchen."}).catch(function(){b.disabled=false;b.textContent="Wunsch abschicken";err.textContent="Keine Verbindung – bitte noch einmal versuchen."})});
document.getElementById("again").onclick=function(){ok.hidden=true;f.hidden=false;f.elements.titel.focus()};})();</script>
<?php else : ?>
<h1>Die Wunschliste ist gerade geschlossen</h1><p class="ev">Songwünsche gehen nur während der Feier. Sprich mich gern direkt am DJ-Pult an!</p></div>
<div class="links"><a class="btn" href="<?php echo esc_url( home_url( '/meine-musik/' ) ); ?>">Meine Musik anhören</a><a class="btn s" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener">DJ KOLJA ONE auf Instagram</a></div>
<?php endif; ?>
<p class="small">Dein Wunsch und – falls angegeben – dein Vorname werden nur für diese Feier gespeichert und nach <?php echo (int) KJO_SW_TAGE; ?> Tagen gelöscht. <a href="<?php echo esc_url( home_url( '/datenschutz/' ) ); ?>">Datenschutz</a> · <a href="<?php echo esc_url( home_url( '/impressum/' ) ); ?>">Impressum</a></p>
</main></body></html>
		<?php
		exit;
	},
	1
);

/* ---------- Admin: Songwünsche ---------- */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Songwünsche', 'Songwünsche', 'edit_pages', 'kjo-songwunsch', 'kjo_sw_page', 'dashicons-playlist-audio', 26 );
	}
);
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'toplevel_page_kjo-songwunsch' === $hook ) {
			wp_enqueue_script( 'kjm-qrcode', plugins_url( 'assets/qrcode.js', __FILE__ ), array(), KJO_VERSION, true );
		}
	}
);
function kjo_sw_page() {
	if ( isset( $_POST['kjo_sw_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_sw_nonce'] ) ), 'kjo_sw' ) && current_user_can( 'edit_pages' ) ) {
		if ( isset( $_POST['start'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore
			update_option( 'kjo_sw_abend', array( 'code' => strtolower( wp_generate_password( 10, false, false ) ), 'name' => '' !== $name ? $name : 'Songwünsche', 'start' => time() ), false );
			kjo_sw_aufraeumen();
		} elseif ( isset( $_POST['ende'] ) ) {
			delete_option( 'kjo_sw_abend' );
		}
	}
	$a = kjo_sw_abend();
	echo '<div class="wrap"><h1>Songwünsche per QR-Code</h1>';
	if ( ! $a ) {
		echo '<p>Startet den Abend kurz vor der Feier. Gäste scannen den QR-Code (Tischkarte, Bildschirm, Pult) und schicken ihre Wünsche – du siehst sie hier live. Nach 18 Stunden schließt die Liste automatisch.</p>';
		echo '<form method="post">' . wp_nonce_field( 'kjo_sw', 'kjo_sw_nonce', true, false ) . '<p><label>Name der Feier (sehen die Gäste)<br><input type="text" name="name" class="regular-text" placeholder="z. B. Hochzeit Anna &amp; Ben"></label></p><p><button class="button button-primary button-hero" name="start" value="1">Abend starten</button></p></form>';
		echo '<p class="description">Wünsche werden nach ' . (int) KJO_SW_TAGE . ' Tagen automatisch gelöscht. IP-Adressen werden nicht gespeichert.</p></div>';
		return;
	}
	$url = kjo_sw_url( $a['code'] );
	echo '<div style="display:flex;gap:28px;flex-wrap:wrap;align-items:flex-start;margin:14px 0">';
	echo '<div class="kjm-qr" data-url="' . esc_attr( $url ) . '" data-titel="' . esc_attr( $a['name'] ) . '" data-kopf="♫  Dein Songwunsch" data-file="qr-songwunsch.png"></div>';
	echo '<div><h2 style="margin-top:0">' . esc_html( $a['name'] ) . '</h2><p>Gestartet ' . esc_html( wp_date( 'd.m.Y, H:i', (int) $a['start'] ) ) . ' Uhr · Link für Gäste:<br><code>' . esc_html( $url ) . '</code></p>';
	echo '<form method="post" onsubmit="return confirm(\'Wunschliste schließen? Gäste können dann nichts mehr schicken.\')">' . wp_nonce_field( 'kjo_sw', 'kjo_sw_nonce', true, false ) . '<button class="button" name="ende" value="1">Abend beenden</button></form></div></div>';
	echo '<h2>Wünsche <small id="kjo-sw-st" style="font-weight:400;color:#646970"></small></h2><table class="widefat striped"><thead><tr><th style="width:70px">Anzahl</th><th>Song</th><th>Von</th><th style="width:90px">Zuletzt</th><th style="width:230px"></th></tr></thead><tbody id="kjo-sw-list"><tr><td colspan="5">Lade …</td></tr></tbody></table>';
	echo '<p><label><input type="checkbox" id="kjo-sw-alle"> Gespielte und ausgeblendete zeigen</label></p></div>';
	$aj = wp_json_encode( array( 'url' => admin_url( 'admin-ajax.php' ), 'n' => wp_create_nonce( 'kjo_sw_ajax' ) ) );
	?>
<script>(function(){var A=<?php echo $aj; // phpcs:ignore ?>,tb=document.getElementById("kjo-sw-list"),st=document.getElementById("kjo-sw-st"),all=document.getElementById("kjo-sw-alle");
function esc(t){return String(t==null?"":t).replace(/[&<>"]/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]})}
function post(p){var fd=new FormData();fd.append("action","kjo_sw");fd.append("_ajax_nonce",A.n);for(var k in p)fd.append(k,p[k]);return fetch(A.url,{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json()})}
function load(){post({do:"list"}).then(function(j){if(!j||!j.success)return;var l=j.data.filter(function(w){return all.checked||w.s==="offen"});
 st.textContent=j.data.filter(function(w){return w.s==="offen"}).length+" offen · aktualisiert "+new Date().toLocaleTimeString("de-DE",{hour:"2-digit",minute:"2-digit",second:"2-digit"});
 tb.innerHTML=l.length?l.map(function(w){return '<tr style="'+(w.s!=="offen"?"opacity:.5":"")+'"><td><strong style="font-size:18px">'+w.n+'×</strong></td><td style="font-size:15px">'+esc(w.t)+'</td><td>'+esc(w.v.join(", "))+'</td><td>'+esc(w.z)+'</td><td>'+(w.s==="offen"?'<button class="button button-primary" data-id="'+w.id+'" data-s="gespielt">✓ gespielt</button> <button class="button" data-id="'+w.id+'" data-s="aus">ausblenden</button>':'<button class="button" data-id="'+w.id+'" data-s="offen">zurückholen</button>')+'</td></tr>'}).join(""):'<tr><td colspan="5">Noch keine Wünsche.</td></tr>'})}
tb.addEventListener("click",function(e){var b=e.target.closest("button");if(!b)return;b.disabled=true;post({do:"set",id:b.getAttribute("data-id"),s:b.getAttribute("data-s")}).then(load)});
all.addEventListener("change",load);load();setInterval(load,10000);
/* QR-Code */var el=document.querySelector(".kjm-qr");if(el&&typeof qrcode==="function"){var q=qrcode(0,"M");q.addData(el.getAttribute("data-url"));q.make();var n=q.getModuleCount(),W=1200,cell=Math.floor((W-180)/n),off=Math.floor((W-cell*n)/2),H=W+250,c=document.createElement("canvas");c.width=W;c.height=H;var x=c.getContext("2d");x.fillStyle="#fff";x.fillRect(0,0,W,H);x.fillStyle="#0F0C07";
 for(var r=0;r<n;r++)for(var k=0;k<n;k++)if(q.isDark(r,k))x.fillRect(off+k*cell,off+r*cell,cell,cell);
 x.textAlign="center";x.font="600 66px sans-serif";x.fillText(el.getAttribute("data-kopf"),W/2,W+60);x.font="46px sans-serif";var t=el.getAttribute("data-titel")||"";x.fillText(t.length>42?t.slice(0,41)+"…":t,W/2,W+135);x.fillStyle="#8C7650";x.font="38px sans-serif";x.fillText("Scannen · Wunsch schicken · DJ KOLJA ONE",W/2,W+205);
 var d=c.toDataURL("image/png"),img=new Image();img.src=d;img.alt="QR-Code";img.style.cssText="width:220px;height:auto;border:1px solid #ccd0d4;display:block;margin:0 0 8px";var a=document.createElement("a");a.href=d;a.download=el.getAttribute("data-file");a.className="button button-primary";a.textContent="QR-Code als PNG herunterladen";el.appendChild(img);el.appendChild(a)}
})();</script>
	<?php
}
add_action(
	'wp_ajax_kjo_sw',
	function () {
		check_ajax_referer( 'kjo_sw_ajax' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( null, 403 );
		}
		$a  = kjo_sw_abend();
		$do = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) ); // phpcs:ignore
		if ( 'set' === $do ) {
			$id = absint( $_POST['id'] ?? 0 ); // phpcs:ignore
			$s  = sanitize_key( wp_unslash( $_POST['s'] ?? '' ) ); // phpcs:ignore
			if ( $id && 'kjo_wunsch' === get_post_type( $id ) && in_array( $s, array( 'offen', 'gespielt', 'aus' ), true ) ) {
				update_post_meta( $id, '_kjo_sw_s', $s );
			}
			wp_send_json_success();
		}
		$out = array();
		if ( $a ) {
			foreach ( get_posts( array( 'post_type' => 'kjo_wunsch', 'post_status' => 'private', 'numberposts' => 300, 'meta_key' => '_kjo_sw_e', 'meta_value' => $a['code'] ) ) as $p ) { // phpcs:ignore
				$out[] = array(
					'id' => $p->ID,
					't'  => html_entity_decode( $p->post_title, ENT_QUOTES, 'UTF-8' ),
					'n'  => (int) get_post_meta( $p->ID, '_kjo_sw_n', true ),
					'v'  => array_values( array_filter( (array) get_post_meta( $p->ID, '_kjo_sw_namen', true ) ) ),
					's'  => (string) get_post_meta( $p->ID, '_kjo_sw_s', true ),
					'ts' => (int) get_post_meta( $p->ID, '_kjo_sw_t', true ),
					'z'  => wp_date( 'H:i', (int) get_post_meta( $p->ID, '_kjo_sw_t', true ) ),
				);
			}
		}
		usort(
			$out,
			function ( $x, $y ) {
				$o = array( 'offen' => 0, 'gespielt' => 1, 'aus' => 2 );
				return array( $o[ $x['s'] ] ?? 0, -$x['n'], -$x['ts'] ) <=> array( $o[ $y['s'] ] ?? 0, -$y['n'], -$y['ts'] );
			}
		);
		wp_send_json_success( $out );
	}
);
