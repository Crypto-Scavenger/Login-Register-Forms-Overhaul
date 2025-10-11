<?php
/**
 * Core functionality
 *
 * @package LoginFormsOverhaul
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LFO_Core {

	private $database;
	private $settings = null;

	public function __construct( $database ) {
		$this->database = $database;
		
		if ( $this->should_apply_customizations() ) {
			$this->init_hooks();
		}
	}

	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->database->get_all_settings();
		}
		return $this->settings;
	}

	private function should_apply_customizations() {
		$settings = $this->get_settings();
		
		if ( '1' !== ( $settings['enable_plugin'] ?? '1' ) ) {
			return false;
		}

		$role_exceptions = $settings['role_exceptions'] ?? array();
		if ( ! empty( $role_exceptions ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
			foreach ( $role_exceptions as $role ) {
				if ( in_array( $role, $user->roles, true ) ) {
					return false;
				}
			}
		}

		$ip_allowlist = $settings['ip_allowlist'] ?? array();
		if ( ! empty( $ip_allowlist ) ) {
			$user_ip = $this->get_user_ip();
			if ( in_array( $user_ip, $ip_allowlist, true ) ) {
				return false;
			}
		}
		
		return true;
	}

	private function get_user_ip() {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip = explode( ',', $ip );
			return trim( $ip[0] );
		}
		
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		
		return '';
	}

	private function init_hooks() {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_assets' ) );
		add_action( 'login_head', array( $this, 'inject_custom_styles' ) );
		add_action( 'login_footer', array( $this, 'inject_custom_scripts' ) );
		add_filter( 'login_headerurl', array( $this, 'custom_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'custom_logo_title' ) );
		add_filter( 'login_errors', array( $this, 'filter_login_errors' ) );
		add_filter( 'login_redirect', array( $this, 'custom_login_redirect' ), 10, 3 );
		add_filter( 'logout_redirect', array( $this, 'custom_logout_redirect' ) );
		add_action( 'wp_logout', array( $this, 'handle_logout' ) );
		add_filter( 'heartbeat_received', array( $this, 'filter_heartbeat' ), 10, 2 );
		add_action( 'login_footer', array( $this, 'hide_form_elements' ) );
	}

	public function enqueue_login_assets() {
		$settings = $this->get_settings();
		
		if ( '1' === ( $settings['use_default_styles'] ?? '1' ) ) {
			wp_enqueue_style(
				'lfo-login',
				LFO_URL . 'assets/login.css',
				array(),
				LFO_VERSION
			);
		}
		
		wp_enqueue_script(
			'lfo-login',
			LFO_URL . 'assets/login.js',
			array(),
			LFO_VERSION,
			true
		);

		wp_localize_script(
			'lfo-login',
			'lfoSettings',
			array(
				'hideSessionExpire' => $settings['hide_session_expire'] ?? '0',
			)
		);
	}

	public function inject_custom_styles() {
		$settings = $this->get_settings();
		$custom_css = $settings['custom_css'] ?? '';
		
		if ( ! empty( $custom_css ) ) {
			echo '<style type="text/css">' . "\n";
			echo wp_strip_all_tags( $custom_css );
			echo "\n</style>\n";
		}
	}

	public function inject_custom_scripts() {
		$settings = $this->get_settings();
		$custom_js = $settings['custom_js'] ?? '';
		
		if ( ! empty( $custom_js ) ) {
			echo '<script type="text/javascript">' . "\n";
			echo wp_strip_all_tags( $custom_js );
			echo "\n</script>\n";
		}
	}

	public function custom_logo_url() {
		$settings = $this->get_settings();
		$logo_link = $settings['logo_link_url'] ?? home_url();
		return ! empty( $logo_link ) ? esc_url( $logo_link ) : home_url();
	}

	public function custom_logo_title() {
		return esc_html( get_bloginfo( 'name' ) );
	}

	public function filter_login_errors( $errors ) {
		$settings = $this->get_settings();
		
		if ( '1' === ( $settings['hide_login_errors'] ?? '0' ) ) {
			$custom_message = $settings['custom_error_message'] ?? 'Invalid credentials.';
			return '<strong>' . esc_html( $custom_message ) . '</strong>';
		}
		
		return $errors;
	}

	public function custom_login_redirect( $redirect_to, $request, $user ) {
		$settings = $this->get_settings();
		
		if ( is_wp_error( $user ) ) {
			return $redirect_to;
		}
		
		if ( in_array( 'subscriber', $user->roles, true ) ) {
			$subscriber_redirect = $settings['login_redirect_subscriber'] ?? '';
			if ( ! empty( $subscriber_redirect ) ) {
				return esc_url_raw( $subscriber_redirect );
			}
		}
		
		return $redirect_to;
	}

	public function custom_logout_redirect() {
		$settings = $this->get_settings();
		$logout_redirect = $settings['logout_redirect_url'] ?? '';
		
		if ( ! empty( $logout_redirect ) ) {
			return esc_url_raw( $logout_redirect );
		}
		
		return home_url();
	}

	public function handle_logout() {
		$settings = $this->get_settings();
		
		if ( '1' === ( $settings['logout_skip_confirmation'] ?? '0' ) ) {
			$logout_redirect = $settings['logout_redirect_url'] ?? home_url();
			wp_safe_redirect( esc_url_raw( $logout_redirect ) );
			exit;
		}
	}

	public function filter_heartbeat( $response, $data ) {
		$settings = $this->get_settings();
		
		if ( '1' === ( $settings['hide_session_expire'] ?? '0' ) ) {
			if ( isset( $data['wp_autosave'] ) ) {
				unset( $response['wp-refresh-post-lock'] );
			}
		}
		
		return $response;
	}

	public function hide_form_elements() {
		$settings = $this->get_settings();
		$hide_elements = array();
		
		if ( '1' === ( $settings['disable_language_switcher'] ?? '0' ) ) {
			$hide_elements[] = '.language-switcher';
		}
		
		if ( '1' === ( $settings['disable_privacy_link'] ?? '0' ) ) {
			$hide_elements[] = '.privacy-policy-page-link';
		}
		
		if ( '1' === ( $settings['disable_back_to_site'] ?? '0' ) ) {
			$hide_elements[] = '#backtoblog';
		}
		
		if ( ! empty( $hide_elements ) ) {
			echo '<style type="text/css">';
			echo esc_html( implode( ',', $hide_elements ) );
			echo '{display:none!important;}';
			echo '</style>';
		}
	}
}
