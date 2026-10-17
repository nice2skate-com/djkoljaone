<?php
/**
 * Social: Reels auf Instagram und Facebook veröffentlichen (seit 1.57.0)
 *
 * Direkt über die Graph-API von Meta, ohne Fremddienst. Admin „Social“:
 *  - Verbindung: Seiten-ID und dauerhafter Seiten-Zugriffsschlüssel (Einrichtung: social/README.md im Repo).
 *  - Posten: Video aus der Mediathek wählen, Text eingeben, Instagram und/oder Facebook anhaken → „Jetzt veröffentlichen“.
 * Dasselbe gibt es als GitHub-Ablauf „Social: Reel posten“ (social/meta_reel.py). Dessen Videos landen in
 * uploads/kjo-social/ und werden hier nach 14 Tagen gelöscht.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kjo_soc_opts() {
	$d = array(
		'page_id'   => '',
		'token'     => '',
		'version'   => 'v23.0',
		'ig_id'     => '',
		'ig_name'   => '',
		'page_name' => '',
		'text'      => "Jedes Event findet nur einmal statt.\nIn 40 Sekunden seht ihr, wer ich bin, was ich mitbringe und wie ihr euren Wunschtermin sichert.\nHochzeiten, Geburtstage, Firmenfeiern und Events – in Memmingen, Ulm, im Allgäu und in Oberschwaben.\nKostenloses Kennenlerngespräch direkt online buchen: Link in der Bio.\n\n#djkoljaone #hochzeitsdj #djmemmingen #djulm #allgäu #oberschwaben #eventdj #firmenfeier",
	);
	$o = get_option( 'kjo_social', array() );
	return is_array( $o ) ? array_merge( $d, array_intersect_key( $o, $d ) ) : $d;
}

/** Aufruf der Graph-API. Rückgabe: Array oder WP_Error mit verständlicher Meldung. */
function kjo_soc_api( $pfad, $daten = null, $get = array() ) {
	$o = kjo_soc_opts();
	if ( ! $o['token'] || ! $o['page_id'] ) {
		return new WP_Error( 'kjo_soc', 'Bitte zuerst Seiten-ID und Zugriffsschlüssel speichern.' );
	}
	$url = 'https://graph.facebook.com/' . rawurlencode( $o['version'] ) . '/' . $pfad;
	$r   = null === $daten
		? wp_remote_get( add_query_arg( array_merge( $get, array( 'access_token' => $o['token'] ) ), $url ), array( 'timeout' => 45 ) )
		: wp_remote_post( $url, array( 'timeout' => 90, 'body' => array_merge( $daten, array( 'access_token' => $o['token'] ) ) ) );
	return kjo_soc_antwort( $r );
}
function kjo_soc_antwort( $r ) {
	if ( is_wp_error( $r ) ) {
		return new WP_Error( 'kjo_soc', 'Keine Verbindung zu Meta: ' . $r->get_error_message() );
	}
	$j = json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( ! empty( $j['error'] ) ) {
		$e = $j['error'];
		return new WP_Error( 'kjo_soc', 'Meta meldet: ' . ( ! empty( $e['error_user_msg'] ) ? $e['error_user_msg'] : ( $e['message'] ?? 'unbekannter Fehler' ) ) . ( ! empty( $e['code'] ) ? ' (Code ' . (int) $e['code'] . ')' : '' ) );
	}
	return is_array( $j ) ? $j : array();
}
function kjo_soc_log( $ziel, $ok, $info ) {
	$l   = get_option( 'kjo_social_log', array() );
	$l   = is_array( $l ) ? $l : array();
	$l[] = array( time(), $ziel, $ok, $info );
	update_option( 'kjo_social_log', array_slice( $l, -30 ), false );
}

/* ---------- Admin ---------- */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Social', 'Social', 'manage_options', 'kjo-social', 'kjo_soc_page', 'dashicons-share-alt', 28 );
	}
);

/** Videos für die Auswahl: MP4 aus der Mediathek und aus uploads/kjo-social. */
function kjo_soc_videos() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'video/mp4', 'numberposts' => 60, 'post_status' => 'inherit' ) ) as $a ) {
		$m     = wp_get_attachment_metadata( $a->ID );
		$info  = ! empty( $m['width'] ) ? (int) $m['width'] . '×' . (int) $m['height'] : '';
		$info .= ! empty( $m['length'] ) ? ( $info ? ', ' : '' ) . (int) $m['length'] . ' s' : '';
		$out[] = array( 'url' => wp_get_attachment_url( $a->ID ), 'name' => $a->post_title . ( $info ? ' (' . $info . ')' : '' ), 'hoch' => ! empty( $m['width'] ) && $m['height'] > $m['width'] );
	}
	$u = wp_upload_dir();
	foreach ( (array) glob( trailingslashit( $u['basedir'] ) . 'kjo-social/*.mp4' ) as $f ) {
		$out[] = array( 'url' => trailingslashit( $u['baseurl'] ) . 'kjo-social/' . basename( $f ), 'name' => 'GitHub: ' . basename( $f ), 'hoch' => true );
	}
	return $out;
}

function kjo_soc_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = kjo_soc_opts();
	if ( isset( $_POST['kjo_soc_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kjo_soc_nonce'] ) ), 'kjo_soc' ) ) {
		$o['page_id'] = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['page_id'] ?? '' ) ) ); // phpcs:ignore
		$t            = trim( sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) ) ); // phpcs:ignore
		if ( '' !== $t ) {
			$o['token'] = $t;
		}
		if ( ! empty( $_POST['token_weg'] ) ) {
			$o['token'] = '';
		}
		$v            = sanitize_text_field( wp_unslash( $_POST['version'] ?? '' ) ); // phpcs:ignore
		$o['version'] = preg_match( '/^v\d+\.\d$/', $v ) ? $v : 'v23.0';
		$o['text']    = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? $o['text'] ) ); // phpcs:ignore
		$o['ig_id']   = '';
		update_option( 'kjo_social', $o, false );
		echo '<div class="notice notice-success"><p>Gespeichert. Jetzt „Verbindung prüfen“.</p></div>';
	}
	$videos = kjo_soc_videos();
	echo '<div class="wrap"><h1>Social: Reels posten</h1>';
	echo '<p>Veröffentlicht ein Video als Reel auf Instagram und/oder auf der Facebook-Seite – direkt über Meta, ohne Fremddienst. Einrichtung Schritt für Schritt: <code>social/README.md</code> im Repo.</p>';

	echo '<h2>1. Verbindung</h2><form method="post"><table class="form-table">';
	wp_nonce_field( 'kjo_soc', 'kjo_soc_nonce' );
	echo '<tr><th><label for="kjo-soc-page">Facebook-Seiten-ID</label></th><td><input id="kjo-soc-page" name="page_id" value="' . esc_attr( $o['page_id'] ) . '" class="regular-text"></td></tr>';
	echo '<tr><th><label for="kjo-soc-token">Seiten-Zugriffsschlüssel</label></th><td><input id="kjo-soc-token" name="token" type="password" autocomplete="off" class="regular-text" placeholder="' . esc_attr( $o['token'] ? 'gespeichert – leer lassen zum Behalten' : 'dauerhafter Page Access Token' ) . '">' . ( $o['token'] ? ' <label><input type="checkbox" name="token_weg" value="1"> löschen</label>' : '' ) . '<p class="description">Wird nur auf deinem Server gespeichert und nie angezeigt.</p></td></tr>';
	echo '<tr><th><label for="kjo-soc-ver">API-Version</label></th><td><input id="kjo-soc-ver" name="version" value="' . esc_attr( $o['version'] ) . '" size="8"><p class="description">Nur ändern, wenn Meta eine Version abschaltet.</p></td></tr>';
	echo '<tr><th><label for="kjo-soc-text">Standardtext</label></th><td><textarea id="kjo-soc-text" name="text" rows="6" class="large-text">' . esc_textarea( $o['text'] ) . '</textarea></td></tr>';
	echo '</table><p><button class="button button-primary">Speichern</button> <button type="button" class="button" id="kjo-soc-check">Verbindung prüfen</button> <span id="kjo-soc-checkmsg">' . ( $o['ig_id'] ? esc_html( 'Verbunden: ' . $o['page_name'] . ' · Instagram @' . $o['ig_name'] ) : '' ) . '</span></p></form>';

	echo '<h2>2. Reel veröffentlichen</h2><table class="form-table">';
	echo '<tr><th><label for="kjo-soc-video">Video</label></th><td><select id="kjo-soc-video" style="max-width:100%">';
	foreach ( $videos as $v ) {
		echo '<option value="' . esc_attr( $v['url'] ) . '">' . esc_html( $v['name'] . ( $v['hoch'] ? '' : ' – nicht hochkant' ) ) . '</option>';
	}
	echo '</select><p class="description">MP4 aus der Mediathek. Für Reels: hochkant (9:16), 3–90 Sekunden, mit Tonspur (notfalls stumm). Neues Video: unter Medien hochladen, dann hier neu laden.</p></td></tr>';
	echo '<tr><th><label for="kjo-soc-post">Text</label></th><td><textarea id="kjo-soc-post" rows="7" class="large-text">' . esc_textarea( $o['text'] ) . '</textarea></td></tr>';
	echo '<tr><th>Wohin</th><td><label><input type="checkbox" id="kjo-soc-ig" checked> Instagram (Reel)</label> &nbsp; <label><input type="checkbox" id="kjo-soc-fb" checked> Facebook-Seite (Reel)</label></td></tr>';
	echo '</table><p><button type="button" class="button button-primary" id="kjo-soc-go"' . ( $videos ? '' : ' disabled' ) . '>Jetzt veröffentlichen</button></p><div id="kjo-soc-out" role="status" style="max-width:760px"></div>';

	$log = get_option( 'kjo_social_log', array() );
	if ( is_array( $log ) && $log ) {
		echo '<h2>Verlauf</h2><table class="widefat striped" style="max-width:760px"><thead><tr><th>Zeit</th><th>Ziel</th><th>Ergebnis</th></tr></thead><tbody>';
		foreach ( array_reverse( $log ) as $e ) {
			$info = (string) $e[3];
			echo '<tr><td>' . esc_html( wp_date( 'd.m.Y H:i', (int) $e[0] ) ) . '</td><td>' . esc_html( $e[1] ) . '</td><td>' . ( $e[2] && 0 === strpos( $info, 'https://' ) ? '<a href="' . esc_url( $info ) . '" target="_blank" rel="noopener">ansehen</a>' : esc_html( $info ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
	$nonce = wp_create_nonce( 'kjo_soc_ajax' );
	?>
<script>
(function(){
var A=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,N=<?php echo wp_json_encode( $nonce ); ?>,out=document.getElementById('kjo-soc-out');
function post(action,data){var f=new FormData();f.append('action',action);f.append('nonce',N);Object.keys(data||{}).forEach(function(k){f.append(k,data[k])});
 return fetch(A,{method:'POST',body:f,credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){if(!j||!j.success)throw new Error((j&&j.data&&j.data.message)||'Unbekannter Fehler');return j.data})}
function zeile(t,cls){var p=document.createElement('p');p.textContent=t;if(cls)p.style.color=cls;out.appendChild(p);return p}
function link(t,u){var p=zeile(t+' ');var a=document.createElement('a');a.href=u;a.target='_blank';a.rel='noopener';a.textContent='ansehen';p.appendChild(a)}
document.getElementById('kjo-soc-check').onclick=function(){var m=document.getElementById('kjo-soc-checkmsg');m.textContent='prüfe …';
 post('kjo_soc_pruefen').then(function(d){m.textContent='Verbunden: '+d.page+(d.ig?' · Instagram @'+d.ig:' · Instagram nicht verknüpft')}).catch(function(e){m.textContent=e.message})};
function warte(ms){return new Promise(function(r){setTimeout(r,ms)})}
function igPoll(cid,n){return warte(5000).then(function(){return post('kjo_soc_ig_status',{cid:cid})}).then(function(d){
 if(d.fertig)return d; if(n>120)throw new Error('Instagram verarbeitet noch – bitte später in der App prüfen.'); return igPoll(cid,n+1)})}
document.getElementById('kjo-soc-go').onclick=function(){
 var b=this,url=document.getElementById('kjo-soc-video').value,txt=document.getElementById('kjo-soc-post').value,ig=document.getElementById('kjo-soc-ig').checked,fb=document.getElementById('kjo-soc-fb').checked;
 if(!ig&&!fb)return;
 if(!confirm('Jetzt öffentlich auf '+[ig?'Instagram':'',fb?'Facebook':''].filter(Boolean).join(' und ')+' veröffentlichen?'))return;
 b.disabled=true;out.innerHTML='';
 var kette=Promise.resolve();
 if(ig)kette=kette.then(function(){var p=zeile('Instagram: Video wird verarbeitet …');return post('kjo_soc_ig_start',{video_url:url,text:txt}).then(function(d){return igPoll(d.cid,0)}).then(function(d){p.remove();link('Instagram: veröffentlicht.',d.link)})});
 if(fb)kette=kette.then(function(){var p=zeile('Facebook: Video wird übertragen …');return post('kjo_soc_fb',{video_url:url,text:txt}).then(function(d){p.remove();link('Facebook: veröffentlicht (die Verarbeitung kann ein paar Minuten dauern).',d.link)})});
 kette.catch(function(e){zeile(e.message,'#b32d2e')}).then(function(){b.disabled=false});
};
})();
</script>
	<?php
}

/* ---------- AJAX ---------- */
function kjo_soc_check_ajax() {
	if ( ! check_ajax_referer( 'kjo_soc_ajax', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Keine Berechtigung – bitte Seite neu laden.' ), 403 );
	}
}
function kjo_soc_fehler( $e, $ziel = '' ) {
	if ( $ziel ) {
		kjo_soc_log( $ziel, false, $e->get_error_message() );
	}
	wp_send_json_error( array( 'message' => $e->get_error_message() ), 400 );
}
/** Video-Adresse muss öffentlich per https auf dieser Website liegen (Meta lädt sie selbst herunter). */
function kjo_soc_video_url() {
	$u = isset( $_POST['video_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_url'] ) ) : ''; // phpcs:ignore
	if ( 0 !== strpos( $u, 'https://' ) || 0 !== strpos( $u, set_url_scheme( home_url( '/' ), 'https' ) ) ) {
		wp_send_json_error( array( 'message' => 'Das Video muss auf dieser Website per https erreichbar sein.' ), 400 );
	}
	return $u;
}
add_action(
	'wp_ajax_kjo_soc_pruefen',
	function () {
		kjo_soc_check_ajax();
		$o = kjo_soc_opts();
		$s = kjo_soc_api( $o['page_id'], null, array( 'fields' => 'id,name,instagram_business_account{id,username}' ) );
		if ( is_wp_error( $s ) ) {
			kjo_soc_fehler( $s );
		}
		$o['page_name'] = (string) ( $s['name'] ?? '' );
		$o['ig_id']     = (string) ( $s['instagram_business_account']['id'] ?? '' );
		$o['ig_name']   = (string) ( $s['instagram_business_account']['username'] ?? '' );
		update_option( 'kjo_social', $o, false );
		wp_send_json_success( array( 'page' => $o['page_name'], 'ig' => $o['ig_name'] ) );
	}
);
add_action(
	'wp_ajax_kjo_soc_ig_start',
	function () {
		kjo_soc_check_ajax();
		$o = kjo_soc_opts();
		if ( ! $o['ig_id'] ) {
			wp_send_json_error( array( 'message' => 'Instagram ist nicht verbunden – bitte „Verbindung prüfen“.' ), 400 );
		}
		$text = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) ); // phpcs:ignore
		$c    = kjo_soc_api( $o['ig_id'] . '/media', array( 'media_type' => 'REELS', 'video_url' => kjo_soc_video_url(), 'caption' => $text, 'share_to_feed' => 'true' ) );
		if ( is_wp_error( $c ) ) {
			kjo_soc_fehler( $c, 'Instagram' );
		}
		wp_send_json_success( array( 'cid' => (string) $c['id'] ) );
	}
);
add_action(
	'wp_ajax_kjo_soc_ig_status',
	function () {
		kjo_soc_check_ajax();
		$o   = kjo_soc_opts();
		$cid = preg_replace( '/[^A-Za-z0-9_]/', '', sanitize_text_field( wp_unslash( $_POST['cid'] ?? '' ) ) ); // phpcs:ignore
		$st  = kjo_soc_api( $cid, null, array( 'fields' => 'status_code,status' ) );
		if ( is_wp_error( $st ) ) {
			kjo_soc_fehler( $st, 'Instagram' );
		}
		$code = $st['status_code'] ?? '';
		if ( in_array( $code, array( 'ERROR', 'EXPIRED' ), true ) ) {
			kjo_soc_fehler( new WP_Error( 'kjo_soc', 'Instagram konnte das Video nicht verarbeiten: ' . ( $st['status'] ?? $code ) ), 'Instagram' );
		}
		if ( 'FINISHED' !== $code ) {
			wp_send_json_success( array( 'fertig' => false ) );
		}
		$m = kjo_soc_api( $o['ig_id'] . '/media_publish', array( 'creation_id' => $cid ) );
		if ( is_wp_error( $m ) ) {
			kjo_soc_fehler( $m, 'Instagram' );
		}
		$p    = kjo_soc_api( (string) $m['id'], null, array( 'fields' => 'permalink' ) );
		$link = is_wp_error( $p ) ? '' : (string) ( $p['permalink'] ?? '' );
		kjo_soc_log( 'Instagram', true, $link ? $link : 'veröffentlicht (' . $m['id'] . ')' );
		wp_send_json_success( array( 'fertig' => true, 'link' => $link ? $link : 'https://www.instagram.com/' . rawurlencode( $o['ig_name'] ) . '/' ) );
	}
);
add_action(
	'wp_ajax_kjo_soc_fb',
	function () {
		kjo_soc_check_ajax();
		$o    = kjo_soc_opts();
		$url  = kjo_soc_video_url();
		$text = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) ); // phpcs:ignore
		$s    = kjo_soc_api( $o['page_id'] . '/video_reels', array( 'upload_phase' => 'start' ) );
		if ( is_wp_error( $s ) ) {
			kjo_soc_fehler( $s, 'Facebook' );
		}
		$up = kjo_soc_antwort( wp_remote_post( (string) $s['upload_url'], array( 'timeout' => 90, 'headers' => array( 'Authorization' => 'OAuth ' . $o['token'], 'file_url' => $url ) ) ) );
		if ( is_wp_error( $up ) ) {
			kjo_soc_fehler( $up, 'Facebook' );
		}
		$f = kjo_soc_api( $o['page_id'] . '/video_reels', array( 'upload_phase' => 'finish', 'video_id' => (string) $s['video_id'], 'video_state' => 'PUBLISHED', 'description' => $text ) );
		if ( is_wp_error( $f ) ) {
			kjo_soc_fehler( $f, 'Facebook' );
		}
		$link = 'https://www.facebook.com/reel/' . rawurlencode( (string) $s['video_id'] );
		kjo_soc_log( 'Facebook', true, $link );
		wp_send_json_success( array( 'link' => $link ) );
	}
);

/* ---------- Aufräumen: Videos aus dem GitHub-Ablauf nach 14 Tagen löschen ---------- */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'kjo_soc_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kjo_soc_cron' );
		}
	}
);
add_action(
	'kjo_soc_cron',
	function () {
		$u = wp_upload_dir();
		foreach ( (array) glob( trailingslashit( $u['basedir'] ) . 'kjo-social/*' ) as $f ) {
			if ( is_file( $f ) && time() - (int) filemtime( $f ) > 14 * DAY_IN_SECONDS ) {
				@unlink( $f ); // phpcs:ignore
			}
		}
	}
);
