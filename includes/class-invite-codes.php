<?php
/**
 * Invite codes management for Login & Register Forms Overhaul
 *
 * @package LoginRegisterFormsOverhaul
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LRFO_Invite_Codes {

	private $database;
	private $codes_table;
	private $usage_table;

	public function __construct( LRFO_Database $database ) {
		global $wpdb;
		$this->database     = $database;
		$this->codes_table  = $wpdb->prefix . 'lrfo_invite_codes';
		$this->usage_table  = $wpdb->prefix . 'lrfo_code_usage';
	}

	public function create_code( $code_string, $usage_limit = 1, $expiry_date = null, $user_role = 'subscriber' ) {
		global $wpdb;

		$code_hash = $this->hash_code( $code_string );

		$result = $wpdb->insert(
			$this->codes_table,
			array(
				'code_string'    => $code_hash,
				'usage_limit'    => absint( $usage_limit ),
				'uses_remaining' => absint( $usage_limit ),
				'total_uses'     => 0,
				'expiry_date'    => $expiry_date ? absint( $expiry_date ) : null,
				'user_role'      => sanitize_text_field( $user_role ),
				'created_at'     => current_time( 'mysql' ),
				'is_active'      => 1,
			),
			array( '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%d' )
		);

		if ( false === $result ) {
			return false;
		}

		wp_cache_delete( 'lrfo_all_codes', 'lrfo_codes' );
		return $wpdb->insert_id;
	}

	public function bulk_generate_codes( $count, $usage_limit = 1, $expiry_date = null, $user_role = 'subscriber', $prefix = 'INV' ) {
		$generated = array();
		$count     = min( absint( $count ), 1000 );

		for ( $i = 0; $i < $count; $i++ ) {
			$code_string = $prefix . '-' . strtoupper( wp_generate_password( 8, false ) );
			$id          = $this->create_code( $code_string, $usage_limit, $expiry_date, $user_role );

			if ( $id ) {
				$generated[] = array(
					'id'   => $id,
					'code' => $code_string,
				);
			}
		}

		return $generated;
	}

	public function validate_code( $code_string, $ip_address = '' ) {
		global $wpdb;

		if ( empty( $code_string ) ) {
			return new WP_Error( 'empty_code', __( 'Please enter an invite code.', 'login-register-forms-overhaul' ) );
		}

		if ( $this->check_rate_limit( $ip_address ) ) {
			return new WP_Error( 'rate_limited', __( 'Too many attempts. Please try again later.', 'login-register-forms-overhaul' ) );
		}

		$code_hash = $this->hash_code( $code_string );

		$code = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE code_string = %s AND is_active = 1',
				$this->codes_table,
				$code_hash
			),
			ARRAY_A
		);

		if ( ! $code ) {
			$this->log_attempt( null, $ip_address, 'invalid' );
			return new WP_Error( 'invalid_code', __( 'Invalid invite code.', 'login-register-forms-overhaul' ) );
		}

		if ( $code['uses_remaining'] <= 0 ) {
			$this->log_attempt( $code['id'], $ip_address, 'exhausted' );
			return new WP_Error( 'code_exhausted', __( 'This invite code has been used up.', 'login-register-forms-overhaul' ) );
		}

		if ( $code['expiry_date'] && time() > $code['expiry_date'] ) {
			$this->log_attempt( $code['id'], $ip_address, 'expired' );
			return new WP_Error( 'code_expired', __( 'This invite code has expired.', 'login-register-forms-overhaul' ) );
		}

		return $code;
	}

	public function use_code( $code_id, $user_id, $ip_address = '' ) {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );

		$code = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d AND is_active = 1 FOR UPDATE',
				$this->codes_table,
				$code_id
			),
			ARRAY_A
		);

		if ( ! $code || $code['uses_remaining'] <= 0 ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$new_remaining = $code['uses_remaining'] - 1;
		$new_total     = $code['total_uses'] + 1;

		$update_result = $wpdb->update(
			$this->codes_table,
			array(
				'uses_remaining' => $new_remaining,
				'total_uses'     => $new_total,
				'is_active'      => ( $new_remaining > 0 ) ? 1 : 0,
			),
			array( 'id' => $code_id ),
			array( '%d', '%d', '%d' ),
			array( '%d' )
		);

		if ( false === $update_result ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$this->log_attempt( $code_id, $ip_address, 'success', $user_id );

		$wpdb->query( 'COMMIT' );

		if ( 0 === $new_remaining ) {
			$this->schedule_code_deletion( $code_id );
			$this->maybe_send_exhausted_notification( $code );
		}

		wp_cache_delete( 'lrfo_all_codes', 'lrfo_codes' );

		return true;
	}

	public function get_all_codes( $include_inactive = false ) {
		global $wpdb;

		$cache_key = $include_inactive ? 'lrfo_all_codes_with_inactive' : 'lrfo_all_codes';
		$cached    = wp_cache_get( $cache_key, 'lrfo_codes' );

		if ( false !== $cached ) {
			return $cached;
		}

		$where = $include_inactive ? '' : 'WHERE is_active = 1';

		$codes = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ' . $where . ' ORDER BY created_at DESC',
				$this->codes_table
			),
			ARRAY_A
		);

		wp_cache_set( $cache_key, $codes, 'lrfo_codes', 3600 );

		return $codes ? $codes : array();
	}

	public function get_code_by_id( $code_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$this->codes_table,
				absint( $code_id )
			),
			ARRAY_A
		);
	}

	public function update_code( $code_id, $data ) {
		global $wpdb;

		$allowed_fields = array( 'usage_limit', 'uses_remaining', 'expiry_date', 'user_role', 'is_active' );
		$update_data    = array();
		$formats        = array();

		foreach ( $data as $field => $value ) {
			if ( in_array( $field, $allowed_fields, true ) ) {
				if ( 'user_role' === $field ) {
					$update_data[ $field ] = sanitize_text_field( $value );
					$formats[]             = '%s';
				} elseif ( 'expiry_date' === $field ) {
					$update_data[ $field ] = $value ? absint( $value ) : null;
					$formats[]             = '%d';
				} else {
					$update_data[ $field ] = absint( $value );
					$formats[]             = '%d';
				}
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->codes_table,
			$update_data,
			array( 'id' => absint( $code_id ) ),
			$formats,
			array( '%d' )
		);

		if ( false !== $result ) {
			wp_cache_delete( 'lrfo_all_codes', 'lrfo_codes' );
			wp_cache_delete( 'lrfo_all_codes_with_inactive', 'lrfo_codes' );
		}

		return false !== $result;
	}

	public function delete_code( $code_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->codes_table,
			array( 'id' => absint( $code_id ) ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$wpdb->delete(
				$this->usage_table,
				array( 'code_id' => absint( $code_id ) ),
				array( '%d' )
			);

			wp_cache_delete( 'lrfo_all_codes', 'lrfo_codes' );
			wp_cache_delete( 'lrfo_all_codes_with_inactive', 'lrfo_codes' );
		}

		return false !== $result;
	}

	private function log_attempt( $code_id, $ip_address, $attempt_type, $user_id = null ) {
		global $wpdb;

		$wpdb->insert(
			$this->usage_table,
			array(
				'code_id'      => $code_id ? absint( $code_id ) : null,
				'ip_address'   => sanitize_text_field( $ip_address ),
				'attempt_type' => sanitize_text_field( $attempt_type ),
				'user_id'      => $user_id ? absint( $user_id ) : null,
				'attempted_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
	}

	private function check_rate_limit( $ip_address ) {
		global $wpdb;

		if ( empty( $ip_address ) ) {
			return false;
		}

		$enabled  = $this->database->get_setting( 'rate_limit_enabled', '1' );
		$attempts = absint( $this->database->get_setting( 'rate_limit_attempts', '3' ) );
		$window   = absint( $this->database->get_setting( 'rate_limit_window', '60' ) );

		if ( '1' !== $enabled ) {
			return false;
		}

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $window * 60 ) );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE ip_address = %s AND attempted_at > %s AND attempt_type IN ("invalid", "exhausted", "expired")',
				$this->usage_table,
				$ip_address,
				$since
			)
		);

		return absint( $count ) >= $attempts;
	}

	private function schedule_code_deletion( $code_id ) {
		wp_schedule_single_event(
			time() + ( 24 * HOUR_IN_SECONDS ),
			'lrfo_delete_exhausted_code',
			array( $code_id )
		);
	}

	private function maybe_send_exhausted_notification( $code ) {
		$notify_enabled = $this->database->get_setting( 'notify_code_exhausted', '1' );

		if ( '1' !== $notify_enabled ) {
			return;
		}

		$to      = $this->database->get_setting( 'notification_email', get_option( 'admin_email' ) );
		$subject = sprintf(
			__( '[%s] Invite Code Exhausted', 'login-register-forms-overhaul' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			__( 'An invite code has been fully used and will be deleted in 24 hours.

Code Details:
- Total Uses: %d
- Role: %s
- Created: %s

This is an automated notification from Login & Register Forms Overhaul plugin.', 'login-register-forms-overhaul' ),
			$code['total_uses'],
			$code['user_role'],
			$code['created_at']
		);

		wp_mail( $to, $subject, $message );
	}

	private function hash_code( $code_string ) {
		return hash( 'sha256', strtoupper( trim( $code_string ) ) );
	}

	public function get_usage_stats( $code_id = null ) {
		global $wpdb;

		$where = '';
		$params = array( $this->usage_table );

		if ( $code_id ) {
			$where = ' WHERE code_id = %d';
			$params[] = absint( $code_id );
		}

		$query = $wpdb->prepare(
			'SELECT 
				attempt_type,
				COUNT(*) as count
			FROM %i' . $where . '
			GROUP BY attempt_type',
			...$params
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		$stats = array(
			'success'   => 0,
			'invalid'   => 0,
			'exhausted' => 0,
			'expired'   => 0,
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				$stats[ $row['attempt_type'] ] = absint( $row['count'] );
			}
		}

		return $stats;
	}
}
