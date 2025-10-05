<?php
/**
 * Uninstall script for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$settings_table = $wpdb->prefix . 'lrfo_settings';
$cleanup        = $wpdb->get_var(
	$wpdb->prepare(
		'SELECT setting_value FROM %i WHERE setting_key = %s',
		$settings_table,
		'cleanup_on_uninstall'
	)
);

if ( '1' === $cleanup ) {
	
	$tables = array(
		$wpdb->prefix . 'lrfo_settings',
		$wpdb->prefix . 'lrfo_invite_codes',
		$wpdb->prefix . 'lrfo_code_usage',
	);

	foreach ( $tables as $table ) {
		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %i',
				$table
			)
		);
	}

	wp_cache_flush();

	$scheduled_hooks = array(
		'lrfo_delete_exhausted_code',
	);

	foreach ( $scheduled_hooks as $hook ) {
		$timestamp = wp_next_scheduled( $hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}
	}

	wp_clear_scheduled_hook( 'lrfo_delete_exhausted_code' );
}
