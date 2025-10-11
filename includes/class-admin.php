<?php
/**
 * Admin functionality
 *
 * @package LoginFormsOverhaul
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LFO_Admin {

	private $database;
	private $settings = null;

	public function __construct( $database ) {
		$this->database = $database;
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_lfo_save_settings', array( $this, 'save_settings' ) );
	}

	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->database->get_all_settings();
		}
		return $this->settings;
	}

	public function add_admin_menu() {
		add_submenu_page(
			'themes.php',
			__( 'Login Forms Overhaul', 'login-forms-overhaul' ),
			__( 'Login Forms', 'login-forms-overhaul' ),
			'manage_options',
			'login-forms-overhaul',
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'appearance_page_login-forms-overhaul' !== $hook ) {
			return;
		}
		
		wp_enqueue_style(
			'lfo-admin',
			LFO_URL . 'assets/admin.css',
			array(),
			LFO_VERSION
		);
		
		wp_enqueue_script(
			'lfo-admin',
			LFO_URL . 'assets/admin.js',
			array( 'jquery' ),
			LFO_VERSION,
			true
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-forms-overhaul' ) );
		}
		
		$settings = $this->get_settings();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		?>
		<div class="wrap">
			<h1><i class="fas fa-sign-in-alt"></i> <?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'login-forms-overhaul' ); ?></p>
				</div>
			<?php endif; ?>
			
			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'themes.php?page=login-forms-overhaul&tab=general' ) ); ?>" 
				   class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'login-forms-overhaul' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'themes.php?page=login-forms-overhaul&tab=styling' ) ); ?>" 
				   class="nav-tab <?php echo 'styling' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Styling', 'login-forms-overhaul' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'themes.php?page=login-forms-overhaul&tab=behavior' ) ); ?>" 
				   class="nav-tab <?php echo 'behavior' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Behavior', 'login-forms-overhaul' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'themes.php?page=login-forms-overhaul&tab=access' ) ); ?>" 
				   class="nav-tab <?php echo 'access' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Access Control', 'login-forms-overhaul' ); ?>
				</a>
			</h2>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'lfo_save_settings', 'lfo_nonce' ); ?>
				<input type="hidden" name="action" value="lfo_save_settings">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
				
				<?php
				switch ( $active_tab ) {
					case 'styling':
						$this->render_styling_tab( $settings );
						break;
					case 'behavior':
						$this->render_behavior_tab( $settings );
						break;
					case 'access':
						$this->render_access_tab( $settings );
						break;
					default:
						$this->render_general_tab( $settings );
				}
				?>
				
				<?php submit_button(); ?>
			</form>
			
			<div class="lfo-preview-notice">
				<p>
					<i class="fas fa-info-circle"></i>
					<?php esc_html_e( 'To preview your login page customizations, open an incognito/private window and visit:', 'login-forms-overhaul' ); ?>
					<a href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank"><?php echo esc_url( wp_login_url() ); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	private function render_general_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Plugin', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="enable_plugin" value="1" <?php checked( '1', $settings['enable_plugin'] ?? '1' ); ?>>
						<?php esc_html_e( 'Enable login forms customization', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Cleanup on Uninstall', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="cleanup_on_uninstall" value="1" <?php checked( '1', $settings['cleanup_on_uninstall'] ?? '1' ); ?>>
						<?php esc_html_e( 'Remove all plugin data when uninstalling', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_styling_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Use Default Dark Theme', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="use_default_styles" value="1" <?php checked( '1', $settings['use_default_styles'] ?? '1' ); ?>>
						<?php esc_html_e( 'Apply cyberpunk dark theme styling', 'login-forms-overhaul' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Applies futuristic dark theme with #262626 background, #ffffff text, and #d11c1c accents', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="logo_url"><?php esc_html_e( 'Custom Logo URL', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<input type="url" id="logo_url" name="logo_url" value="<?php echo esc_attr( $settings['logo_url'] ?? '' ); ?>" class="regular-text">
					<p class="description">
						<?php esc_html_e( 'URL to custom logo image (leave empty to use default WordPress logo)', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="logo_link_url"><?php esc_html_e( 'Logo Link URL', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<input type="url" id="logo_link_url" name="logo_link_url" value="<?php echo esc_attr( $settings['logo_link_url'] ?? home_url() ); ?>" class="regular-text">
					<p class="description">
						<?php esc_html_e( 'URL when clicking the logo (defaults to home page)', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="custom_css"><?php esc_html_e( 'Custom CSS', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<textarea id="custom_css" name="custom_css" rows="10" class="large-text code"><?php echo esc_textarea( $settings['custom_css'] ?? '' ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Add custom CSS styles to the login page', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="custom_js"><?php esc_html_e( 'Custom JavaScript', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<textarea id="custom_js" name="custom_js" rows="10" class="large-text code"><?php echo esc_textarea( $settings['custom_js'] ?? '' ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Add custom JavaScript to the login page (without <script> tags)', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_behavior_tab( $settings ) {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Hide Login Errors', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="hide_login_errors" value="1" <?php checked( '1', $settings['hide_login_errors'] ?? '0' ); ?>>
						<?php esc_html_e( 'Hide detailed error messages on failed login', 'login-forms-overhaul' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Security best practice: prevents username enumeration attacks', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="custom_error_message"><?php esc_html_e( 'Custom Error Message', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<input type="text" id="custom_error_message" name="custom_error_message" value="<?php echo esc_attr( $settings['custom_error_message'] ?? 'Invalid credentials.' ); ?>" class="regular-text">
					<p class="description">
						<?php esc_html_e( 'Generic message shown when "Hide Login Errors" is enabled', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Language Switcher', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="disable_language_switcher" value="1" <?php checked( '1', $settings['disable_language_switcher'] ?? '0' ); ?>>
						<?php esc_html_e( 'Hide language selection dropdown', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Privacy Link', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="disable_privacy_link" value="1" <?php checked( '1', $settings['disable_privacy_link'] ?? '0' ); ?>>
						<?php esc_html_e( 'Hide "Privacy Policy" link', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable Back to Site Link', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="disable_back_to_site" value="1" <?php checked( '1', $settings['disable_back_to_site'] ?? '0' ); ?>>
						<?php esc_html_e( 'Hide "← Go to [Site Name]" link', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="login_redirect_subscriber"><?php esc_html_e( 'Subscriber Login Redirect', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<input type="url" id="login_redirect_subscriber" name="login_redirect_subscriber" value="<?php echo esc_attr( $settings['login_redirect_subscriber'] ?? '' ); ?>" class="regular-text">
					<p class="description">
						<?php esc_html_e( 'Custom URL to redirect subscribers after login (leave empty for default)', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="logout_redirect_url"><?php esc_html_e( 'Logout Redirect URL', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<input type="url" id="logout_redirect_url" name="logout_redirect_url" value="<?php echo esc_attr( $settings['logout_redirect_url'] ?? '' ); ?>" class="regular-text">
					<p class="description">
						<?php esc_html_e( 'Custom URL to redirect after logout (defaults to home page)', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Skip Logout Confirmation', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="logout_skip_confirmation" value="1" <?php checked( '1', $settings['logout_skip_confirmation'] ?? '0' ); ?>>
						<?php esc_html_e( 'Immediately logout without confirmation page', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Hide Session Expire Modal', 'login-forms-overhaul' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="hide_session_expire" value="1" <?php checked( '1', $settings['hide_session_expire'] ?? '0' ); ?>>
						<?php esc_html_e( 'Suppress session expiration warnings', 'login-forms-overhaul' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	private function render_access_tab( $settings ) {
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		$role_exceptions = $settings['role_exceptions'] ?? array();
		$ip_allowlist = $settings['ip_allowlist'] ?? array();
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Role Exceptions', 'login-forms-overhaul' ); ?></th>
				<td>
					<?php foreach ( $all_roles as $role_key => $role_info ) : ?>
						<label style="display: block; margin-bottom: 5px;">
							<input type="checkbox" name="role_exceptions[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $role_exceptions, true ) ); ?>>
							<?php echo esc_html( $role_info['name'] ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description">
						<?php esc_html_e( 'Selected roles will see default WordPress login page instead of customizations', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="ip_allowlist"><?php esc_html_e( 'IP Allowlist', 'login-forms-overhaul' ); ?></label>
				</th>
				<td>
					<textarea id="ip_allowlist" name="ip_allowlist" rows="5" class="large-text code"><?php echo esc_textarea( is_array( $ip_allowlist ) ? implode( "\n", $ip_allowlist ) : '' ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'One IP address per line. These IPs will bypass all customizations and see the default login page.', 'login-forms-overhaul' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-forms-overhaul' ) );
		}
		
		if ( ! isset( $_POST['lfo_nonce'] ) || 
			 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lfo_nonce'] ) ), 'lfo_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'login-forms-overhaul' ) );
		}
		
		$tab = isset( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : 'general';
		
		switch ( $tab ) {
			case 'general':
				$this->save_general_settings();
				break;
			case 'styling':
				$this->save_styling_settings();
				break;
			case 'behavior':
				$this->save_behavior_settings();
				break;
			case 'access':
				$this->save_access_settings();
				break;
		}
		
		wp_safe_redirect( add_query_arg(
			array(
				'page' => 'login-forms-overhaul',
				'tab' => $tab,
				'settings-updated' => 'true',
			),
			admin_url( 'themes.php' )
		) );
		exit;
	}

	private function save_general_settings() {
		$settings = array(
			'enable_plugin' => isset( $_POST['enable_plugin'] ) ? '1' : '0',
			'cleanup_on_uninstall' => isset( $_POST['cleanup_on_uninstall'] ) ? '1' : '0',
		);
		
		foreach ( $settings as $key => $value ) {
			$this->database->save_setting( $key, $value );
		}
	}

	private function save_styling_settings() {
		$settings = array(
			'use_default_styles' => isset( $_POST['use_default_styles'] ) ? '1' : '0',
			'custom_css' => isset( $_POST['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ) ) : '',
			'custom_js' => isset( $_POST['custom_js'] ) ? wp_strip_all_tags( wp_unslash( $_POST['custom_js'] ) ) : '',
			'logo_url' => isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : '',
			'logo_link_url' => isset( $_POST['logo_link_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_link_url'] ) ) : home_url(),
		);
		
		foreach ( $settings as $key => $value ) {
			$this->database->save_setting( $key, $value );
		}
	}

	private function save_behavior_settings() {
		$settings = array(
			'hide_login_errors' => isset( $_POST['hide_login_errors'] ) ? '1' : '0',
			'custom_error_message' => isset( $_POST['custom_error_message'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_error_message'] ) ) : 'Invalid credentials.',
			'disable_language_switcher' => isset( $_POST['disable_language_switcher'] ) ? '1' : '0',
			'disable_privacy_link' => isset( $_POST['disable_privacy_link'] ) ? '1' : '0',
			'disable_back_to_site' => isset( $_POST['disable_back_to_site'] ) ? '1' : '0',
			'logout_redirect_url' => isset( $_POST['logout_redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['logout_redirect_url'] ) ) : '',
			'logout_skip_confirmation' => isset( $_POST['logout_skip_confirmation'] ) ? '1' : '0',
			'login_redirect_subscriber' => isset( $_POST['login_redirect_subscriber'] ) ? esc_url_raw( wp_unslash( $_POST['login_redirect_subscriber'] ) ) : '',
			'hide_session_expire' => isset( $_POST['hide_session_expire'] ) ? '1' : '0',
		);
		
		foreach ( $settings as $key => $value ) {
			$this->database->save_setting( $key, $value );
		}
	}

	private function save_access_settings() {
		if ( isset( $_POST['role_exceptions'] ) && is_array( $_POST['role_exceptions'] ) ) {
			$role_exceptions = array_map( 'sanitize_text_field', wp_unslash( $_POST['role_exceptions'] ) );
		} else {
			$role_exceptions = array();
		}
		$this->database->save_setting( 'role_exceptions', $role_exceptions );
		
		if ( isset( $_POST['ip_allowlist'] ) ) {
			$ip_list = sanitize_textarea_field( wp_unslash( $_POST['ip_allowlist'] ) );
			$ips = array_filter( array_map( 'trim', explode( "\n", $ip_list ) ) );
		} else {
			$ips = array();
		}
		$this->database->save_setting( 'ip_allowlist', $ips );
	}
}
