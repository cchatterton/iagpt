<?php
/**
 * Settings page template.
 *
 * @package AnalyticsChatForWordPress
 *
 * @var ACFW_Independent_Analytics $analytics
 * @var array $diagnostics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Analytics Chat for WordPress', 'analytics-chat-for-wordpress' ); ?></h1>

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
				<th scope="row"><?php echo esc_html__( 'Public API', 'analytics-chat-for-wordpress' ); ?></th>
				<td><?php echo esc_html__( 'Enabled', 'analytics-chat-for-wordpress' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'REST base URL', 'analytics-chat-for-wordpress' ); ?></th>
				<td><code><?php echo esc_html( rest_url( 'acfw/v1' ) ); ?></code></td>
			</tr>
		</tbody>
	</table>

	<h2><?php echo esc_html__( 'Public Analytics API', 'analytics-chat-for-wordpress' ); ?></h2>
	<p><?php echo esc_html__( 'The REST endpoints are public and read-only. They return aggregated performance data only; no visitor identifiers, WordPress users, IP addresses, or fingerprints are exposed.', 'analytics-chat-for-wordpress' ); ?></p>

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
	<p><?php echo esc_html__( 'No API key is required. Configure the GPT Action with no authentication and point the server URL at this site REST base URL.', 'analytics-chat-for-wordpress' ); ?></p>

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
