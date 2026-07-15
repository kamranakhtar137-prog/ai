<?php
/**
 * HTML output buffer for head-level SEO cleanup.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cleans duplicate canonicals, staging schema, and invalid pagination links from final HTML.
 */
class Vendavo_SEO_Output_Buffer {

	/**
	 * Register output buffering.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
	}

	/**
	 * Begin buffering front-end HTML.
	 */
	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		ob_start( array( __CLASS__, 'filter_html' ) );
	}

	/**
	 * Apply SEO cleanup filters to rendered HTML.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public static function filter_html( $html ) {
		$html = self::remove_staging_organization_schema( $html );
		$html = self::remove_relative_canonical_tags( $html );
		$html = self::remove_invalid_loadmore_links( $html );

		return $html;
	}

	/**
	 * Remove hardcoded Organization schema that still references staging.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	private static function remove_staging_organization_schema( $html ) {
		return preg_replace(
			'/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?vendavostg\.wpenginepowered\.com.*?<\/script>\s*/is',
			'',
			$html
		);
	}

	/**
	 * Remove relative canonical link tags; Yoast already outputs the absolute version.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	private static function remove_relative_canonical_tags( $html ) {
		return preg_replace(
			'/<link\s+rel=["\']canonical["\']\s+href=["\']\/[^"\']*["\']\s*\/?>\s*/i',
			'',
			$html
		);
	}

	/**
	 * Remove load-more links when there is no next page of content.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	private static function remove_invalid_loadmore_links( $html ) {
		$archive = Vendavo_SEO_Pagination::get_current_archive();

		if ( ! $archive ) {
			return $html;
		}

		$current_page = Vendavo_SEO_Pagination::get_current_page();
		$max_pages    = Vendavo_SEO_Pagination::get_max_pages( $archive );

		if ( $current_page >= $max_pages ) {
			$html = preg_replace(
				'/<div[^>]*class=["\'][^"\']*vfp-loadmore-wrap[^"\']*["\'][^>]*>.*?<\/div>\s*/is',
				'',
				$html
			);
		}

		return $html;
	}
}
