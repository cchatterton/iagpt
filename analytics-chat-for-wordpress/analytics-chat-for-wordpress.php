<?php
/**
 * Plugin Name: Analytics Chat for WordPress
 * Plugin URI: https://github.com/cchatterton/iagpt
 * Description: Read-only GPT bridge for WordPress content analytics using Independent Analytics data.
 * Version: 0.1.8
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Techn
 * Author URI: https://techn.com.au
 * Text Domain: analytics-chat-for-wordpress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ACFW_VERSION', '0.1.8' );
define( 'ACFW_PLUGIN_FILE', __FILE__ );
define( 'ACFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-auth.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-metrics-normalizer.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-response-builder.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-independent-analytics.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-updater.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-rest.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-settings.php';
require_once ACFW_PLUGIN_DIR . 'includes/class-acfw-plugin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		ACFW_Plugin::instance()->init();
	}
);
