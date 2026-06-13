<?php
/**
 * Admin settings.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Settings {
	private const OPTION_BRIDGE_URL = 'acfw_bridge_url';
	private const OPTION_BRIDGE_SITE_ID = 'acfw_bridge_site_id';
	private const OPTION_BRIDGE_STATUS = 'acfw_bridge_status';
	private const OPTION_BRIDGE_CONNECTED_AT = 'acfw_bridge_connected_at';

	private ?string $generated_key = null;
	private array $admin_notice = array();

	public function __construct(
		private readonly ACFW_Auth $auth,
		private readonly ACFW_Independent_Analytics $analytics
	) {}

	public function register_admin_page(): void {
		add_options_page(
			__( 'Analytics Chat', 'analytics-chat-for-wordpress' ),
			__( 'Analytics Chat', 'analytics-chat-for-wordpress' ),
			'manage_options',
			'analytics-chat-for-wordpress',
			array( $this, 'render' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'acfw_settings',
			'acfw_max_period_days',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_max_period' ),
				'default'           => 365,
			)
		);

		register_setting(
			'acfw_settings',
			'acfw_max_result_limit',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_max_limit' ),
				'default'           => 100,
			)
		);

		register_setting(
			'acfw_settings',
			self::OPTION_BRIDGE_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_bridge_url' ),
				'default'           => '',
			)
		);

		add_action( 'admin_post_acfw_generate_key', array( $this, 'handle_generate_key' ) );
		add_action( 'admin_post_acfw_revoke_key', array( $this, 'handle_revoke_key' ) );
		add_action( 'admin_post_acfw_connect_bridge', array( $this, 'handle_connect_bridge' ) );
		add_action( 'admin_post_acfw_disconnect_bridge', array( $this, 'handle_disconnect_bridge' ) );
	}

	public function sanitize_max_period( mixed $value ): int {
		return max( 1, min( 365, absint( $value ) ?: 365 ) );
	}

	public function sanitize_max_limit( mixed $value ): int {
		return max( 1, min( 100, absint( $value ) ?: 100 ) );
	}

	public function sanitize_bridge_url( mixed $value ): string {
		$url = esc_url_raw( untrailingslashit( (string) $value ) );
		return preg_match( '#^https?://#i', $url ) ? $url : '';
	}

	public function handle_generate_key(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Analytics Chat settings.', 'analytics-chat-for-wordpress' ) );
		}

		check_admin_referer( 'acfw_generate_key' );
		$key = $this->auth->generate_key();
		set_transient( 'acfw_generated_key_' . get_current_user_id(), $key, 5 * MINUTE_IN_SECONDS );

		wp_safe_redirect( add_query_arg( 'acfw_key_generated', '1', menu_page_url( 'analytics-chat-for-wordpress', false ) ) );
		exit;
	}

	public function handle_revoke_key(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Analytics Chat settings.', 'analytics-chat-for-wordpress' ) );
		}

		check_admin_referer( 'acfw_revoke_key' );
		$this->auth->revoke_key();

		wp_safe_redirect( add_query_arg( 'acfw_key_revoked', '1', menu_page_url( 'analytics-chat-for-wordpress', false ) ) );
		exit;
	}

	public function handle_connect_bridge(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Analytics Chat settings.', 'analytics-chat-for-wordpress' ) );
		}

		check_admin_referer( 'acfw_connect_bridge' );

		$bridge_url      = $this->sanitize_bridge_url( wp_unslash( $_POST['acfw_bridge_url'] ?? '' ) );
		$connection_code = sanitize_text_field( wp_unslash( $_POST['acfw_connection_code'] ?? '' ) );

		if ( '' === $bridge_url || '' === $connection_code ) {
			$this->set_notice( 'error', __( 'Bridge URL and connection code are required.', 'analytics-chat-for-wordpress' ) );
			$this->redirect_to_settings();
		}

		update_option( self::OPTION_BRIDGE_URL, $bridge_url, false );

		$response = wp_remote_post(
			$bridge_url . '/api/v1/internal/connections/complete',
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'connection_code'               => $connection_code,
						'site_name'                     => get_bloginfo( 'name' ),
						'site_url'                      => home_url(),
						'wordpress_version'             => get_bloginfo( 'version' ),
						'php_version'                   => PHP_VERSION,
						'plugin_version'                => ACFW_VERSION,
						'independent_analytics_active'  => $this->analytics->is_available(),
						'independent_analytics_version' => $this->analytics->get_version(),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->set_notice( 'error', $response->get_error_message() );
			$this->redirect_to_settings();
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $body ) || empty( $body['site_id'] ) || empty( $body['bridge_token'] ) ) {
			$message = is_array( $body ) && isset( $body['error']['message'] )
				? sanitize_text_field( (string) $body['error']['message'] )
				: __( 'Bridge connection failed.', 'analytics-chat-for-wordpress' );
			$this->set_notice( 'error', $message );
			$this->redirect_to_settings();
		}

		$this->auth->set_bridge_token( (string) $body['bridge_token'] );
		update_option( self::OPTION_BRIDGE_SITE_ID, sanitize_text_field( (string) $body['site_id'] ), false );
		update_option( self::OPTION_BRIDGE_STATUS, 'connected', false );
		update_option( self::OPTION_BRIDGE_CONNECTED_AT, current_time( 'mysql', true ), false );

		$this->set_notice( 'success', __( 'Site connected to the Analytics Chat bridge.', 'analytics-chat-for-wordpress' ) );
		$this->redirect_to_settings();
	}

	public function handle_disconnect_bridge(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Analytics Chat settings.', 'analytics-chat-for-wordpress' ) );
		}

		check_admin_referer( 'acfw_disconnect_bridge' );

		$this->auth->revoke_bridge_token();
		delete_option( self::OPTION_BRIDGE_SITE_ID );
		delete_option( self::OPTION_BRIDGE_STATUS );
		delete_option( self::OPTION_BRIDGE_CONNECTED_AT );

		$this->set_notice( 'success', __( 'Site disconnected from the Analytics Chat bridge.', 'analytics-chat-for-wordpress' ) );
		$this->redirect_to_settings();
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$transient_key       = 'acfw_generated_key_' . get_current_user_id();
		$this->generated_key = (string) get_transient( $transient_key );
		if ( '' !== $this->generated_key ) {
			delete_transient( $transient_key );
		}

		$notice_key         = 'acfw_admin_notice_' . get_current_user_id();
		$this->admin_notice = (array) get_transient( $notice_key );
		delete_transient( $notice_key );

		$auth                = $this->auth;
		$analytics           = $this->analytics;
		$diagnostics         = $this->analytics->diagnostics();
		$bridge_url          = (string) get_option( self::OPTION_BRIDGE_URL, '' );
		$bridge_site_id      = (string) get_option( self::OPTION_BRIDGE_SITE_ID, '' );
		$bridge_status       = (string) get_option( self::OPTION_BRIDGE_STATUS, '' );
		$bridge_connected_at = (string) get_option( self::OPTION_BRIDGE_CONNECTED_AT, '' );

		require ACFW_PLUGIN_DIR . 'admin/settings-page.php';
	}

	private function set_notice( string $type, string $message ): void {
		set_transient(
			'acfw_admin_notice_' . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			5 * MINUTE_IN_SECONDS
		);
	}

	private function redirect_to_settings(): void {
		wp_safe_redirect( menu_page_url( 'analytics-chat-for-wordpress', false ) );
		exit;
	}
}
