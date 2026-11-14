<?php
/**
 * Spotify-Playlisten direkt auf der Website abspielen (seit 1.78.0).
 *
 * Shortcode [kjo_playlists stil="karten"] (Meine Musik) bzw. [kjo_playlists stil="chips"] (Startseite).
 * Klick auf „Hier anhören“ lädt den offiziellen Spotify-Player (Embed) unter den Playlisten – erst nach dem Klick
 * (2-Klick-Lösung): vorher wird nichts von Spotify geladen und keine Daten übertragen.
 *
 * Abspielen ohne Spotify-Abo: Mit Spotify-Anmeldung im Browser (auch kostenloses Konto) laufen die Songs komplett,
 * ohne Anmeldung spielt Spotify je Song eine Vorschau (ca. 30 Sekunden). Das legt Spotify fest, nicht die Website.
 * Ereignis „playlist_play“ (Genre, Playlist-ID) geht an Analytics/Clarity wie die übrigen Klicks.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Playlisten je Genre (Konto DJ KOLJA ONE) – gleiche Reihenfolge wie site/lib.py PLAYLISTS. */
const KJO_PLAYLISTS = array(
	array( 'Pop & Charts', 'Aktuelle Hits und die großen Songs der letzten Jahrzehnte.', '7d4o4r1akUFfV9SkXQGQbi' ),
	array( '80er, 90er & 2000er', 'Die Klassiker, bei denen jede Generation mitsingt.', '2PE3d6fdMnOqXkvaijTmyC' ),
	array( 'Schlager & Party', 'Wenn es zur richtigen Zeit passt – mit Augenmaß.', '46SzCrplPjVNrGaO46G1q7' ),
	array( 'Rock & Indie', 'Gitarren für die, die es etwas rauer mögen.', '0GlIgez6h7n00HNRdjjR90' ),
	array( 'House & Dance', 'Für späte Stunden und volle Tanzflächen.', '3sir4JiXufFXmOAM5QfUqP' ),
	array( 'Latin, Soul & Lounge', 'Für Empfang, Dinner und besondere Momente.', '0OPdAoZR5z6IWJdusrPkXl' ),
);

add_shortcode(
	'kjo_playlists',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'stil' => 'karten' ), $atts, 'kjo_playlists' );
		$stil = 'chips' === $atts['stil'] ? 'chips' : 'karten';
		$ds   = get_page_by_path( 'datenschutz' );
		$ds   = $ds ? get_permalink( $ds ) : home_url( '/datenschutz/' );
		$h    = '<div class="kjo-pls kjo-pls-' . $stil . '" data-kjo-pls><div class="kjo-pls-grid">';
		foreach ( KJO_PLAYLISTS as $p ) {
			$url = 'https://open.spotify.com/playlist/' . $p[2];
			$h  .= '<div class="kjo-pl kjo-pls-item" data-id="' . esc_attr( $p[2] ) . '">';
			$h  .= 'chips' === $stil ? '<p class="kjo-pls-t">' . esc_html( $p[0] ) . '</p>' : '<div><h3 class="kjo-pls-t">' . esc_html( $p[0] ) . '</h3><p class="kjo-pls-d">' . esc_html( $p[1] ) . '</p></div>';
			$h  .= '<div class="kjo-pls-acts"><button type="button" class="kjo-pls-play" data-id="' . esc_attr( $p[2] ) . '" data-t="' . esc_attr( $p[0] ) . '" aria-controls="kjo-pls-player-' . $stil . '"><span aria-hidden="true">▶</span> Hier anhören</button>';
			$h  .= '<a class="kjo-pls-ext" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">In Spotify öffnen ↗</a></div></div>';
		}
		$h .= '</div><div class="kjo-pls-player" id="kjo-pls-player-' . $stil . '" hidden><div class="kjo-pls-bar"><span class="kjo-pls-now"></span><button type="button" class="kjo-pls-zu" aria-label="Player schließen">✕</button></div><div class="kjo-pls-frame"></div></div>';
		$h .= '<p class="kjo-pls-note">Ganze Songs: „In Spotify öffnen ↗“ (auch mit kostenlosem Konto). Hier auf der Seite spielt Spotify je nach Browser Vorschauen. Der Spotify-Player wird erst beim Klick auf „Hier anhören“ geladen; dabei werden Daten an Spotify übertragen (<a href="' . esc_url( $ds ) . '">Datenschutz</a>).</p></div>';
		return $h . kjo_playlists_assets();
	}
);

/** CSS und JS einmal je Seite ausgeben. */
function kjo_playlists_assets() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	$css  = '.kjo-pls{width:100%;font-family:"Fira Sans",sans-serif}'
		. '.kjo-pls-grid{display:flex;flex-wrap:wrap;gap:24px;justify-content:center}'
		. '.kjo-pls-item{box-sizing:border-box;background:#0F0C07;border-top:2px solid #B29D75;display:flex;flex-direction:column;justify-content:space-between;gap:20px;transition:box-shadow .2s}'
		. '.kjo-pls-karten .kjo-pls-item{flex:0 1 calc(33.333% - 16px);padding:34px 28px}'
		. '.kjo-pls-chips .kjo-pls-grid{gap:16px}.kjo-pls-chips .kjo-pls-item{flex:0 1 calc(33.333% - 11px);padding:18px 16px;text-align:center;border:1px solid rgba(178,157,117,.35);align-items:center;gap:10px}'
		. '.kjo-pls-item.aktiv{box-shadow:0 0 0 1px #B29D75 inset}'
		. '.kjo-pls h3.kjo-pls-t{margin:0 0 12px;font-size:23px;font-weight:400;color:#F3F1E9;line-height:1.3}'
		. '.kjo-pls-chips p.kjo-pls-t{margin:0;font-size:19px;font-weight:300;color:#F3F1E9}'
		. '.kjo-pls-d{margin:0;font-size:16px;line-height:1.7;color:#A9A193}'
		. '.kjo-pls-acts{display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px}.kjo-pls-chips .kjo-pls-acts{justify-content:center}'
		. '.kjo-pls-play{cursor:pointer;background:transparent;color:#F3F1E9;border:1px solid #F3F1E9;padding:12px 22px;font:600 13px/1 "Fira Sans",sans-serif;letter-spacing:2px;text-transform:uppercase;border-radius:0;transition:background .2s,color .2s}'
		. '.kjo-pls-play:hover,.kjo-pls-play:focus-visible,.kjo-pls-item.aktiv .kjo-pls-play{background:#B29D75;border-color:#B29D75;color:#0F0C07}'
		. '.kjo-pls-chips .kjo-pls-play{padding:9px 14px;font-size:12px;letter-spacing:1.5px}'
		. '.kjo-pls-ext{font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#B29D75!important;text-decoration:none}.kjo-pls-ext:hover{text-decoration:underline}'
		. '.kjo-pls-player{margin:24px auto 0;max-width:820px;background:#0F0C07;border-top:2px solid #B29D75}'
		. '.kjo-pls-bar{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;color:#B29D75;font-size:13px;letter-spacing:1.5px;text-transform:uppercase}'
		. '.kjo-pls-zu{background:none;border:0;color:#A9A193;font-size:18px;cursor:pointer;padding:2px 6px}.kjo-pls-zu:hover{color:#F3F1E9}'
		. '.kjo-pls-frame iframe{display:block;width:100%;height:380px;border:0;border-radius:0 0 12px 12px}'
		. '.kjo-pls-note{margin:16px auto 0;max-width:820px;text-align:center;font-size:13px;line-height:1.6;color:#8A8274}.kjo-pls-note a{color:#B29D75}'
		. '@media(max-width:1024px){.kjo-pls-karten .kjo-pls-item,.kjo-pls-chips .kjo-pls-item{flex-basis:calc(50% - 12px)}}'
		. '@media(max-width:600px){.kjo-pls-karten .kjo-pls-item{flex-basis:100%;padding:28px 22px}.kjo-pls-chips .kjo-pls-item{flex-basis:calc(50% - 8px);padding:14px 10px}.kjo-pls-chips p.kjo-pls-t{font-size:16px}.kjo-pls-frame iframe{height:352px}}';
	$js = '(function(){function init(w){if(w.dataset.ok)return;w.dataset.ok=1;var pl=w.querySelector(".kjo-pls-player"),fr=pl.querySelector(".kjo-pls-frame"),now=pl.querySelector(".kjo-pls-now");'
		. 'w.addEventListener("click",function(e){var b=e.target.closest(".kjo-pls-play");if(b){var id=b.dataset.id,t=b.dataset.t;'
		. 'fr.innerHTML=\'<iframe title="Spotify-Playlist: \'+t.replace(/"/g,"")+\'" src="https://open.spotify.com/embed/playlist/\'+encodeURIComponent(id)+\'?utm_source=generator&theme=0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>\';'
		. 'now.textContent="Playlist: "+t;pl.hidden=false;w.querySelectorAll(".kjo-pls-item").forEach(function(i){i.classList.toggle("aktiv",i.dataset.id===id);});'
		. 'var r=pl.getBoundingClientRect();if(r.top<0||r.bottom>window.innerHeight)pl.scrollIntoView({behavior:"smooth",block:"nearest"});'
		. 'if(window.kjoTrack)window.kjoTrack("playlist_play",{item_name:t,item_id:id});return;}'
		. 'if(e.target.closest(".kjo-pls-zu")){fr.innerHTML="";pl.hidden=true;w.querySelectorAll(".kjo-pls-item.aktiv").forEach(function(i){i.classList.remove("aktiv");});}});}'
		. 'function go(){document.querySelectorAll("[data-kjo-pls]").forEach(init);}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",go);else go();})();';
	return '<style id="kjo-pls-css">' . $css . '</style><script id="kjo-pls-js">' . $js . '</script>';
}
