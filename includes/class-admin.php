<?php
/**
 * Admin interface for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LRFO_Admin {

	private $database;
	private $invite_codes;

	public function __construct( LRFO_Database $database, LRFO_Invite_Codes $invite_codes ) {
		$this->database     = $database;
		$this->invite_codes = $invite_codes;

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_lrfo_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_lrfo_create_code', array( $this, 'create_code' ) );
		add_action( 'admin_post_lrfo_bulk_generate', array( $this, 'bulk_generate' ) );
		add_action( 'admin_post_lrfo_delete_code', array( $this, 'delete_code' ) );
		add_action( 'lrfo_delete_exhausted_code', array( $this, 'delete_exhausted_code' ) );
	}

	public function add_admin_menu() {
		add_users_page(
			__( 'Login & Register Forms', 'login-register-forms-overhaul' ),
			__( 'Login & Register', 'login-register-forms-overhaul' ),
			'manage_options',
			'lrfo-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'users_page_lrfo-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'lrfo-admin', LRFO_URL . 'assets/admin.css', array(), LRFO_VERSION );
		wp_enqueue_script( 'lrfo-admin', LRFO_URL . 'assets/admin.js', array( 'jquery' ), LRFO_VERSION, true );
		wp_enqueue_media();
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-register-forms-overhaul' ) );
		}

		$settings     = $this->database->get_all_settings();
		$active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'visual';
		$codes        = $this->invite_codes->get_all_codes( true );
		$stats        = $this->invite_codes->get_usage_stats();
		?>
		<div class="wrap lrfo-admin">
			<h1><?php esc_html_e( 'Login & Register Forms Overhaul', 'login-register-forms-overhaul' ); ?></h1>

			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'login-register-forms-overhaul' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['code-created'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Invite code created successfully.', 'login-register-forms-overhaul' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['codes-generated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php printf( esc_html__( '%d invite codes generated successfully.', 'login-register-forms-overhaul' ), intval( $_GET['count'] ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['code-deleted'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Invite code deleted successfully.', 'login-register-forms-overhaul' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a href="?page=lrfo-settings&tab=visual" class="nav-tab <?php echo 'visual' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Visual Styles', 'login-register-forms-overhaul' ); ?>
				</a>
				<a href="?page=lrfo-settings&tab=behavior" class="nav-tab <?php echo 'behavior' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Behavior', 'login-register-forms-overhaul' ); ?>
				</a>
				<a href="?page=lrfo-settings&tab=invite-codes" class="nav-tab <?php echo 'invite-codes' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Invite Codes', 'login-register-forms-overhaul' ); ?>
				</a>
				<a href="?page=lrfo-settings&tab=advanced" class="nav-tab <?php echo 'advanced' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Advanced', 'login-register-forms-overhaul' ); ?>
				</a>
			</nav>

			<div class="lrfo-tab-content">
				<?php
				switch ( $active_tab ) {
					case 'visual':
						$this->render_visual_tab( $settings );
						break;
					case 'behavior':
						$this->render_behavior_tab( $settings );
						break;
					case 'invite-codes':
						$this->render_invite_codes_tab( $codes, $stats );
						break;
					case 'advanced':
						$this->render_advanced_tab( $settings );
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	private function render_visual_tab( $settings ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lrfo_save_settings">
			<?php wp_nonce_field( 'lrfo_save_settings', 'lrfo_nonce' ); ?>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="enable_custom_styles">
								<?php esc_html_e( 'Enable Custom Styles', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="enable_custom_styles" name="enable_custom_styles" value="1" <?php checked( '1', $settings['enable_custom_styles'] ); ?>>
								<?php esc_html_e( 'Apply custom CSS to login pages', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="logo_url">
								<?php esc_html_e( 'Custom Logo URL', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<input type="text" id="logo_url" name="logo_url" value="<?php echo esc_attr( $settings['logo_url'] ); ?>" class="regular-text">
							<button type="button" class="button lrfo-upload-image"><?php esc_html_e( 'Upload Image', 'login-register-forms-overhaul' ); ?></button>
							<p class="description"><?php esc_html_e( 'Replace the WordPress logo on the login page.', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="custom_css">
								<?php esc_html_e( 'Custom CSS', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<textarea id="custom_css" name="custom_css" rows="10" class="large-text code"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Add custom CSS for login pages. Will be sanitized for security.', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="custom_js">
								<?php esc_html_e( 'Custom JavaScript', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<textarea id="custom_js" name="custom_js" rows="10" class="large-text code"><?php echo esc_textarea( $settings['custom_js'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Add custom JavaScript for login pages. Will be sanitized for security.', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_behavior_tab( $settings ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lrfo_save_settings">
			<?php wp_nonce_field( 'lrfo_save_settings', 'lrfo_nonce' ); ?>

			<h2><?php esc_html_e( 'Login Behavior', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hide_login_errors">
								<?php esc_html_e( 'Hide Login Errors', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="hide_login_errors" name="hide_login_errors" value="1" <?php checked( '1', $settings['hide_login_errors'] ); ?>>
								<?php esc_html_e( 'Hide specific error messages (wrong password, unknown user)', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="custom_login_error_message">
								<?php esc_html_e( 'Custom Error Message', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<input type="text" id="custom_login_error_message" name="custom_login_error_message" value="<?php echo esc_attr( $settings['custom_login_error_message'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Replace all login errors with this message. Leave empty for default.', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Registration Behavior', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="disable_registration">
								<?php esc_html_e( 'Disable Registration', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="disable_registration" name="disable_registration" value="1" <?php checked( '1', $settings['disable_registration'] ); ?>>
								<?php esc_html_e( 'Completely disable user registration', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="hide_registration_errors">
								<?php esc_html_e( 'Hide Registration Errors', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="hide_registration_errors" name="hide_registration_errors" value="1" <?php checked( '1', $settings['hide_registration_errors'] ); ?>>
								<?php esc_html_e( 'Hide registration validation messages', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="remove_email_requirement">
								<?php esc_html_e( 'Remove Email Requirement', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="remove_email_requirement" name="remove_email_requirement" value="1" <?php checked( '1', $settings['remove_email_requirement'] ); ?>>
								<?php esc_html_e( 'Allow registration with username and password only', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Logout Behavior', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="custom_logout_redirect">
								<?php esc_html_e( 'Custom Logout Redirect', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<input type="url" id="custom_logout_redirect" name="custom_logout_redirect" value="<?php echo esc_attr( $settings['custom_logout_redirect'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Redirect users to this URL after logout. Leave empty for default.', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Session Management', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="hide_session_expire_modal">
								<?php esc_html_e( 'Hide Session Expire Modal', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="hide_session_expire_modal" name="hide_session_expire_modal" value="1" <?php checked( '1', $settings['hide_session_expire_modal'] ); ?>>
								<?php esc_html_e( 'Prevent session expiration popups from appearing', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	private function render_invite_codes_tab( $codes, $stats ) {
		$editable_roles = get_editable_roles();
		?>
		<div class="lrfo-invite-codes">
			<h2><?php esc_html_e( 'Invite Code System', 'login-register-forms-overhaul' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lrfo_save_settings">
				<?php wp_nonce_field( 'lrfo_save_settings', 'lrfo_nonce' ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="require_invite_code">
									<?php esc_html_e( 'Require Invite Code', 'login-register-forms-overhaul' ); ?>
								</label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="require_invite_code" name="require_invite_code" value="1" <?php checked( '1', $this->database->get_setting( 'require_invite_code', '1' ) ); ?>>
									<?php esc_html_e( 'Require valid invite code for registration', 'login-register-forms-overhaul' ); ?>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rate_limit_enabled">
									<?php esc_html_e( 'Rate Limiting', 'login-register-forms-overhaul' ); ?>
								</label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="rate_limit_enabled" name="rate_limit_enabled" value="1" <?php checked( '1', $this->database->get_setting( 'rate_limit_enabled', '1' ) ); ?>>
									<?php esc_html_e( 'Enable rate limiting for code validation', 'login-register-forms-overhaul' ); ?>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rate_limit_attempts">
									<?php esc_html_e( 'Max Attempts', 'login-register-forms-overhaul' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="rate_limit_attempts" name="rate_limit_attempts" value="<?php echo esc_attr( $this->database->get_setting( 'rate_limit_attempts', '3' ) ); ?>" min="1" max="100" class="small-text">
								<p class="description"><?php esc_html_e( 'Maximum failed attempts before blocking', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rate_limit_window">
									<?php esc_html_e( 'Time Window (minutes)', 'login-register-forms-overhaul' ); ?>
								</label>
							</th>
							<td>
								<input type="number" id="rate_limit_window" name="rate_limit_window" value="<?php echo esc_attr( $this->database->get_setting( 'rate_limit_window', '60' ) ); ?>" min="1" max="1440" class="small-text">
								<p class="description"><?php esc_html_e( 'Time window for rate limiting', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="notify_code_exhausted">
									<?php esc_html_e( 'Email Notifications', 'login-register-forms-overhaul' ); ?>
								</label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="notify_code_exhausted" name="notify_code_exhausted" value="1" <?php checked( '1', $this->database->get_setting( 'notify_code_exhausted', '1' ) ); ?>>
									<?php esc_html_e( 'Send email when codes are exhausted', 'login-register-forms-overhaul' ); ?>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="notification_email">
									<?php esc_html_e( 'Notification Email', 'login-register-forms-overhaul' ); ?>
								</label>
							</th>
							<td>
								<input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr( $this->database->get_setting( 'notification_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text">
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr>

			<h3><?php esc_html_e( 'Usage Statistics', 'login-register-forms-overhaul' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Successful Registrations', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Invalid Attempts', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Exhausted Code Attempts', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Expired Code Attempts', 'login-register-forms-overhaul' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo absint( $stats['success'] ); ?></td>
						<td><?php echo absint( $stats['invalid'] ); ?></td>
						<td><?php echo absint( $stats['exhausted'] ); ?></td>
						<td><?php echo absint( $stats['expired'] ); ?></td>
					</tr>
				</tbody>
			</table>

			<hr>

			<h3><?php esc_html_e( 'Create New Invite Code', 'login-register-forms-overhaul' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lrfo-create-code-form">
				<input type="hidden" name="action" value="lrfo_create_code">
				<?php wp_nonce_field( 'lrfo_create_code', 'lrfo_nonce' ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="code_string"><?php esc_html_e( 'Code', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="text" id="code_string" name="code_string" class="regular-text" required>
								<button type="button" class="button lrfo-generate-code"><?php esc_html_e( 'Generate Random', 'login-register-forms-overhaul' ); ?></button>
								<p class="description"><?php esc_html_e( '6-12 characters, case-insensitive', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="usage_limit"><?php esc_html_e( 'Usage Limit', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="number" id="usage_limit" name="usage_limit" value="1" min="1" max="999" class="small-text">
								<p class="description"><?php esc_html_e( 'Maximum number of times this code can be used', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="expiry_date"><?php esc_html_e( 'Expiry Date', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="datetime-local" id="expiry_date" name="expiry_date">
								<p class="description"><?php esc_html_e( 'Optional: Code will expire after this date', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="user_role"><?php esc_html_e( 'User Role', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<select id="user_role" name="user_role">
									<?php foreach ( $editable_roles as $role_slug => $role_info ) : ?>
										<option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( 'subscriber', $role_slug ); ?>>
											<?php echo esc_html( $role_info['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Role assigned to users registering with this code', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Create Code', 'login-register-forms-overhaul' ) ); ?>
			</form>

			<hr>

			<h3><?php esc_html_e( 'Bulk Generate Codes', 'login-register-forms-overhaul' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lrfo_bulk_generate">
				<?php wp_nonce_field( 'lrfo_bulk_generate', 'lrfo_nonce' ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="bulk_count"><?php esc_html_e( 'Number of Codes', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="number" id="bulk_count" name="bulk_count" value="10" min="1" max="1000" class="small-text">
								<p class="description"><?php esc_html_e( 'Maximum 1000 codes per generation', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="bulk_prefix"><?php esc_html_e( 'Prefix', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="text" id="bulk_prefix" name="bulk_prefix" value="INV" maxlength="10" class="small-text">
								<p class="description"><?php esc_html_e( 'Prefix for generated codes (e.g., INV-ABC123)', 'login-register-forms-overhaul' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="bulk_usage_limit"><?php esc_html_e( 'Usage Limit', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="number" id="bulk_usage_limit" name="bulk_usage_limit" value="1" min="1" max="999" class="small-text">
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="bulk_expiry_date"><?php esc_html_e( 'Expiry Date', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<input type="datetime-local" id="bulk_expiry_date" name="bulk_expiry_date">
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="bulk_user_role"><?php esc_html_e( 'User Role', 'login-register-forms-overhaul' ); ?></label>
							</th>
							<td>
								<select id="bulk_user_role" name="bulk_user_role">
									<?php foreach ( $editable_roles as $role_slug => $role_info ) : ?>
										<option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( 'subscriber', $role_slug ); ?>>
											<?php echo esc_html( $role_info['name'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Generate Codes', 'login-register-forms-overhaul' ) ); ?>
			</form>

			<hr>

			<h3><?php esc_html_e( 'Existing Codes', 'login-register-forms-overhaul' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Uses Remaining', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Total Uses', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Expiry', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Role', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Status', 'login-register-forms-overhaul' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'login-register-forms-overhaul' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $codes ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No codes found.', 'login-register-forms-overhaul' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $codes as $code ) : ?>
							<tr>
								<td><?php echo absint( $code['id'] ); ?></td>
								<td><?php echo absint( $code['uses_remaining'] ); ?></td>
								<td><?php echo absint( $code['total_uses'] ); ?></td>
								<td>
									<?php
									if ( $code['expiry_date'] ) {
										echo esc_html( gmdate( 'Y-m-d H:i', $code['expiry_date'] ) );
									} else {
										esc_html_e( 'Never', 'login-register-forms-overhaul' );
									}
									?>
								</td>
								<td><?php echo esc_html( $code['user_role'] ); ?></td>
								<td>
									<?php if ( '1' === $code['is_active'] ) : ?>
										<span style="color: green;"><?php esc_html_e( 'Active', 'login-register-forms-overhaul' ); ?></span>
									<?php else : ?>
										<span style="color: red;"><?php esc_html_e( 'Inactive', 'login-register-forms-overhaul' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
										<input type="hidden" name="action" value="lrfo_delete_code">
										<input type="hidden" name="code_id" value="<?php echo absint( $code['id'] ); ?>">
										<?php wp_nonce_field( 'lrfo_delete_code', 'lrfo_nonce' ); ?>
										<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this code?', 'login-register-forms-overhaul' ); ?>');">
											<?php esc_html_e( 'Delete', 'login-register-forms-overhaul' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_advanced_tab( $settings ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lrfo_save_settings">
			<?php wp_nonce_field( 'lrfo_save_settings', 'lrfo_nonce' ); ?>

			<h2><?php esc_html_e( 'Role-Based Exceptions', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="enable_role_exceptions">
								<?php esc_html_e( 'Enable Role Exceptions', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="enable_role_exceptions" name="enable_role_exceptions" value="1" <?php checked( '1', $settings['enable_role_exceptions'] ); ?>>
								<?php esc_html_e( 'Allow specific roles to bypass modifications', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="exception_roles">
								<?php esc_html_e( 'Exception Roles', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<input type="text" id="exception_roles" name="exception_roles" value="<?php echo esc_attr( $settings['exception_roles'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Comma-separated role slugs (e.g., administrator,editor)', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'IP Allowlist', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="enable_ip_allowlist">
								<?php esc_html_e( 'Enable IP Allowlist', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="enable_ip_allowlist" name="enable_ip_allowlist" value="1" <?php checked( '1', $settings['enable_ip_allowlist'] ); ?>>
								<?php esc_html_e( 'Allow specific IPs to bypass modifications', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ip_allowlist">
								<?php esc_html_e( 'Allowed IPs', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<textarea id="ip_allowlist" name="ip_allowlist" rows="5" class="large-text"><?php echo esc_textarea( $settings['ip_allowlist'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One IP address per line', 'login-register-forms-overhaul' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Cleanup', 'login-register-forms-overhaul' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="cleanup_on_uninstall">
								<?php esc_html_e( 'Cleanup on Uninstall', 'login-register-forms-overhaul' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="cleanup_on_uninstall" name="cleanup_on_uninstall" value="1" <?php checked( '1', $settings['cleanup_on_uninstall'] ); ?>>
								<?php esc_html_e( 'Remove all plugin data when uninstalling', 'login-register-forms-overhaul' ); ?>
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
		<?php
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-register-forms-overhaul' ) );
		}

		if ( ! isset( $_POST['lrfo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lrfo_nonce'] ) ), 'lrfo_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'login-register-forms-overhaul' ) );
		}

		$fields = array(
			'enable_custom_styles',
			'logo_url',
			'custom_css',
			'custom_js',
			'hide_login_errors',
			'custom_login_error_message',
			'hide_registration_errors',
			'disable_registration',
			'custom_logout_redirect',
			'hide_session_expire_modal',
			'enable_role_exceptions',
			'exception_roles',
			'enable_ip_allowlist',
			'ip_allowlist',
			'require_invite_code',
			'remove_email_requirement',
			'rate_limit_enabled',
			'rate_limit_attempts',
			'rate_limit_window',
			'notification_email',
			'notify_code_exhausted',
			'cleanup_on_uninstall',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = wp_unslash( $_POST[ $field ] );

				switch ( $field ) {
					case 'logo_url':
					case 'custom_logout_redirect':
						$value = esc_url_raw( $value );
						break;
					case 'notification_email':
						$value = sanitize_email( $value );
						break;
					case 'custom_css':
					case 'custom_js':
					case 'ip_allowlist':
						$value = sanitize_textarea_field( $value );
						break;
					case 'rate_limit_attempts':
					case 'rate_limit_window':
						$value = absint( $value );
						break;
					default:
						$value = sanitize_text_field( $value );
				}

				$this->database->save_setting( $field, $value );
			} else {
				$this->database->save_setting( $field, '0' );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'lrfo-settings',
					'tab'              => isset( $_POST['current_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['current_tab'] ) ) : 'visual',
					'settings-updated' => 'true',
				),
				admin_url( 'users.php' )
			)
		);
		exit;
	}

	public function create_code() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-register-forms-overhaul' ) );
		}

		if ( ! isset( $_POST['lrfo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lrfo_nonce'] ) ), 'lrfo_create_code' ) ) {
			wp_die( esc_html__( 'Security check failed', 'login-register-forms-overhaul' ) );
		}

		$code_string = isset( $_POST['code_string'] ) ? sanitize_text_field( wp_unslash( $_POST['code_string'] ) ) : '';
		$usage_limit = isset( $_POST['usage_limit'] ) ? absint( $_POST['usage_limit'] ) : 1;
		$user_role   = isset( $_POST['user_role'] ) ? sanitize_text_field( wp_unslash( $_POST['user_role'] ) ) : 'subscriber';
		$expiry_date = null;

		if ( ! empty( $_POST['expiry_date'] ) ) {
			$expiry_date = strtotime( sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) );
		}

		$this->invite_codes->create_code( $code_string, $usage_limit, $expiry_date, $user_role );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'lrfo-settings',
					'tab'          => 'invite-codes',
					'code-created' => 'true',
				),
				admin_url( 'users.php' )
			)
		);
		exit;
	}

	public function bulk_generate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-register-forms-overhaul' ) );
		}

		if ( ! isset( $_POST['lrfo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lrfo_nonce'] ) ), 'lrfo_bulk_generate' ) ) {
			wp_die( esc_html__( 'Security check failed', 'login-register-forms-overhaul' ) );
		}

		$count       = isset( $_POST['bulk_count'] ) ? absint( $_POST['bulk_count'] ) : 10;
		$prefix      = isset( $_POST['bulk_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_prefix'] ) ) : 'INV';
		$usage_limit = isset( $_POST['bulk_usage_limit'] ) ? absint( $_POST['bulk_usage_limit'] ) : 1;
		$user_role   = isset( $_POST['bulk_user_role'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_user_role'] ) ) : 'subscriber';
		$expiry_date = null;

		if ( ! empty( $_POST['bulk_expiry_date'] ) ) {
			$expiry_date = strtotime( sanitize_text_field( wp_unslash( $_POST['bulk_expiry_date'] ) ) );
		}

		$codes = $this->invite_codes->bulk_generate_codes( $count, $usage_limit, $expiry_date, $user_role, $prefix );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'lrfo-settings',
					'tab'             => 'invite-codes',
					'codes-generated' => 'true',
					'count'           => count( $codes ),
				),
				admin_url( 'users.php' )
			)
		);
		exit;
	}

	public function delete_code() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'login-register-forms-overhaul' ) );
		}

		if ( ! isset( $_POST['lrfo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lrfo_nonce'] ) ), 'lrfo_delete_code' ) ) {
			wp_die( esc_html__( 'Security check failed', 'login-register-forms-overhaul' ) );
		}

		$code_id = isset( $_POST['code_id'] ) ? absint( $_POST['code_id'] ) : 0;

		if ( $code_id > 0 ) {
			$this->invite_codes->delete_code( $code_id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'lrfo-settings',
					'tab'          => 'invite-codes',
					'code-deleted' => 'true',
				),
				admin_url( 'users.php' )
			)
		);
		exit;
	}

	public function delete_exhausted_code( $code_id ) {
		$this->invite_codes->delete_code( $code_id );
	}
}
