<?php
/**
 * Settings page template.
 *
 * @package AnalyticsChatForWordPress
 *
 * @var ACFW_Auth $auth
 * @var ACFW_Independent_Analytics $analytics
 * @var array $diagnostics
 * @var string $bridge_url
 * @var string $bridge_site_id
 * @var string $bridge_status
 * @var string $bridge_connected_at
 * @var array $update_status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Analytics Chat for WordPress', 'analytics-chat-for-wordpress' ); ?></h1>

	<?php if ( '' !== $this->generated_key ) : ?>
		<div class="notice notice-success">
			<p><strong><?php echo esc_html__( 'New API key generated. Copy it now; it will not be shown again.', 'analytics-chat-for-wordpress' ); ?></strong></p>
			<p><code style="font-size: 14px;"><?php echo esc_html( $this->generated_key ); ?></code></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['acfw_key_revoked'] ) ) : ?>
		<div class="notice notice-success"><p><?php echo esc_html__( 'API key revoked.', 'analytics-chat-for-wordpress' ); ?></p></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['acfw_update_check'] ) ) : ?>
		<div class="notice notice-info"><p><?php echo esc_html__( 'GitHub update check completed.', 'analytics-chat-for-wordpress' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! empty( $this->admin_notice['message'] ) ) : ?>
		<div class="notice notice-<?php echo 'error' === $this->admin_notice['type'] ? 'error' : 'success'; ?>">
			<p><?php echo esc_html( (string) $this->admin_notice['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<h2><?php echo esc_html__( 'Status', 'analytics-chat-for-wordpress' ); ?></h2>
	<table class="widefat striped" style="max-width: 900px;">
		<tbody>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Plugin status', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo esc_html__( 'Active', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Independent Analytics', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo $analytics->is_available() ? esc_html__( 'Detected', 'analytics-chat-for-wordpress' ) : esc_html__( 'Not detected', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Pro-like capabilities', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo $analytics->has_pro_features() ? esc_html__( 'Detected', 'analytics-chat-for-wordpress' ) : esc_html__( 'Not detected / unknown', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'API key status', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo $auth->has_key() ? esc_html__( 'Configured', 'analytics-chat-for-wordpress' ) : esc_html__( 'Not configured', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Hosted bridge status', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo 'connected' === $bridge_status ? esc_html__( 'Connected', 'analytics-chat-for-wordpress' ) : esc_html__( 'Not connected', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
		</tbody>
	</table>

	<h2><?php echo esc_html__( 'Hosted Bridge Connection', 'analytics-chat-for-wordpress' ); ?></h2>
	<p><?php echo esc_html__( 'Use this section when connecting the site to the public Analytics Chat bridge. The bridge token is stored securely and is never displayed.', 'analytics-chat-for-wordpress' ); ?></p>

	<table class="widefat striped" style="max-width: 900px; margin-bottom: 16px;">
		<tbody>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Connection status', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo 'connected' === $bridge_status ? esc_html__( 'Connected', 'analytics-chat-for-wordpress' ) : esc_html__( 'Not connected', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Bridge site ID', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo '' !== $bridge_site_id ? '<code>' . esc_html( $bridge_site_id ) . '</code>' : esc_html__( 'Not assigned', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Connected at', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo '' !== $bridge_connected_at ? esc_html( $bridge_connected_at . ' UTC' ) : esc_html__( 'Not connected', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
		</tbody>
	</table>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width: 900px;">
		<input type="hidden" name="action" value="acfw_connect_bridge">
		<?php wp_nonce_field( 'acfw_connect_bridge' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="acfw_bridge_url"><?php echo esc_html__( 'Bridge URL', 'analytics-chat-for-wordpress' ); ?></label></th>
				<td>
					<input name="acfw_bridge_url" id="acfw_bridge_url" type="url" class="regular-text" value="<?php echo esc_attr( $bridge_url ); ?>" placeholder="https://app.analyticschat.example">
					<p class="description"><?php echo esc_html__( 'The hosted bridge base URL. Do not include /api/v1.', 'analytics-chat-for-wordpress' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="acfw_connection_code"><?php echo esc_html__( 'Connection code', 'analytics-chat-for-wordpress' ); ?></label></th>
				<td>
					<input name="acfw_connection_code" id="acfw_connection_code" type="text" class="regular-text" autocomplete="one-time-code">
					<p class="description"><?php echo esc_html__( 'Paste the short code returned by the Analytics Chat GPT when you choose to connect a new site.', 'analytics-chat-for-wordpress' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Connect this site', 'analytics-chat-for-wordpress' ) ); ?>
	</form>

	<?php if ( 'connected' === $bridge_status ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="acfw_disconnect_bridge">
			<?php wp_nonce_field( 'acfw_disconnect_bridge' ); ?>
			<?php submit_button( __( 'Disconnect bridge', 'analytics-chat-for-wordpress' ), 'delete', 'submit', false ); ?>
		</form>
	<?php endif; ?>

	<h2><?php echo esc_html__( 'API Key', 'analytics-chat-for-wordpress' ); ?></h2>
	<p><?php echo esc_html__( 'Use this key as a Bearer token in your GPT Action. The full key is shown only once after generation.', 'analytics-chat-for-wordpress' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-right: 8px;">
		<input type="hidden" name="action" value="acfw_generate_key">
		<?php wp_nonce_field( 'acfw_generate_key' ); ?>
		<?php submit_button( __( 'Generate new API key', 'analytics-chat-for-wordpress' ), 'primary', 'submit', false ); ?>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
		<input type="hidden" name="action" value="acfw_revoke_key">
		<?php wp_nonce_field( 'acfw_revoke_key' ); ?>
		<?php submit_button( __( 'Revoke API key', 'analytics-chat-for-wordpress' ), 'delete', 'submit', false ); ?>
	</form>

	<h2><?php echo esc_html__( 'Updates', 'analytics-chat-for-wordpress' ); ?></h2>
	<table class="widefat striped" style="max-width: 900px; margin-bottom: 16px;">
		<tbody>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Installed version', 'analytics-chat-for-wordpress' ); ?></th>
				<td><code><?php echo esc_html( ACFW_VERSION ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'GitHub release status', 'analytics-chat-for-wordpress' ); ?></th>
				<td>
					<?php echo esc_html( (string) ( $update_status['message'] ?? __( 'Unknown.', 'analytics-chat-for-wordpress' ) ) ); ?>
					<?php if ( ! empty( $update_status['url'] ) ) : ?>
						<a href="<?php echo esc_url( (string) $update_status['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'View release', 'analytics-chat-for-wordpress' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="acfw_check_updates">
		<?php wp_nonce_field( 'acfw_check_updates' ); ?>
		<?php submit_button( __( 'Check GitHub for updates', 'analytics-chat-for-wordpress' ), 'secondary', 'submit', false ); ?>
	</form>

	<h2><?php echo esc_html__( 'Access Limits', 'analytics-chat-for-wordpress' ); ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'acfw_settings' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="acfw_max_period_days"><?php echo esc_html__( 'Maximum period', 'analytics-chat-for-wordpress' ); ?></label></th>
				<td>
					<input name="acfw_max_period_days" id="acfw_max_period_days" type="number" min="1" max="365" value="<?php echo esc_attr( get_option( 'acfw_max_period_days', 365 ) ); ?>">
					<p class="description"><?php echo esc_html__( 'Maximum number of days a request may cover. Hard-capped at 365.', 'analytics-chat-for-wordpress' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="acfw_max_result_limit"><?php echo esc_html__( 'Maximum result limit', 'analytics-chat-for-wordpress' ); ?></label></th>
				<td>
					<input name="acfw_max_result_limit" id="acfw_max_result_limit" type="number" min="1" max="100" value="<?php echo esc_attr( get_option( 'acfw_max_result_limit', 100 ) ); ?>">
					<p class="description"><?php echo esc_html__( 'Maximum rows returned by list endpoints. Hard-capped at 100.', 'analytics-chat-for-wordpress' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>

	<h2><?php echo esc_html__( 'Allowed Origins / Notes', 'analytics-chat-for-wordpress' ); ?></h2>
	<p><?php echo esc_html__( 'For the MVP, access is controlled by the API key. Configure the GPT Action to send Authorization: Bearer {api_key}.', 'analytics-chat-for-wordpress' ); ?></p>

	<h2><?php echo esc_html__( 'Diagnostics', 'analytics-chat-for-wordpress' ); ?></h2>
	<table class="widefat striped" style="max-width: 900px;">
		<tbody>
			<?php foreach ( $diagnostics as $key => $value ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></th>
					<td>
						<?php if ( is_array( $value ) ) : ?>
							<code><?php echo esc_html( wp_json_encode( $value ) ); ?></code>
						<?php else : ?>
							<?php echo esc_html( (string) $value ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
