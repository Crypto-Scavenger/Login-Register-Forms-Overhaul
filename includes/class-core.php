<?php
/**
 * Core functionality for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LRFO_Core {

	private $database;
	private $invite_codes;

	public function __construct( LRFO_Database $database, LRFO_Invite_Codes $invite_codes ) {
		$this->database     = $database;
		$this->invite_codes = $invite_codes;

		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_assets' ) );
		add_filter( 'login_errors', array( $this, 'filter_login_errors' ) );
		add_filter( 'login_headerurl', array( $this, 'change_login_logo_url' ) );
		add_action( 'login_head', array( $this, 'add_custom_logo_styles' ) );
		add_action( 'wp_logout', array( $this, 'custom_logout_redirect' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'register_form', array( $this, 'add_invite_code_field' ) );
		add_filter( 'registration_errors', array( $this, 'validate_invite_code' ), 10, 3 );
		add_action( 'user_register', array( $this, 'process_invite_code_usage' ) );
		add_filter( 'register_url', array( $this, 'maybe_disable_registration' ) );
		add_action( 'login_form_register', array( $this, 'maybe_block_registration_page' ) );
		add_filter( 'wp_pre_insert_user_data', array( $this, 'modify_user_data' ), 10, 4 );
		add_filter( 'registration_redirect', array( $this, 'registration_redirect' ) );
		add_action( 'init', array( $this, 'register_session_hooks' ) );
	}

	public function enqueue_login_assets() {
		if ( $this->check_bypass() ) {
			return;
		}

		if ( '1' === $this->database->get_setting( 'enable_custom_styles', '0' ) ) {
			wp_enqueue_style( 'lrfo-login', LRFO_URL . 'assets/login.css', array(), LRFO_VERSION );

			$custom_css = $this->database->get_setting( 'custom_css', '' );
			if ( ! empty( $custom_css ) ) {
				wp_add_inline_style( 'lrfo-login', wp_strip_all_tags( $custom_css ) );
			}
		}

		$custom_js = $this->database->get_setting( 'custom_js', '' );
		if ( ! empty( $custom_js ) ) {
			wp_enqueue_script( 'lrfo-login', LRFO_URL . 'assets/login.js', array( 'jquery' ), LRFO_VERSION, true );
			wp_add_inline_script( 'lrfo-login', wp_strip_all_tags( $custom_js ) );
		}
	}

	public function filter_login_errors( $error ) {
		if ( $this->check_bypass() ) {
			return $error;
		}

		if ( '1' === $this->database->get_setting( 'hide_login_errors', '0' ) ) {
			$custom_message = $this->database->get_setting( 'custom_login_error_message', '' );
			return ! empty( $custom_message ) ? $custom_message : __( 'Login failed. Please try again.', 'login-register-forms-overhaul' );
		}

		return $error;
	}

	public function change_login_logo_url( $url ) {
		if ( $this->check_bypass() ) {
			return $url;
		}

		$logo_url = $this->database->get_setting( 'logo_url', '' );
		return ! empty( $logo_url ) ? esc_url( $logo_url ) : home_url( '/' );
	}

	public function add_custom_logo_styles() {
		if ( $this->check_bypass() ) {
			return;
		}

		$logo_url = $this->database->get_setting( 'logo_url', '' );
		if ( ! empty( $logo_url ) ) {
			echo '<style>
				#login h1 a {
					background-image: url(' . esc_url( $logo_url ) . ');
					background-size: contain;
					width: 100%;
					height: 100px;
				}
			</style>';
		}
	}

	public function custom_logout_redirect() {
		if ( $this->check_bypass() ) {
			return;
		}

		$redirect_url = $this->database->get_setting( 'custom_logout_redirect', '' );
		if ( ! empty( $redirect_url ) ) {
			wp_safe_redirect( esc_url( $redirect_url ) );
			exit;
		}
	}

	public function enqueue_frontend_assets() {
		if ( $this->check_bypass() ) {
			return;
		}

		if ( '1' === $this->database->get_setting( 'hide_session_expire_modal', '0' ) ) {
			wp_enqueue_script( 'lrfo-frontend', LRFO_URL . 'assets/login.js', array( 'jquery' ), LRFO_VERSION, true );
			wp_localize_script(
				'lrfo-frontend',
				'lrfoData',
				array(
					'hideSessionExpire' => true,
				)
			);
		}
	}

	public function add_invite_code_field() {
		if ( $this->check_bypass() ) {
			return;
		}

		if ( '1' !== $this->database->get_setting( 'require_invite_code', '1' ) ) {
			return;
		}

		$value = isset( $_POST['invite_code'] ) ? sanitize_text_field( wp_unslash( $_POST['invite_code'] ) ) : '';
		?>
		<p>
			<label for="invite_code"><?php esc_html_e( 'Invite Code', 'login-register-forms-overhaul' ); ?><br />
			<input type="text" name="invite_code" id="invite_code" class="input" value="<?php echo esc_attr( $value ); ?>" size="25" required /></label>
		</p>
		<?php
	}

	public function validate_invite_code( $errors, $sanitized_user_login, $user_email ) {
		if ( $this->check_bypass() ) {
			return $errors;
		}

		if ( '1' !== $this->database->get_setting( 'require_invite_code', '1' ) ) {
			return $errors;
		}

		if ( ! isset( $_POST['invite_code'] ) ) {
			$errors->add( 'invite_code_missing', __( 'Please enter an invite code.', 'login-register-forms-overhaul' ) );
			return $errors;
		}

		$invite_code = sanitize_text_field( wp_unslash( $_POST['invite_code'] ) );
		$ip_address  = $this->get_client_ip();
		$validation  = $this->invite_codes->validate_code( $invite_code, $ip_address );

		if ( is_wp_error( $validation ) ) {
			$errors->add( 'invite_code_invalid', $validation->get_error_message() );
		}

		return $errors;
	}

	public function process_invite_code_usage( $user_id ) {
		if ( $this->check_bypass() ) {
			return;
		}

		if ( '1' !== $this->database->get_setting( 'require_invite_code', '1' ) ) {
			return;
		}

		if ( ! isset( $_POST['invite_code'] ) ) {
			return;
		}

		$invite_code = sanitize_text_field( wp_unslash( $_POST['invite_code'] ) );
		$ip_address  = $this->get_client_ip();
		$code        = $this->invite_codes->validate_code( $invite_code, $ip_address );

		if ( is_wp_error( $code ) ) {
			return;
		}

		$this->invite_codes->use_code( $code['id'], $user_id, $ip_address );

		$user = get_userdata( $user_id );
		if ( $user && ! empty( $code['user_role'] ) ) {
			$user->set_role( $code['user_role'] );
		}
	}

	public function maybe_disable_registration( $url ) {
		if ( $this->check_bypass() ) {
			return $url;
		}

		if ( '1' === $this->database->get_setting( 'disable_registration', '0' ) ) {
			return '';
		}

		return $url;
	}

	public function maybe_block_registration_page() {
		if ( $this->check_bypass() ) {
			return;
		}

		if ( '1' === $this->database->get_setting( 'disable_registration', '0' ) ) {
			wp_die( esc_html__( 'User registration is currently disabled.', 'login-register-forms-overhaul' ) );
		}
	}

	public function modify_user_data( $data, $update, $user_id, $userdata ) {
		if ( $update || $this->check_bypass() ) {
			return $data;
		}

		if ( '1' === $this->database->get_setting( 'remove_email_requirement', '1' ) ) {
			if ( empty( $data['user_email'] ) ) {
				$username         = sanitize_user( $data['user_login'] );
				$data['user_email'] = $username . '@localhost.local';
			}
		}

		return $data;
	}

	public function registration_redirect( $redirect_to ) {
		if ( $this->check_bypass() ) {
			return $redirect_to;
		}

		if ( '1' === $this->database->get_setting( 'hide_registration_errors', '0' ) ) {
			return home_url();
		}

		return $redirect_to;
	}

	public function register_session_hooks() {
		if ( $this->check_bypass() ) {
			return;
		}

		if ( '1' === $this->database->get_setting( 'hide_session_expire_modal', '0' ) ) {
			add_filter( 'auth_cookie_expiration', array( $this, 'extend_session' ), 10, 3 );
		}
	}

	public function extend_session( $expiration, $user_id, $remember ) {
		return $remember ? $expiration : YEAR_IN_SECONDS;
	}

	private function check_bypass() {
		if ( '1' === $this->database->get_setting( 'enable_role_exceptions', '0' ) ) {
			$exception_roles = $this->database->get_setting( 'exception_roles', '' );
			if ( ! empty( $exception_roles ) ) {
				$roles = array_map( 'trim', explode( ',', $exception_roles ) );
				$user  = wp_get_current_user();

				if ( $user && $user->ID > 0 ) {
					foreach ( $roles as $role ) {
						if ( in_array( $role, (array) $user->roles, true ) ) {
							return true;
						}
					}
				}
			}
		}

		if ( '1' === $this->database->get_setting( 'enable_ip_allowlist', '0' ) ) {
			$ip_allowlist = $this->database->get_setting( 'ip_allowlist', '' );
			if ( ! empty( $ip_allowlist ) ) {
				$allowed_ips = array_map( 'trim', explode( "\n", $ip_allowlist ) );
				$client_ip   = $this->get_client_ip();

				if ( in_array( $client_ip, $allowed_ips, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
