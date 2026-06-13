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
	private ?string $generated_key = null;

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

		add_action( 'admin_post_acfw_generate_key', array( $this, 'handle_generate_key' ) );
		add_action( 'admin_post_acfw_revoke_key', array( $this, 'handle_revoke_key' ) );
	}

	public function sanitize_max_period( mixed $value ): int {
		return max( 1, min( 365, absint( $value ) ?: 365 ) );
	}

	public function sanitize_max_limit( mixed $value ): int {
		return max( 1, min( 100, absint( $value ) ?: 100 ) );
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

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$transient_key       = 'acfw_generated_key_' . get_current_user_id();
		$this->generated_key = (string) get_transient( $transient_key );
		if ( '' !== $this->generated_key ) {
			delete_transient( $transient_key );
		}

		$auth        = $this->auth;
		$analytics   = $this->analytics;
		$diagnostics = $this->analytics->diagnostics();

		require ACFW_PLUGIN_DIR . 'admin/settings-page.php';
	}
}
