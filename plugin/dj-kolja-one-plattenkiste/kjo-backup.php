<?php
/**
 * Backup-Wächter (seit 1.33.0): prüft UpdraftPlus.
 * Warnt im Admin (und einmal täglich per E-Mail), wenn kein Zeitplan aktiv ist oder die letzte Sicherung
 * älter als 3 Tage ist bzw. fehlgeschlagen ist. Abschalten: add_filter( 'kjo_backup_check', '__return_false' );
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Liefert eine Liste von Problemen (leer = alles gut). */
function kjo_backup_probleme() {
	if ( ! defined( 'UPDRAFTPLUS_DIR' ) && ! class_exists( 'UpdraftPlus' ) ) {
		return array( 'UpdraftPlus ist nicht aktiv – es laufen keine eigenen Sicherungen.' );
	}
	$p = array();
	if ( ! wp_next_scheduled( 'updraft_backup' ) ) {
		$p[] = 'Für die <strong>Dateien</strong> ist kein Sicherungsplan aktiv.';
	}
	if ( ! wp_next_scheduled( 'updraft_backup_database' ) ) {
		$p[] = 'Für die <strong>Datenbank</strong> ist kein Sicherungsplan aktiv.';
	}
	$last = get_option( 'updraft_last_backup' );
	$t    = is_array( $last ) && ! empty( $last['backup_time'] ) ? (int) $last['backup_time'] : 0;
	if ( ! $t ) {
		$p[] = 'Es wurde noch keine Sicherung gefunden.';
	} elseif ( time() - $t > 3 * DAY_IN_SECONDS ) {
		$p[] = 'Die letzte Sicherung ist vom <strong>' . esc_html( wp_date( 'd.m.Y', $t ) ) . '</strong> (älter als 3 Tage).';
	}
	if ( is_array( $last ) && isset( $last['success'] ) && ! $last['success'] ) {
		$p[] = 'Die letzte Sicherung meldete Fehler.';
	}
	return $p;
}
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! apply_filters( 'kjo_backup_check', true ) ) {
			return;
		}
		$p = kjo_backup_probleme();
		if ( ! $p ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Backup prüfen:</strong> ' . implode( ' ', $p ) . ' '; // phpcs:ignore
		echo '<a href="' . esc_url( admin_url( 'options-general.php?page=updraftplus&tab=settings' ) ) . '">UpdraftPlus-Einstellungen öffnen</a> (Zeitplan wählen und unten auf „Änderungen speichern“ klicken).</p></div>';
	}
);
/* Täglich prüfen, bei Problemen eine E-Mail (höchstens alle 3 Tage). */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'kjo_backup_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kjo_backup_daily' );
		}
	}
);
add_action(
	'kjo_backup_daily',
	function () {
		if ( ! apply_filters( 'kjo_backup_check', true ) || get_transient( 'kjo_backup_mail' ) ) {
			return;
		}
		$p = kjo_backup_probleme();
		if ( $p ) {
			set_transient( 'kjo_backup_mail', 1, 3 * DAY_IN_SECONDS );
			wp_mail( get_option( 'admin_email' ), 'Backup prüfen: dj-kolja-one.de', "Beim Backup stimmt etwas nicht:\n\n- " . implode( "\n- ", array_map( 'wp_strip_all_tags', $p ) ) . "\n\nUpdraftPlus: " . admin_url( 'options-general.php?page=updraftplus&tab=settings' ) . "\n", array( 'Content-Type: text/plain; charset=UTF-8' ) );
		}
	}
);
