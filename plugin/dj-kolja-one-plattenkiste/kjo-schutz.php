<?php
/**
 * Missbrauchsschutz für öffentliche Formulare und Zähler (seit 1.72.1).
 *
 * - kjo_netz():               IP-Adresse des Besuchers, bei IPv6 gekürzt auf das /64-Netz. Ein Anschluss
 *                             hat dort Milliarden Adressen; so zählen sie für die Sperre als eine.
 * - kjo_netz_hash( $salz ):   nicht umkehrbarer Prüfwert daraus (für Transient-Schlüssel, keine IP gespeichert).
 * - kjo_hat_link( ...$t ):    true, wenn ein Feld eine Webadresse enthält. Name, Ort, Gästezahl und Telefon
 *                             landen in der Bestätigungsmail; mit Links darin ließe sich das Formular als
 *                             Spam-Schleuder von dj-kolja-one.de aus missbrauchen.
 * - kjo_bestaetigung_ok():    höchstens eine Bestätigungsmail pro Adresse, Formular und Tag und höchstens
 *                             KJO_BESTAETIGUNG_STUNDE pro Stunde insgesamt. Die Anfrage selbst kommt immer an.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KJO_BESTAETIGUNG_STUNDE' ) ) {
	define( 'KJO_BESTAETIGUNG_STUNDE', 20 );
}

function kjo_netz() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( false === strpos( $ip, ':' ) ) {
		return $ip;
	}
	$b = @inet_pton( $ip ); // phpcs:ignore
	if ( false === $b || 16 !== strlen( $b ) ) {
		return $ip;
	}
	if ( str_repeat( "\0", 10 ) . "\xff\xff" === substr( $b, 0, 12 ) ) {
		return inet_ntop( substr( $b, 12 ) ); // IPv4 in IPv6-Schreibweise (::ffff:1.2.3.4)
	}
	return bin2hex( substr( $b, 0, 8 ) ) . '::/64';
}

function kjo_netz_hash( $salz = '' ) {
	return substr( hash_hmac( 'sha256', $salz . kjo_netz(), wp_salt( 'nonce' ) ), 0, 20 );
}

function kjo_hat_link( ...$texte ) {
	foreach ( $texte as $t ) {
		if ( preg_match( '~(https?:|ftp:|www\.|\b[a-z0-9-]{2,}\.(de|com|net|org|eu|io|info|biz|xyz|top|ru|cn|ly|me|co|app|site|online|shop|click|link|live|club)\b)~i', (string) $t ) ) {
			return true;
		}
	}
	return false;
}

function kjo_bestaetigung_ok( $email, $art ) {
	$k = 'kjo_bst_' . substr( hash_hmac( 'sha256', $art . '|' . strtolower( trim( (string) $email ) ), wp_salt( 'nonce' ) ), 0, 20 );
	$h = 'kjo_bst_stunde';
	$n = (int) get_transient( $h );
	if ( get_transient( $k ) || $n >= KJO_BESTAETIGUNG_STUNDE ) {
		return false;
	}
	set_transient( $k, 1, DAY_IN_SECONDS );
	set_transient( $h, $n + 1, HOUR_IN_SECONDS );
	return true;
}
