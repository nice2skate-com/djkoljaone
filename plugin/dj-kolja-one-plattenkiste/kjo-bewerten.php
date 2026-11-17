<?php
/**
 * Kurzlink dj-kolja-one.de/bewerten → Google-Bewertung (seit 1.85.6).
 *
 * Für Rechnungen, Mails, Visitenkarten und den QR-Code (media/bewerten/). Ziel änderbar über die Konstante
 * KJO_BEWERTEN_URL; jeder Aufruf wird gezählt (Option kjo_bewerten_klicks, steht unter Angebote → Einstellungen).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KJO_BEWERTEN_URL = 'https://g.page/r/CRTQHjKq3Je-ECE/review'; // öffnet direkt den Bewertungsdialog (seit 1.85.7)

add_action(
	'init',
	function () {
		$p = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore
		if ( ! preg_match( '#^/bewerten/?$#i', $p ) ) {
			return;
		}
		update_option( 'kjo_bewerten_klicks', (int) get_option( 'kjo_bewerten_klicks', 0 ) + 1, false );
		nocache_headers();
		wp_redirect( KJO_BEWERTEN_URL, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect -- festes Ziel (Google)
		exit;
	},
	0
);
