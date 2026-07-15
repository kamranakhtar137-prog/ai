<?php
/**
 * Paginated archive handling.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles max-page calculation and invalid pagination redirects.
 */
class Vendavo_SEO_Pagination {

	/**
	 * Paginated listing configuration.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static $archives = array(
		'insights/blog'        => array(
			'path'             => 'insights/blog',
			'post_type'        => 'post',
			'posts_per_page'   => 9,
			'query'            => array(),
		),
		'insights/whitepapers' => array(
			'path'             => 'insights/whitepapers',
			'post_type'        => 'whitepapers',
			'posts_per_page'   => 9,
			'query'            => array(),
		),
		'insights/videos'      => array(
			'path'             => 'insights/videos',
			'post_type'        => 'videos',
			'posts_per_page'   => 9,
			'query'            => array(),
		),
		'glossary'             => array(
			'path'             => 'glossary',
			'post_type'        => 'glossary',
			'posts_per_page'   => 18,
			'query'            => array(),
		),
	);

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'redirect_invalid_pages' ), 1 );
	}

	/**
	 * Redirect paginated URLs beyond the last page of content.
	 */
	public static function redirect_invalid_pages() {
		$archive = self::get_current_archive();
		if ( ! $archive ) {
			return;
		}

		$current_page = self::get_current_page();
		$max_pages    = self::get_max_pages( $archive );

		if ( $current_page <= $max_pages ) {
			return;
		}

		$target = home_url( '/' . $archive['path'] . '/' );
		if ( $max_pages > 1 ) {
			$target = add_query_arg( 'page', $max_pages, $target );
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Determine whether the current request is a configured paginated archive.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_current_archive() {
		$request_path = self::get_request_path();
		if ( '' === $request_path ) {
			return null;
		}

		foreach ( self::$archives as $archive ) {
			if ( $request_path === $archive['path'] ) {
				return $archive;
			}
		}

		return null;
	}

	/**
	 * Get the current ?page= value.
	 *
	 * @return int
	 */
	public static function get_current_page() {
		$page = isset( $_GET['page'] ) ? absint( wp_unslash( $_GET['page'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return max( 1, $page );
	}

	/**
	 * Calculate the maximum number of pages for an archive.
	 *
	 * @param array<string, mixed> $archive Archive config.
	 * @return int
	 */
	public static function get_max_pages( $archive ) {
		$count = self::get_post_count( $archive );
		$ppp   = max( 1, (int) $archive['posts_per_page'] );

		return max( 1, (int) ceil( $count / $ppp ) );
	}

	/**
	 * Count published posts for an archive.
	 *
	 * @param array<string, mixed> $archive Archive config.
	 * @return int
	 */
	private static function get_post_count( $archive ) {
		$cache_key = 'vendavo_seo_count_' . md5( wp_json_encode( $archive ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$query_args = array_merge(
			array(
				'post_type'              => $archive['post_type'],
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			),
			(array) $archive['query']
		);

		$query = new WP_Query( $query_args );
		$count = (int) $query->found_posts;

		set_transient( $cache_key, $count, HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Resolve the current front-end request path.
	 *
	 * @return string
	 */
	private static function get_request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path        = trim( (string) parse_url( $request_uri, PHP_URL_PATH ), '/' );

		return strtolower( $path );
	}
}
