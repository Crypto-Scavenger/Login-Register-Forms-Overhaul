<?php
/**
 * Database operations
 *
 * @package LoginFormsOverhaul
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LFO_Database {

	private $settings_cache = null;
	private $table_verified = null;

	private function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'lfo_settings';
	}

	private function ensure_table_exists() {
		if ( null !== $this->table_verified ) {
			return $this->table_verified;
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table
		) );
		
		if ( $table !== $table_exists ) {
			$this->create_tables();
			$table_exists = $wpdb->get_var( $wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table
			) );
		}
		
		$this->table_verified = ( $table === $table_exists );
		return $this->table_verified;
	}

	public static function activate() {
		$instance = new self();
		$instance->create_tables();
		$instance->initialize_defaults();
	}

	private function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table = $this->get_table_name();
		
		$sql = $wpdb->prepare(
			'CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			)',
			$table
		) . ' ' . $charset_collate;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private function initialize_defaults() {
		$defaults = array(
			'enable_plugin' => '1',
			'use_default_styles' => '1',
			'custom_css' => '',
			'custom_js' => '',
			'logo_url' => '',
			'logo_link_url' => home_url(),
			'hide_login_errors' => '0',
			'custom_error_message' => 'Invalid credentials.',
			'disable_language_switcher' => '0',
			'disable_privacy_link' => '0',
			'disable_back_to_site' => '0',
			'logout_redirect_url' => '',
			'logout_skip_confirmation' => '0',
			'login_redirect_subscriber' => '',
			'hide_session_expire' => '0',
			'role_exceptions' => array(),
			'ip_allowlist' => array(),
			'cleanup_on_uninstall' => '1',
		);
		
		foreach ( $defaults as $key => $value ) {
			if ( false === $this->get_setting( $key ) ) {
				$this->save_setting( $key, $value );
			}
		}
	}

	public function get_setting( $key, $default = false ) {
		if ( ! $this->ensure_table_exists() ) {
			return $default;
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$value = $wpdb->get_var( $wpdb->prepare(
			'SELECT setting_value FROM %i WHERE setting_key = %s',
			$table,
			$key
		) );
		
		if ( null === $value ) {
			return $default;
		}
		
		return maybe_unserialize( $value );
	}

	public function get_all_settings() {
		if ( null !== $this->settings_cache ) {
			return $this->settings_cache;
		}

		if ( ! $this->ensure_table_exists() ) {
			return array();
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$results = $wpdb->get_results(
			$wpdb->prepare( 'SELECT setting_key, setting_value FROM %i', $table ),
			ARRAY_A
		);
		
		if ( ! is_array( $results ) ) {
			return array();
		}
		
		$settings = array();
		foreach ( $results as $row ) {
			$key = $row['setting_key'] ?? '';
			$value = $row['setting_value'] ?? '';
			if ( ! empty( $key ) ) {
				$settings[ $key ] = maybe_unserialize( $value );
			}
		}
		
		$this->settings_cache = $settings;
		return $settings;
	}

	public function save_setting( $key, $value ) {
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}

		global $wpdb;
		$table = $this->get_table_name();
		
		$result = $wpdb->replace(
			$table,
			array(
				'setting_key'   => $key,
				'setting_value' => maybe_serialize( $value ),
			),
			array( '%s', '%s' )
		);
		
		if ( false !== $result ) {
			$this->settings_cache = null;
		}
		
		return false !== $result;
	}

	public static function uninstall() {
		$instance = new self();
		$cleanup = $instance->get_setting( 'cleanup_on_uninstall', '1' );
		
		if ( '1' !== $cleanup ) {
			return;
		}
		
		global $wpdb;
		$table = $instance->get_table_name();
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
	}
}
