<?php
/**
 * REST response helpers.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Response_Builder {
	public static function error( string $code, string $message, int $status ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'error' => array(
					'code'    => $code,
					'message' => $message,
				),
			),
			$status
		);
	}

	public static function unavailable( string $message ): WP_REST_Response {
		return self::error( 'independent_analytics_unavailable', $message, 503 );
	}
}
