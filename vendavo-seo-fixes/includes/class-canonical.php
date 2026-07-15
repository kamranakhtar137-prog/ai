<?php
/**
 * Canonical tag handling for paginated archives.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures paginated archive canonicals are absolute and include the page query arg.
 */
class Vendavo_SEO_Canonical {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'wpseo_canonical', array( __CLASS__, 'filter_yoast_canonical' ), 20 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'filter_core_canonical' ), 20 );
	}

	/**
	 * Normalize Yoast canonical URLs on paginated listing pages.
	 *
	 * @param string $canonical Canonical URL.
	 * @return string
	 */
	public static function filter_yoast_canonical( $canonical ) {
		return self::normalize_paginated_canonical( $canonical );
	}

	/**
	 * Normalize core canonical URLs on paginated listing pages.
	 *
	 * @param string $canonical Canonical URL.
	 * @return string
	 */
	public static function filter_core_canonical( $canonical ) {
		return self::normalize_paginated_canonical( $canonical );
	}

	/**
	 * Build an absolute canonical URL with ?page=N when applicable.
	 *
	 * @param string $canonical Existing canonical.
	 * @return string
	 */
	private static function normalize_paginated_canonical( $canonical ) {
		$archive = Vendavo_SEO_Pagination::get_current_archive();
		if ( ! $archive ) {
			return $canonical;
		}

		$current_page = Vendavo_SEO_Pagination::get_current_page();
		$base_url     = home_url( '/' . $archive['path'] . '/' );

		if ( $current_page <= 1 ) {
			return esc_url( $base_url );
		}

		return esc_url( add_query_arg( 'page', $current_page, $base_url ) );
	}
}
