<?php
/**
 * Lowercase URL normalization.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects mixed/uppercase request paths to lowercase equivalents.
 */
class Vendavo_SEO_Lowercase_Redirect {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 0 );
	}

	/**
	 * Redirect uppercase paths to lowercase.
	 */
	public static function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $request_uri ) {
			return;
		}

		$parts = wp_parse_url( $request_uri );
		$path  = isset( $parts['path'] ) ? $parts['path'] : '';

		if ( '' === $path || $path === strtolower( $path ) ) {
			return;
		}

		$lower_path = strtolower( $path );
		$target     = $lower_path;

		if ( ! empty( $parts['query'] ) ) {
			$target .= '?' . $parts['query'];
		}

		wp_safe_redirect( home_url( $target ), 301 );
		exit;
	}
}
