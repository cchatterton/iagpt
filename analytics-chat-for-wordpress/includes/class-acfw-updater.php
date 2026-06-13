<?php
/**
 * GitHub release updater.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Updater {
	private const OWNER = 'cchatterton';
	private const REPO = 'iagpt';
	private const SLUG = 'analytics-chat-for-wordpress';
	private const ASSET_NAME = 'analytics-chat-for-wordpress.zip';
	private const RELEASE_TRANSIENT = 'acfw_github_latest_release';
	private const PLUGINS_SCREEN_CHECK_TRANSIENT = 'acfw_plugins_screen_update_check';

	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'filter_plugin_info' ), 10, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( ACFW_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'maybe_check_on_plugins_screen' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_post_acfw_check_updates', array( $this, 'handle_manual_check' ) );
	}

	public function filter_update_plugins( mixed $transient ): mixed {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$update = $this->get_update();
		if ( null === $update ) {
			return $transient;
		}

		$plugin_file = plugin_basename( ACFW_PLUGIN_FILE );
		$item        = (object) array(
			'id'            => $this->github_url(),
			'slug'          => self::SLUG,
			'plugin'        => $plugin_file,
			'new_version'   => $update['version'],
			'url'           => $update['html_url'],
			'package'       => $update['package'],
			'requires'      => '6.0',
			'requires_php'  => '8.1',
		);

		$transient->response[ $plugin_file ] = $item;

		return $transient;
	}

	public function filter_plugin_info( mixed $result, string $action, object $args ): mixed {
		if ( 'plugin_information' !== $action || self::SLUG !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		$release = $this->latest_release();
		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Analytics Chat for WordPress',
			'slug'          => self::SLUG,
			'version'       => $this->release_version( $release ),
			'author'        => 'Techn',
			'homepage'      => $this->github_url(),
			'download_link' => $this->release_package_url( $release ),
			'requires'      => '6.0',
			'requires_php'  => '8.1',
			'sections'      => array(
				'description' => 'Read-only GPT bridge for WordPress content analytics using Independent Analytics data.',
				'changelog'   => $this->release_body( $release ),
			),
		);
	}

	public function plugin_action_links( array $links ): array {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return $links;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'acfw_check_updates',
					'redirect' => 'plugins',
				),
				admin_url( 'admin-post.php' )
			),
			'acfw_check_updates'
		);

		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check GitHub updates', 'analytics-chat-for-wordpress' ) . '</a>';

		return $links;
	}

	public function plugin_row_meta( array $links, string $file ): array {
		if ( plugin_basename( ACFW_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		$links[] = '<a href="' . esc_url( $this->github_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub', 'analytics-chat-for-wordpress' ) . '</a>';

		return $links;
	}

	public function maybe_check_on_plugins_screen(): void {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( get_site_transient( self::PLUGINS_SCREEN_CHECK_TRANSIENT ) ) {
			return;
		}

		$this->refresh_wordpress_updates();
		set_site_transient( self::PLUGINS_SCREEN_CHECK_TRANSIENT, 1, 15 * MINUTE_IN_SECONDS );
	}

	public function admin_notices(): void {
		if ( ! is_admin() || ! current_user_can( 'update_plugins' ) || ! isset( $_GET['acfw_update_check'] ) ) {
			return;
		}

		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Analytics Chat checked GitHub for plugin updates.', 'analytics-chat-for-wordpress' ) . '</p></div>';
	}

	public function handle_manual_check(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to check for plugin updates.', 'analytics-chat-for-wordpress' ) );
		}

		check_admin_referer( 'acfw_check_updates' );

		$this->refresh_wordpress_updates();

		$redirect = sanitize_key( (string) ( $_GET['redirect'] ?? '' ) );
		$target   = 'plugins' === $redirect
			? admin_url( 'plugins.php' )
			: admin_url( 'options-general.php?page=analytics-chat-for-wordpress' );

		wp_safe_redirect( add_query_arg( 'acfw_update_check', '1', $target ) );
		exit;
	}

	public function update_status(): array {
		$release = $this->latest_release();
		if ( null === $release ) {
			return array(
				'available' => false,
				'message'   => __( 'Could not read the latest GitHub release.', 'analytics-chat-for-wordpress' ),
			);
		}

		$version = $this->release_version( $release );
		$package = $this->release_package_url( $release );
		if ( '' === $package ) {
			return array(
				'available' => false,
				'version'   => $version,
				'message'   => __( 'Latest GitHub release found, but it does not include analytics-chat-for-wordpress.zip.', 'analytics-chat-for-wordpress' ),
			);
		}

		$is_newer = version_compare( $version, ACFW_VERSION, '>' );

		return array(
			'available' => $is_newer,
			'version'   => $version,
			'url'       => (string) ( $release['html_url'] ?? $this->github_url() ),
			'message'   => $is_newer
				? sprintf( __( 'Version %s is available from GitHub.', 'analytics-chat-for-wordpress' ), $version )
				: __( 'This plugin is up to date with the latest GitHub release.', 'analytics-chat-for-wordpress' ),
		);
	}

	private function get_update(): ?array {
		$release = $this->latest_release();
		if ( null === $release ) {
			return null;
		}

		$version = $this->release_version( $release );
		$package = $this->release_package_url( $release );
		if ( '' === $version || '' === $package || ! version_compare( $version, ACFW_VERSION, '>' ) ) {
			return null;
		}

		return array(
			'version'  => $version,
			'package'  => $package,
			'html_url' => (string) ( $release['html_url'] ?? $this->github_url() ),
		);
	}

	private function latest_release(): ?array {
		$cached = get_site_transient( self::RELEASE_TRANSIENT );
		if ( is_array( $cached ) ) {
			if ( ! empty( $cached['__acfw_error'] ) ) {
				return null;
			}

			return $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Analytics-Chat-for-WordPress/' . ACFW_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->cache_failed_lookup();
			return null;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
			$this->cache_failed_lookup();
			return null;
		}

		set_site_transient( self::RELEASE_TRANSIENT, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	private function release_version( array $release ): string {
		return ltrim( sanitize_text_field( (string) ( $release['tag_name'] ?? '' ) ), 'vV' );
	}

	private function release_package_url( array $release ): string {
		$assets = is_array( $release['assets'] ?? null ) ? $release['assets'] : array();

		foreach ( $assets as $asset ) {
			if ( self::ASSET_NAME === ( $asset['name'] ?? '' ) && ! empty( $asset['browser_download_url'] ) ) {
				return esc_url_raw( (string) $asset['browser_download_url'] );
			}
		}

		return '';
	}

	private function release_body( array $release ): string {
		$body = trim( wp_kses_post( (string) ( $release['body'] ?? '' ) ) );
		return '' !== $body ? $body : __( 'No changelog was provided for this release.', 'analytics-chat-for-wordpress' );
	}

	private function clear_cache(): void {
		delete_site_transient( self::RELEASE_TRANSIENT );
		delete_site_transient( self::PLUGINS_SCREEN_CHECK_TRANSIENT );
	}

	private function refresh_wordpress_updates(): void {
		$this->clear_cache();
		delete_site_transient( 'update_plugins' );

		if ( ! function_exists( 'wp_update_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		wp_update_plugins();
	}

	private function cache_failed_lookup(): void {
		set_site_transient( self::RELEASE_TRANSIENT, array( '__acfw_error' => true ), 30 * MINUTE_IN_SECONDS );
	}

	private function github_url(): string {
		return 'https://github.com/' . self::OWNER . '/' . self::REPO;
	}
}
