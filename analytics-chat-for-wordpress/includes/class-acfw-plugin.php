<?php
/**
 * Plugin bootstrap.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Plugin {
	private static ?ACFW_Plugin $instance = null;

	private ACFW_Auth $auth;
	private ACFW_Independent_Analytics $analytics;
	private ACFW_Updater $updater;
	private ACFW_REST $rest;
	private ACFW_Settings $settings;

	private function __construct() {
		$this->auth      = new ACFW_Auth();
		$this->analytics = new ACFW_Independent_Analytics();
		$this->updater   = new ACFW_Updater();
		$this->rest      = new ACFW_REST( $this->analytics );
		$this->settings  = new ACFW_Settings( $this->auth, $this->analytics );
	}

	public static function instance(): ACFW_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(): void {
		$this->updater->init();
		add_action( 'rest_api_init', array( $this->rest, 'register_routes' ) );
		add_action( 'admin_menu', array( $this->settings, 'register_admin_page' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
	}
}
