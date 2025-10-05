<?php
/**
 * Database operations for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LRFO_Database {

	private $settings_table;
	private $codes_table;
	private $usage_table;
	private $settings_cache = array();

	public function __construct() {
		global $wpdb;
		$this->settings_table = $wpdb->prefix . 'lrfo_settings';
		$this->codes_table    = $wpdb->prefix . 'lrfo_invite_codes';
		$this->usage_table    = $wpdb->prefix . 'lrfo_code_usage';
	}

	public static function activate() {
		$instance = new self();
		$instance->create_tables();
		$instance->insert_defaults();
	}

	private function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$settings_sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			) %s',
			$this->settings_table,
			$charset_collate
		);

		$codes_sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code_string varchar(191) NOT NULL,
				usage_limit int(11) NOT NULL DEFAULT 1,
				uses_remaining int(11) NOT NULL,
				total_uses int(11) NOT NULL DEFAULT 0,
				expiry_date bigint(20),
				user_role varchar(50) NOT NULL DEFAULT "subscriber",
				created_at datetime NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (id),
				UNIQUE KEY code_string (code_string),
				KEY is_active (is_active)
			) %s',
			$this->codes_table,
			$charset_collate
		);

		$usage_sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code_id bigint(20) unsigned,
				ip_address varchar(100),
				attempt_type varchar(20) NOT NULL,
				user_id bigint(20) unsigned,
				attempted_at datetime NOT NULL,
				PRIMARY KEY (id),
				KEY code_id (code_id),
				KEY ip_address (ip_address),
				KEY attempted_at (attempted_at)
			) %s',
			$this->usage_table,
			$charset_collate
		);

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $settings_sql );
		dbDelta( $codes_sql );
		dbDelta( $usage_sql );
	}

	private function insert_defaults() {
		$defaults = array(
			'enable_custom_styles'        => '0',
			'custom_css'                  => '',
			'custom_js'                   => '',
			'logo_url'                    => '',
			'hide_login_errors'           => '0',
			'custom_login_error_message'  => '',
			'hide_registration_errors'    => '0',
			'disable_registration'        => '0',
			'custom_logout_redirect'      => '',
			'hide_session_expire_modal'   => '0',
			'enable_role_exceptions'      => '0',
			'exception_roles'             => '',
			'enable_ip_allowlist'         => '0',
			'ip_allowlist'                => '',
			'require_invite_code'         => '1',
			'remove_email_requirement'    => '1',
			'rate_limit_enabled'          => '1',
			'rate_limit_attempts'         => '3',
			'rate_limit_window'           => '60',
			'notification_email'          => get_option( 'admin_email' ),
			'notify_code_exhausted'       => '1',
			'cleanup_on_uninstall'        => '0',
		);

		foreach ( $defaults as $key => $value ) {
			$this->save_setting( $key, $value );
		}
	}

	public function save_setting( $key, $value ) {
		global $wpdb;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE setting_key = %s',
				$this->settings_table,
				$key
			)
		);

		if ( $existing ) {
			$result = $wpdb->update(
				$this->settings_table,
				array( 'setting_value' => $value ),
				array( 'setting_key' => $key ),
				array( '%s' ),
				array( '%s' )
			);
		} else {
			$result = $wpdb->insert(
				$this->settings_table,
				array(
					'setting_key'   => $key,
					'setting_value' => $value,
				),
				array( '%s', '%s' )
			);
		}

		if ( false !== $result ) {
			$this->settings_cache[ $key ] = $value;
			wp_cache_delete( 'lrfo_setting_' . $key, 'lrfo_settings' );
		}

		return false !== $result;
	}

	public function get_setting( $key, $default = '' ) {
		if ( isset( $this->settings_cache[ $key ] ) ) {
			return $this->settings_cache[ $key ];
		}

		$cache_key = 'lrfo_setting_' . $key;
		$cached    = wp_cache_get( $cache_key, 'lrfo_settings' );

		if ( false !== $cached ) {
			$this->settings_cache[ $key ] = $cached;
			return $cached;
		}

		global $wpdb;
		$value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT setting_value FROM %i WHERE setting_key = %s',
				$this->settings_table,
				$key
			)
		);

		$value = ( null !== $value ) ? $value : $default;
		$this->settings_cache[ $key ] = $value;
		wp_cache_set( $cache_key, $value, 'lrfo_settings', 3600 );

		return $value;
	}

	public function get_all_settings() {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT setting_key, setting_value FROM %i',
				$this->settings_table
			),
			ARRAY_A
		);

		$settings = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$settings[ $row['setting_key'] ] = $row['setting_value'];
				$this->settings_cache[ $row['setting_key'] ] = $row['setting_value'];
			}
		}

		return $settings;
	}
}
