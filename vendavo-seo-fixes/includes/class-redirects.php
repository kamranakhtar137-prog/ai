<?php
/**
 * Redirect rules loaded from CSV.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies redirect audit rules before page rendering.
 */
class Vendavo_SEO_Redirects {

	/**
	 * Cached redirect map.
	 *
	 * @var array<string, array<string, int>>|null
	 */
	private static $redirects = null;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 2 );
	}

	/**
	 * Apply a redirect when the current request matches the audit list.
	 */
	public static function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$redirects = self::get_redirects();
		if ( empty( $redirects ) ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$lookup_key  = self::normalize_lookup_key( $request_uri );

		if ( ! isset( $redirects[ $lookup_key ] ) ) {
			return;
		}

		$rule   = $redirects[ $lookup_key ];
		$target = $rule['target'];
		$code   = $rule['code'];

		wp_safe_redirect( $target, $code );
		exit;
	}

	/**
	 * Load redirect rules from the bundled CSV file.
	 *
	 * @return array<string, array<string, int|string>>
	 */
	private static function get_redirects() {
		if ( null !== self::$redirects ) {
			return self::$redirects;
		}

		self::$redirects = array();
		$csv_path        = VENDAVO_SEO_PATH . 'data/redirects.csv';

		if ( ! file_exists( $csv_path ) ) {
			return self::$redirects;
		}

		$handle = fopen( $csv_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			return self::$redirects;
		}

		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return self::$redirects;
		}

		$header_map = array();
		foreach ( $header as $index => $column ) {
			$header_map[ strtolower( trim( $column ) ) ] = $index;
		}

		while ( ( $row = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			$source = self::get_csv_value( $row, $header_map, array( 'source', 'source url' ) );
			$target = self::get_csv_value( $row, $header_map, array( 'new target', 'target', 'destination' ) );

			if ( '' === $source || '' === $target ) {
				continue;
			}

			$code = (int) self::get_csv_value( $row, $header_map, array( 'code', 'status', 'redirect code' ) );
			if ( $code < 300 || $code > 399 ) {
				$code = 301;
			}

			$key = self::normalize_lookup_key( $source );
			self::$redirects[ $key ] = array(
				'target' => self::normalize_target_url( $target ),
				'code'   => $code,
			);
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return self::$redirects;
	}

	/**
	 * Read a value from a CSV row using possible header aliases.
	 *
	 * @param array<int, string>   $row Row data.
	 * @param array<string, int>   $header_map Header indexes.
	 * @param array<int, string>   $aliases Possible header names.
	 * @return string
	 */
	private static function get_csv_value( $row, $header_map, $aliases ) {
		foreach ( $aliases as $alias ) {
			if ( isset( $header_map[ $alias ], $row[ $header_map[ $alias ] ] ) ) {
				return trim( (string) $row[ $header_map[ $alias ] ] );
			}
		}

		return '';
	}

	/**
	 * Normalize a source URL or path for lookup.
	 *
	 * @param string $url Source URL or path.
	 * @return string
	 */
	private static function normalize_lookup_key( $url ) {
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? strtolower( untrailingslashit( $parts['path'] ) ) : '';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		return $path . $query;
	}

	/**
	 * Normalize redirect targets to absolute production URLs.
	 *
	 * @param string $target Redirect target.
	 * @return string
	 */
	private static function normalize_target_url( $target ) {
		if ( 0 === strpos( $target, 'http://' ) || 0 === strpos( $target, 'https://' ) ) {
			return $target;
		}

		return home_url( '/' . ltrim( $target, '/' ) );
	}
}
