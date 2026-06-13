<?php
/**
 * Metric defaults and calculations.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Metrics_Normalizer {
	public static function metrics( array $row ): array {
		$views       = absint( $row['views'] ?? 0 );
		$visitors    = absint( $row['visitors'] ?? 0 );
		$sessions    = absint( $row['sessions'] ?? 0 );
		$conversions = absint( $row['conversions'] ?? $row['conversion_count'] ?? 0 );

		return array(
			'views'                    => $views,
			'visitors'                 => $visitors,
			'sessions'                 => $sessions,
			'bounce_rate'              => self::rate( $row['bounce_rate'] ?? null ),
			'average_session_duration' => absint( $row['average_session_duration'] ?? 0 ),
			'conversions'              => $conversions,
			'conversion_count'         => $conversions,
			'conversion_rate'          => self::rate( $row['conversion_rate'] ?? ( $visitors > 0 ? $conversions / $visitors : 0 ) ),
		);
	}

	public static function site_metrics( array $row ): array {
		$metrics = self::metrics( $row );
		unset( $metrics['conversions'] );
		return $metrics;
	}

	public static function percent_change( float|int $current, float|int $previous ): ?float {
		if ( 0.0 === (float) $previous ) {
			return 0.0 === (float) $current ? 0.0 : null;
		}

		return round( ( ( (float) $current - (float) $previous ) / (float) $previous ) * 100, 1 );
	}

	private static function rate( mixed $value ): float {
		if ( null === $value || '' === $value ) {
			return 0.0;
		}

		$rate = (float) $value;
		if ( $rate > 1 ) {
			$rate = $rate / 100;
		}

		return round( max( 0, min( 1, $rate ) ), 4 );
	}
}
