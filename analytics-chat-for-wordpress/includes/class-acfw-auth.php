<?php
/**
 * API key authentication.
 *
 * @package AnalyticsChatForWordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACFW_Auth {
	public const OPTION_KEY_HASH = 'acfw_api_key_hash';
	public const OPTION_BRIDGE_TOKEN_HASH = 'acfw_bridge_token_hash';

	public function has_key(): bool {
		return '' !== (string) get_option( self::OPTION_KEY_HASH, '' );
	}

	public function generate_key(): string {
		$key  = 'acfw_' . bin2hex( random_bytes( 32 ) );
		$hash = wp_hash_password( $key );

		update_option( self::OPTION_KEY_HASH, $hash, false );

		return $key;
	}

	public function revoke_key(): void {
		delete_option( self::OPTION_KEY_HASH );
	}

	public function set_bridge_token( string $token ): void {
		update_option( self::OPTION_BRIDGE_TOKEN_HASH, wp_hash_password( $token ), false );
	}

	public function revoke_bridge_token(): void {
		delete_option( self::OPTION_BRIDGE_TOKEN_HASH );
	}

	public function authenticate_request( WP_REST_Request $request ): bool|WP_Error {
		$header = $request->get_header( 'authorization' );

		if ( '' === $header ) {
			return new WP_Error(
				'unauthorized',
				__( 'Missing Authorization bearer token.', 'analytics-chat-for-wordpress' ),
				array( 'status' => 401 )
			);
		}

		if ( ! preg_match( '/^Bearer\s+(.+)$/i', $header, $matches ) ) {
			return new WP_Error(
				'unauthorized',
				__( 'Invalid Authorization header format.', 'analytics-chat-for-wordpress' ),
				array( 'status' => 401 )
			);
		}

		$token        = trim( $matches[1] );
		$stored_hash  = (string) get_option( self::OPTION_KEY_HASH, '' );
		$bridge_hash  = (string) get_option( self::OPTION_BRIDGE_TOKEN_HASH, '' );
		$direct_match = '' !== $stored_hash && wp_check_password( $token, $stored_hash );
		$bridge_match = '' !== $bridge_hash && wp_check_password( $token, $bridge_hash );

		if ( ! $direct_match && ! $bridge_match ) {
			return new WP_Error(
				'forbidden',
				__( 'Invalid API key.', 'analytics-chat-for-wordpress' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
