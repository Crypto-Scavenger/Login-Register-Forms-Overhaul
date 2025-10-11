<?php
/**
 * Plugin Name: Login Forms Overhaul
 * Description: Complete control over WordPress login/logout flows including styling, error messages, redirects, and session handling
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Text Domain: login-forms-overhaul
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LFO_VERSION', '1.0.0' );
define( 'LFO_DIR', plugin_dir_path( __FILE__ ) );
define( 'LFO_URL', plugin_dir_url( __FILE__ ) );

require_once LFO_DIR . 'includes/class-database.php';
require_once LFO_DIR . 'includes/class-core.php';
require_once LFO_DIR . 'includes/class-admin.php';

function lfo_init() {
	$database = new LFO_Database();
	$core = new LFO_Core( $database );
	
	if ( is_admin() ) {
		$admin = new LFO_Admin( $database );
	}
}
add_action( 'plugins_loaded', 'lfo_init' );

register_activation_hook( __FILE__, array( 'LFO_Database', 'activate' ) );
