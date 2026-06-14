<?php
/**
 * REST API controller.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_REST {
	private const NAMESPACE = 'acfw/v1';

	public function __construct(
		private readonly ACFW_Independent_Analytics $analytics
	) {}

	public function register_routes(): void {
		$routes = array(
			'/site-summary'          => 'site_summary',
			'/top-content'           => 'top_content',
			'/content-performance'   => 'content_performance',
			'/content-opportunities' => 'content_opportunities',
			'/referrers'             => 'referrers',
			'/campaigns'             => 'campaigns',
			'/forms'                 => 'forms',
			'/user-journey'          => 'user_journey',
		);

		foreach ( $routes as $route => $method ) {
			register_rest_route(
				self::NAMESPACE,
				$route,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, $method ),
					'permission_callback' => array( $this, 'permission_callback' ),
				)
			);
		}
	}

	public function permission_callback(): bool {
		return true;
	}

	public function site_summary( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['compare'] = $this->compare_arg( $request );
		return rest_ensure_response( $this->analytics->get_site_summary( $args ) );
	}

	public function top_content( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['post_type'] = sanitize_key( (string) $request->get_param( 'post_type' ) ?: 'any' );
		$args['order_by']  = $this->enum_arg( $request, 'order_by', array( 'views', 'visitors', 'conversions', 'conversion_rate', 'engagement' ), 'views' );

		return rest_ensure_response( $this->analytics->get_top_content( $args ) );
	}

	public function content_performance( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['post_id'] = absint( $request->get_param( 'post_id' ) );
		$args['url']     = esc_url_raw( (string) $request->get_param( 'url' ) );
		$args['compare'] = $this->compare_arg( $request );

		if ( 0 === $args['post_id'] && '' === $args['url'] ) {
			return ACFW_Response_Builder::error( 'missing_content_identifier', __( 'At least one of post_id or url is required.', 'analytics-chat-for-wordpress' ), 400 );
		}

		$result = $this->analytics->get_content_performance( $args );
		if ( is_wp_error( $result ) ) {
			return $this->wp_error_response( $result );
		}

		return rest_ensure_response( $result );
	}

	public function content_opportunities( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['opportunity_type'] = $this->enum_arg(
			$request,
			'opportunity_type',
			array( 'traffic_no_conversion', 'declining', 'rising', 'high_exit', 'stale_but_visited', 'all' ),
			'all'
		);

		return rest_ensure_response( $this->analytics->get_content_opportunities( $args ) );
	}

	public function referrers( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['post_id'] = absint( $request->get_param( 'post_id' ) );

		return rest_ensure_response( $this->analytics->get_referrers( $args ) );
	}

	public function campaigns( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['campaign'] = sanitize_text_field( (string) $request->get_param( 'campaign' ) );
		$args['source']   = sanitize_text_field( (string) $request->get_param( 'source' ) );
		$args['medium']   = sanitize_text_field( (string) $request->get_param( 'medium' ) );

		return rest_ensure_response( $this->analytics->get_campaigns( $args ) );
	}

	public function forms( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['form_id'] = sanitize_text_field( (string) $request->get_param( 'form_id' ) );

		return rest_ensure_response( $this->analytics->get_forms( $args ) );
	}

	public function user_journey( WP_REST_Request $request ): WP_REST_Response {
		$args = $this->common_args( $request, 10 );
		if ( is_wp_error( $args ) ) {
			return $this->wp_error_response( $args );
		}

		$args['post_id'] = absint( $request->get_param( 'post_id' ) );

		return rest_ensure_response( $this->analytics->get_user_journey_summary( $args ) );
	}

	private function common_args( WP_REST_Request $request, int $default_limit = 20 ): array|WP_Error {
		$period = $this->period_args( $request );
		if ( is_wp_error( $period ) ) {
			return $period;
		}

		$max_limit = max( 1, min( 100, absint( get_option( 'acfw_max_result_limit', 100 ) ) ?: 100 ) );
		$limit     = absint( $request->get_param( 'limit' ) ) ?: $default_limit;
		if ( $limit < 1 || $limit > $max_limit ) {
			return new WP_Error( 'invalid_limit', sprintf( 'Limit must be between 1 and %d.', $max_limit ), array( 'status' => 400 ) );
		}

		return array_merge(
			$period,
			array(
				'limit' => $limit,
			)
		);
	}

	private function period_args( WP_REST_Request $request ): array|WP_Error {
		$max_days = max( 1, min( 365, absint( get_option( 'acfw_max_period_days', 365 ) ) ?: 365 ) );
		$start    = sanitize_text_field( (string) $request->get_param( 'start_date' ) );
		$end      = sanitize_text_field( (string) $request->get_param( 'end_date' ) );

		if ( '' !== $start || '' !== $end ) {
			if ( ! $this->valid_date( $start ) || ! $this->valid_date( $end ) ) {
				return new WP_Error( 'invalid_period', __( 'start_date and end_date must use YYYY-MM-DD.', 'analytics-chat-for-wordpress' ), array( 'status' => 400 ) );
			}

			$start_ts = strtotime( $start . ' 00:00:00 UTC' );
			$end_ts   = strtotime( $end . ' 00:00:00 UTC' );
			$days     = (int) floor( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) + 1;
			if ( $days < 1 || $days > $max_days ) {
				return new WP_Error( 'invalid_period', sprintf( 'Period must be between 1 and %d days.', $max_days ), array( 'status' => 400 ) );
			}

			return array( 'start_date' => $start, 'end_date' => $end, 'days' => $days );
		}

		$period = $this->normalize_period( sanitize_text_field( (string) $request->get_param( 'period' ) ?: '30d' ) );
		if ( ! preg_match( '/^(\d+)d$/', $period, $matches ) ) {
			return new WP_Error( 'invalid_period', __( 'Period must use a value like 30d or last_30_days.', 'analytics-chat-for-wordpress' ), array( 'status' => 400 ) );
		}

		$days = absint( $matches[1] );
		if ( $days < 1 || $days > $max_days ) {
			return new WP_Error( 'invalid_period', sprintf( 'Period must be between 1 and %d days.', $max_days ), array( 'status' => 400 ) );
		}

		$end_dt   = current_datetime();
		$start_dt = ( clone $end_dt )->modify( '-' . ( $days - 1 ) . ' days' );

		return array(
			'start_date' => $start_dt->format( 'Y-m-d' ),
			'end_date'   => $end_dt->format( 'Y-m-d' ),
			'days'       => $days,
		);
	}

	private function valid_date( string $date ): bool {
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && false !== strtotime( $date );
	}

	private function normalize_period( string $period ): string {
		if ( preg_match( '/^last_(\d+)_days$/', $period, $matches ) ) {
			return absint( $matches[1] ) . 'd';
		}

		return $period;
	}

	private function compare_arg( WP_REST_Request $request ): string {
		return $this->enum_arg( $request, 'compare', array( 'previous_period', 'none' ), 'previous_period' );
	}

	private function enum_arg( WP_REST_Request $request, string $name, array $allowed, string $default ): string {
		$value = sanitize_key( (string) $request->get_param( $name ) );
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	private function wp_error_response( WP_Error $error ): WP_REST_Response {
		$data = $error->get_error_data();
		return ACFW_Response_Builder::error( $error->get_error_code(), $error->get_error_message(), absint( $data['status'] ?? 500 ) ?: 500 );
	}
}
