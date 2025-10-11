<?php
/**
 * Uninstall handler
 *
 * @package LoginFormsOverhaul
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

LFO_Database::uninstall();
