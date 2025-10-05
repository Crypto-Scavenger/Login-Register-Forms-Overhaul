<?php
/**
 * Plugin Name: Login & Register Forms Overhaul
 * Description: Complete control over WordPress login, registration, and logout flows with invite-only registration system
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Text Domain: login-register-forms-overhaul
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LRFO_VERSION', '1.0.0' );
define( 'LRFO_DIR', plugin_dir_path( __FILE__ ) );
define( 'LRFO_URL', plugin_dir_url( __FILE__ ) );

require_once LRFO_DIR . 'includes/class-database.php';
require_once LRFO_DIR . 'includes/class-invite-codes.php';
require_once LRFO_DIR . 'includes/class-core.php';
require_once LRFO_DIR . 'includes/class-admin.php';

function lrfo_init() {
	$database     = new LRFO_Database();
	$invite_codes = new LRFO_Invite_Codes( $database );
	$core         = new LRFO_Core( $database, $invite_codes );
	
	if ( is_admin() ) {
		$admin = new LRFO_Admin( $database, $invite_codes );
	}
}
add_action( 'plugins_loaded', 'lrfo_init' );

register_activation_hook( __FILE__, array( 'LRFO_Database', 'activate' ) );
