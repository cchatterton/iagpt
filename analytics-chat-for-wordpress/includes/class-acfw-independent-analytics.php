<?php
/**
 * Independent Analytics adapter.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Independent_Analytics {
	private array $table_cache = array();
	private array $columns_cache = array();

	public function is_available(): bool {
		return $this->plugin_active() || null !== $this->views_table();
	}

	public function get_version(): ?string {
		if ( defined( 'IAWP_VERSION' ) ) {
			return (string) IAWP_VERSION;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $file => $data ) {
			if ( str_contains( $file, 'independent-analytics' ) ) {
				return $data['Version'] ?? null;
			}
		}

		return null;
	}

	public function has_pro_features(): bool {
		return class_exists( 'IAWP_Pro' )
			|| defined( 'IAWP_PRO_VERSION' )
			|| null !== $this->find_table( array( 'campaign', 'form', 'conversion', 'journey' ) );
	}

	public function diagnostics(): array {
		return array(
			'wp_version'                   => get_bloginfo( 'version' ),
			'php_version'                  => PHP_VERSION,
			'independent_analytics_active' => $this->plugin_active(),
			'independent_analytics_version'=> $this->get_version(),
			'detected_ia_tables'           => $this->all_ia_tables(),
			'detected_ia_classes'          => $this->detected_classes(),
			'pro_like_features_detected'   => $this->has_pro_features() ? 'yes' : 'unknown',
			'rest_endpoint_health'         => rest_url( 'acfw/v1/site-summary' ),
		);
	}

	public function get_site_summary( array $args ): array|WP_REST_Response {
		if ( ! $this->is_available() ) {
			return ACFW_Response_Builder::unavailable( __( 'Independent Analytics is not active or could not be detected.', 'analytics-chat-for-wordpress' ) );
		}

		$current  = $this->aggregate_period( $args );
		$previous = 'none' === ( $args['compare'] ?? 'previous_period' ) ? array() : $this->aggregate_period( $this->previous_period_args( $args ) );
		$metrics  = ACFW_Metrics_Normalizer::site_metrics( $current );

		return array(
			'site'       => array(
				'name' => get_bloginfo( 'name' ),
				'url'  => home_url(),
			),
			'period'     => $this->period_shape( $args, empty( $previous ) ? null : $this->previous_period_args( $args ) ),
			'metrics'    => $metrics,
			'comparison' => $this->comparison( $metrics, ACFW_Metrics_Normalizer::site_metrics( $previous ) ),
			'notes'      => $this->notes(),
		);
	}

	public function get_top_content( array $args ): array|WP_REST_Response {
		if ( ! $this->is_available() ) {
			return ACFW_Response_Builder::unavailable( __( 'Independent Analytics is not active or could not be detected.', 'analytics-chat-for-wordpress' ) );
		}

		return array(
			'period'  => $this->period_shape( $args ),
			'content' => $this->content_rows( $args ),
			'notes'   => $this->notes(),
		);
	}

	public function get_content_performance( array $args ): array|WP_Error|WP_REST_Response {
		$post_id = absint( $args['post_id'] ?? 0 );
		if ( 0 === $post_id && ! empty( $args['url'] ) ) {
			$post_id = url_to_postid( $args['url'] );
		}

		if ( 0 === $post_id || 'publish' !== get_post_status( $post_id ) ) {
			return new WP_Error( 'content_not_found', __( 'Content could not be found.', 'analytics-chat-for-wordpress' ), array( 'status' => 404 ) );
		}

		if ( ! $this->is_available() ) {
			return ACFW_Response_Builder::unavailable( __( 'Independent Analytics is not active or could not be detected.', 'analytics-chat-for-wordpress' ) );
		}

		$current   = $this->aggregate_period( $args, $post_id );
		$previous  = 'none' === ( $args['compare'] ?? 'previous_period' ) ? array() : $this->aggregate_period( $this->previous_period_args( $args ), $post_id );
		$metrics   = ACFW_Metrics_Normalizer::metrics( $current );
		$post_data = $this->post_shape( $post_id, true );

		return array(
			'content'       => $post_data,
			'period'        => $this->period_shape( $args ),
			'metrics'       => $this->content_metrics_shape( $metrics ),
			'comparison'    => $this->comparison( $metrics, ACFW_Metrics_Normalizer::metrics( $previous ) ),
			'top_referrers' => $this->referrer_rows( array_merge( $args, array( 'post_id' => $post_id, 'limit' => 5 ) ) ),
			'top_campaigns' => array(),
			'top_clicks'    => array(),
			'notes'         => $this->notes(),
		);
	}

	public function get_content_opportunities( array $args ): array|WP_REST_Response {
		if ( ! $this->is_available() ) {
			return ACFW_Response_Builder::unavailable( __( 'Independent Analytics is not active or could not be detected.', 'analytics-chat-for-wordpress' ) );
		}

		$rows       = $this->content_rows( array_merge( $args, array( 'limit' => 100 ) ) );
		$views      = array_column( array_column( $rows, 'metrics' ), 'views' );
		$convrates  = array_column( array_column( $rows, 'metrics' ), 'conversion_rate' );
		$bounces    = array_column( array_column( $rows, 'metrics' ), 'bounce_rate' );
		$median_v   = $this->median( $views );
		$median_cr  = $this->median( $convrates );
		$median_b   = $this->median( $bounces );
		$previous   = $this->content_rows( array_merge( $this->previous_period_args( $args ), array( 'limit' => 100 ) ) );
		$previous_by_id = array();

		foreach ( $previous as $row ) {
			$previous_by_id[ $row['post_id'] ] = $row;
		}

		$wanted = $args['opportunity_type'] ?? 'all';
		$out    = array();

		foreach ( $rows as $row ) {
			$metric = $row['metrics'];
			$types  = array();
			$prev_v = absint( $previous_by_id[ $row['post_id'] ]['metrics']['views'] ?? 0 );

			if ( $metric['views'] > $median_v && $metric['conversion_rate'] < $median_cr ) {
				$types['traffic_no_conversion'] = 'High traffic but below-average conversion rate.';
			}
			if ( $prev_v > 0 && $metric['views'] <= $prev_v * 0.8 ) {
				$types['declining'] = 'Views are down by at least 20% compared with the previous period.';
			}
			if ( $prev_v > 0 && $metric['views'] >= $prev_v * 1.2 ) {
				$types['rising'] = 'Views are up by at least 20% compared with the previous period.';
			}
			if ( $metric['views'] > $median_v && $metric['bounce_rate'] > $median_b ) {
				$types['high_exit'] = 'High traffic with above-average bounce rate.';
			}
			if ( $metric['views'] > $median_v && strtotime( $row['modified_date'] ) < strtotime( '-180 days' ) ) {
				$types['stale_but_visited'] = 'Older content is still receiving meaningful traffic.';
			}

			foreach ( $types as $type => $reason ) {
				if ( 'all' !== $wanted && $wanted !== $type ) {
					continue;
				}

				$out[] = array(
					'type'            => $type,
					'priority'        => $this->priority( $type, $metric ),
					'post_id'         => $row['post_id'],
					'title'           => $row['title'],
					'url'             => $row['url'],
					'reason'          => $reason,
					'metrics'         => array(
						'views'           => $metric['views'],
						'visitors'        => $metric['visitors'],
						'conversions'     => $metric['conversions'],
						'conversion_rate' => $metric['conversion_rate'],
					),
				);
			}
		}

		usort(
			$out,
			static fn ( array $a, array $b ): int => $b['metrics']['views'] <=> $a['metrics']['views']
		);

		return array(
			'period'        => $this->period_shape( $args ),
			'opportunities' => array_slice( $out, 0, absint( $args['limit'] ?? 20 ) ),
		);
	}

	public function get_referrers( array $args ): array {
		return array(
			'period'    => $this->period_shape( $args ),
			'referrers' => $this->referrer_rows( $args ),
		);
	}

	public function get_campaigns( array $args ): array {
		$table = $this->find_table( array( 'campaign' ) );
		if ( null === $table ) {
			return $this->feature_unavailable( 'campaigns', __( 'Independent Analytics Pro campaign tracking is not available or not enabled.', 'analytics-chat-for-wordpress' ) );
		}

		return array(
			'campaigns' => array(),
			'available' => false,
			'reason'    => __( 'Campaign table was detected, but this MVP does not recognise its schema safely.', 'analytics-chat-for-wordpress' ),
		);
	}

	public function get_forms( array $args ): array {
		$table = $this->find_table( array( 'form', 'conversion' ) );
		if ( null === $table ) {
			return $this->feature_unavailable( 'forms', __( 'Independent Analytics Pro form conversion tracking is not available or not enabled.', 'analytics-chat-for-wordpress' ) );
		}

		return array(
			'forms'     => array(),
			'available' => false,
			'reason'    => __( 'Form/conversion table was detected, but this MVP does not recognise its schema safely.', 'analytics-chat-for-wordpress' ),
		);
	}

	public function get_user_journey_summary( array $args ): array {
		$table = $this->find_table( array( 'journey', 'session' ) );
		if ( null === $table ) {
			return array(
				'available'     => false,
				'journeys'      => array(),
				'reason'        => __( 'Aggregated journey data is not available in the detected Independent Analytics installation.', 'analytics-chat-for-wordpress' ),
				'privacy_note'  => __( 'Journeys are aggregated and anonymised.', 'analytics-chat-for-wordpress' ),
			);
		}

		return array(
			'available'     => false,
			'journeys'      => array(),
			'reason'        => __( 'Journey-like table was detected, but this MVP does not recognise its schema safely.', 'analytics-chat-for-wordpress' ),
			'privacy_note'  => __( 'Journeys are aggregated and anonymised.', 'analytics-chat-for-wordpress' ),
		);
	}

	private function aggregate_period( array $args, int $post_id = 0 ): array {
		global $wpdb;

		$table = $this->views_table();
		if ( null === $table ) {
			return array();
		}

		$cols       = $this->columns( $table );
		$date_col   = $this->first_column( $cols, array( 'viewed_at', 'created_at', 'date', 'created' ) );
		$visitor_col= $this->first_column( $cols, array( 'visitor_id', 'visitors_id', 'visitor_hash', 'user_hash' ) );
		$session_col= $this->first_column( $cols, array( 'session_id', 'sessions_id' ) );
		$post_col   = $this->first_column( $cols, array( 'post_id', 'resource_id', 'object_id' ) );
		$path_col   = $this->first_column( $cols, array( 'url', 'path', 'viewed_url', 'page_url' ) );

		if ( null === $date_col ) {
			return array();
		}

		$where  = "DATE(`$date_col`) BETWEEN %s AND %s";
		$params = array( $args['start_date'], $args['end_date'] );

		if ( $post_id > 0 && null !== $post_col ) {
			$where   .= " AND `$post_col` = %d";
			$params[] = $post_id;
		} elseif ( $post_id > 0 && null !== $path_col ) {
			$permalink = get_permalink( $post_id );
			if ( false !== $permalink ) {
				$where   .= " AND `$path_col` IN (%s, %s)";
				$params[] = $permalink;
				$params[] = wp_make_link_relative( $permalink );
			}
		}

		$select_visitors = null === $visitor_col ? '0' : "COUNT(DISTINCT `$visitor_col`)";
		$select_sessions = null === $session_col ? '0' : "COUNT(DISTINCT `$session_col`)";
		$sql             = "SELECT COUNT(*) AS views, $select_visitors AS visitors, $select_sessions AS sessions FROM `$table` WHERE $where";
		$row             = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	private function content_rows( array $args ): array {
		global $wpdb;

		$table = $this->views_table();
		if ( null === $table ) {
			return array();
		}

		$cols       = $this->columns( $table );
		$date_col   = $this->first_column( $cols, array( 'viewed_at', 'created_at', 'date', 'created' ) );
		$post_col   = $this->first_column( $cols, array( 'post_id', 'resource_id', 'object_id' ) );
		$path_col   = $this->first_column( $cols, array( 'url', 'path', 'viewed_url', 'page_url' ) );
		$visitor_col= $this->first_column( $cols, array( 'visitor_id', 'visitors_id', 'visitor_hash', 'user_hash' ) );
		$session_col= $this->first_column( $cols, array( 'session_id', 'sessions_id' ) );

		if ( null === $date_col || ( null === $post_col && null === $path_col ) ) {
			return array();
		}

		$group_col = null !== $post_col ? $post_col : $path_col;
		$order_by  = $args['order_by'] ?? 'views';
		$order_sql = in_array( $order_by, array( 'visitors', 'sessions' ), true ) ? $order_by : 'views';
		$limit     = absint( $args['limit'] ?? 20 );
		$where     = "DATE(`$date_col`) BETWEEN %s AND %s";
		$params    = array( $args['start_date'], $args['end_date'] );
		$select_visitors = null === $visitor_col ? '0' : "COUNT(DISTINCT `$visitor_col`)";
		$select_sessions = null === $session_col ? '0' : "COUNT(DISTINCT `$session_col`)";
		$sql = "SELECT `$group_col` AS content_key, COUNT(*) AS views, $select_visitors AS visitors, $select_sessions AS sessions
			FROM `$table`
			WHERE $where
			GROUP BY `$group_col`
			ORDER BY $order_sql DESC
			LIMIT %d";
		$params[] = $limit * 3;
		$raw      = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		$out      = array();

		foreach ( $raw as $row ) {
			$post_id = null !== $post_col ? absint( $row['content_key'] ) : $this->post_id_from_urlish_value( (string) $row['content_key'] );
			if ( 0 === $post_id || 'publish' !== get_post_status( $post_id ) ) {
				continue;
			}

			$post_type = get_post_type( $post_id );
			if ( 'any' !== ( $args['post_type'] ?? 'any' ) && $post_type !== $args['post_type'] ) {
				continue;
			}

			$out[] = array_merge(
				$this->post_shape( $post_id, false ),
				array(
					'metrics' => $this->content_metrics_shape( ACFW_Metrics_Normalizer::metrics( $row ) ),
				)
			);

			if ( count( $out ) >= $limit ) {
				break;
			}
		}

		return $out;
	}

	private function referrer_rows( array $args ): array {
		global $wpdb;

		$table = $this->views_table();
		if ( null === $table ) {
			return array();
		}

		$cols        = $this->columns( $table );
		$date_col    = $this->first_column( $cols, array( 'viewed_at', 'created_at', 'date', 'created' ) );
		$ref_col     = $this->first_column( $cols, array( 'referrer', 'referrer_url', 'referring_url', 'source' ) );
		$post_col    = $this->first_column( $cols, array( 'post_id', 'resource_id', 'object_id' ) );
		$path_col    = $this->first_column( $cols, array( 'url', 'path', 'viewed_url', 'page_url' ) );
		$visitor_col = $this->first_column( $cols, array( 'visitor_id', 'visitors_id', 'visitor_hash', 'user_hash' ) );
		$session_col = $this->first_column( $cols, array( 'session_id', 'sessions_id' ) );

		if ( null === $date_col || null === $ref_col ) {
			return array();
		}

		$where  = "DATE(`$date_col`) BETWEEN %s AND %s AND `$ref_col` <> ''";
		$params = array( $args['start_date'], $args['end_date'] );
		if ( ! empty( $args['post_id'] ) && null !== $post_col ) {
			$where   .= " AND `$post_col` = %d";
			$params[] = absint( $args['post_id'] );
		} elseif ( ! empty( $args['post_id'] ) && null !== $path_col ) {
			$permalink = get_permalink( absint( $args['post_id'] ) );
			if ( false !== $permalink ) {
				$where   .= " AND `$path_col` IN (%s, %s)";
				$params[] = $permalink;
				$params[] = wp_make_link_relative( $permalink );
			}
		}

		$select_visitors = null === $visitor_col ? '0' : "COUNT(DISTINCT `$visitor_col`)";
		$select_sessions = null === $session_col ? '0' : "COUNT(DISTINCT `$session_col`)";
		$sql = "SELECT `$ref_col` AS source, COUNT(*) AS views, $select_visitors AS visitors, $select_sessions AS sessions
			FROM `$table`
			WHERE $where
			GROUP BY `$ref_col`
			ORDER BY visitors DESC, views DESC
			LIMIT %d";
		$params[] = absint( $args['limit'] ?? 20 );
		$rows     = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return array_map(
			fn ( array $row ): array => array(
				'source'   => $this->safe_source( (string) $row['source'] ),
				'type'     => $this->referrer_type( (string) $row['source'] ),
				'views'    => absint( $row['views'] ),
				'visitors' => absint( $row['visitors'] ),
				'sessions' => absint( $row['sessions'] ),
			),
			$rows
		);
	}

	private function views_table(): ?string {
		return $this->find_table( array( 'view' ) );
	}

	private function find_table( array $needles ): ?string {
		$key = implode( '|', $needles );
		if ( array_key_exists( $key, $this->table_cache ) ) {
			return $this->table_cache[ $key ];
		}

		foreach ( $this->all_ia_tables() as $table ) {
			$lower = strtolower( $table );
			foreach ( $needles as $needle ) {
				if ( str_contains( $lower, $needle ) ) {
					$this->table_cache[ $key ] = $table;
					return $table;
				}
			}
		}

		$this->table_cache[ $key ] = null;
		return null;
	}

	private function all_ia_tables(): array {
		global $wpdb;

		$like_a = $wpdb->esc_like( $wpdb->prefix . 'independent_analytics_' ) . '%';
		$like_b = $wpdb->esc_like( $wpdb->prefix . 'iawp_' ) . '%';
		$tables = array_merge(
			(array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like_a ) ),
			(array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like_b ) )
		);

		return array_values( array_unique( array_map( 'sanitize_text_field', $tables ) ) );
	}

	private function columns( string $table ): array {
		global $wpdb;

		if ( isset( $this->columns_cache[ $table ] ) ) {
			return $this->columns_cache[ $table ];
		}

		$rows = $wpdb->get_results( "DESCRIBE `$table`", ARRAY_A );
		$this->columns_cache[ $table ] = array_map( static fn ( array $row ): string => $row['Field'], is_array( $rows ) ? $rows : array() );
		return $this->columns_cache[ $table ];
	}

	private function first_column( array $columns, array $candidates ): ?string {
		foreach ( $candidates as $candidate ) {
			if ( in_array( $candidate, $columns, true ) ) {
				return $candidate;
			}
		}

		return null;
	}

	private function plugin_active(): bool {
		if ( defined( 'IAWP_VERSION' ) || class_exists( 'IAWP\\Independent_Analytics' ) || class_exists( 'IAWP' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'independent-analytics/iawp.php' )
			|| is_plugin_active( 'independent-analytics/independent-analytics.php' );
	}

	private function post_shape( int $post_id, bool $include_terms ): array {
		$post = get_post( $post_id );
		$data = array(
			'post_id'        => $post_id,
			'title'          => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'url'            => get_permalink( $post_id ),
			'post_type'      => get_post_type( $post_id ),
			'status'         => get_post_status( $post_id ),
			'published_date' => get_the_date( 'Y-m-d', $post_id ),
			'modified_date'  => get_the_modified_date( 'Y-m-d', $post_id ),
		);

		if ( $include_terms && $post instanceof WP_Post ) {
			$data['word_count'] = str_word_count( wp_strip_all_tags( $post->post_content ) );
			$data['categories'] = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'names' ) );
			$data['tags']       = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'names' ) );
		}

		return $data;
	}

	private function content_metrics_shape( array $metrics ): array {
		return array(
			'views'                    => $metrics['views'],
			'visitors'                 => $metrics['visitors'],
			'sessions'                 => $metrics['sessions'],
			'bounce_rate'              => $metrics['bounce_rate'],
			'average_session_duration' => $metrics['average_session_duration'],
			'conversions'              => $metrics['conversions'],
			'conversion_rate'          => $metrics['conversion_rate'],
		);
	}

	private function period_shape( array $args, ?array $compare = null ): array {
		$period = array(
			'start' => $args['start_date'],
			'end'   => $args['end_date'],
		);

		if ( null !== $compare ) {
			$period['compare_to'] = array(
				'start' => $compare['start_date'],
				'end'   => $compare['end_date'],
			);
		}

		return $period;
	}

	private function previous_period_args( array $args ): array {
		$days  = absint( $args['days'] ?? 30 );
		$end   = gmdate( 'Y-m-d', strtotime( $args['start_date'] . ' -1 day' ) );
		$start = gmdate( 'Y-m-d', strtotime( $end . ' -' . ( $days - 1 ) . ' days' ) );

		return array_merge( $args, array( 'start_date' => $start, 'end_date' => $end ) );
	}

	private function comparison( array $current, array $previous ): array {
		if ( empty( $previous ) ) {
			return array();
		}

		return array(
			'views_change_pct'           => ACFW_Metrics_Normalizer::percent_change( $current['views'] ?? 0, $previous['views'] ?? 0 ),
			'visitors_change_pct'        => ACFW_Metrics_Normalizer::percent_change( $current['visitors'] ?? 0, $previous['visitors'] ?? 0 ),
			'conversions_change_pct'     => ACFW_Metrics_Normalizer::percent_change( $current['conversions'] ?? $current['conversion_count'] ?? 0, $previous['conversions'] ?? $previous['conversion_count'] ?? 0 ),
			'conversion_rate_change_pct' => ACFW_Metrics_Normalizer::percent_change( $current['conversion_rate'] ?? 0, $previous['conversion_rate'] ?? 0 ),
		);
	}

	private function notes(): array {
		return array(
			__( 'Some metrics may be unavailable depending on Independent Analytics version and modules enabled.', 'analytics-chat-for-wordpress' ),
			__( 'Only aggregated analytics are returned; visitor identifiers are never exposed.', 'analytics-chat-for-wordpress' ),
		);
	}

	private function median( array $values ): float {
		$values = array_values( array_filter( array_map( 'floatval', $values ), static fn ( float $value ): bool => $value >= 0 ) );
		if ( empty( $values ) ) {
			return 0.0;
		}

		sort( $values );
		$count = count( $values );
		$mid   = intdiv( $count, 2 );

		return 1 === $count % 2 ? $values[ $mid ] : ( $values[ $mid - 1 ] + $values[ $mid ] ) / 2;
	}

	private function priority( string $type, array $metrics ): string {
		if ( in_array( $type, array( 'traffic_no_conversion', 'declining' ), true ) && $metrics['views'] >= 100 ) {
			return 'high';
		}

		return $metrics['views'] >= 50 ? 'medium' : 'low';
	}

	private function detected_classes(): array {
		return array_values(
			array_filter(
				array( 'IAWP', 'IAWP\\Independent_Analytics', 'IAWP_Pro' ),
				'class_exists'
			)
		);
	}

	private function feature_unavailable( string $key, string $reason ): array {
		return array(
			$key         => array(),
			'available' => false,
			'reason'    => $reason,
		);
	}

	private function post_id_from_urlish_value( string $value ): int {
		$post_id = url_to_postid( $value );
		if ( $post_id > 0 ) {
			return $post_id;
		}

		if ( str_starts_with( $value, '/' ) ) {
			return url_to_postid( home_url( $value ) );
		}

		return 0;
	}

	private function safe_source( string $source ): string {
		$host = wp_parse_url( $source, PHP_URL_HOST );
		if ( is_string( $host ) && '' !== $host ) {
			return sanitize_text_field( strtolower( $host ) );
		}

		return sanitize_text_field( $source );
	}

	private function referrer_type( string $source ): string {
		$source = strtolower( $source );
		if ( str_contains( $source, 'google' ) || str_contains( $source, 'bing' ) || str_contains( $source, 'duckduckgo' ) ) {
			return 'search';
		}
		if ( str_contains( $source, 'facebook' ) || str_contains( $source, 'instagram' ) || str_contains( $source, 'linkedin' ) || str_contains( $source, 'twitter' ) || str_contains( $source, 'x.com' ) ) {
			return 'social';
		}

		return 'referral';
	}
}
