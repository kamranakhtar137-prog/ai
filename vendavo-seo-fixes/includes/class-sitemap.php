<?php
/**
 * Yoast sitemap exclusions.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes low-value and audit-flagged URLs from Yoast XML sitemaps.
 */
class Vendavo_SEO_Sitemap {

	/**
	 * Post types excluded from XML sitemaps.
	 *
	 * @var string[]
	 */
	private static $excluded_post_types = array(
		'board-of-director',
		'leadership-team',
	);

	/**
	 * Taxonomies excluded from XML sitemaps.
	 *
	 * @var string[]
	 */
	private static $excluded_taxonomies = array(
		'category',
	);

	/**
	 * Author archives are excluded via dedicated Yoast filter.
	 *
	 * @var bool
	 */
	private static $exclude_authors = true;

	/**
	 * Individual page paths excluded from the page sitemap.
	 *
	 * @var string[]
	 */
	private static $excluded_page_paths = array(
		'mss',
	);

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'wpseo_sitemap_exclude_post_type', array( __CLASS__, 'exclude_post_type' ), 10, 2 );
		add_filter( 'wpseo_sitemap_exclude_taxonomy', array( __CLASS__, 'exclude_taxonomy' ), 10, 2 );
		add_filter( 'wpseo_sitemap_exclude_author', array( __CLASS__, 'exclude_author' ), 10, 2 );
		add_filter( 'wpseo_sitemap_entry', array( __CLASS__, 'exclude_page_entry' ), 10, 3 );
	}

	/**
	 * Exclude configured post types from sitemap generation.
	 *
	 * @param bool   $excluded Current exclusion state.
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public static function exclude_post_type( $excluded, $post_type ) {
		if ( in_array( $post_type, self::$excluded_post_types, true ) ) {
			return true;
		}

		return $excluded;
	}

	/**
	 * Exclude configured taxonomies from sitemap generation.
	 *
	 * @param bool   $excluded Current exclusion state.
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	public static function exclude_taxonomy( $excluded, $taxonomy ) {
		if ( in_array( $taxonomy, self::$excluded_taxonomies, true ) ) {
			return true;
		}

		return $excluded;
	}

	/**
	 * Exclude author archives from sitemap generation.
	 *
	 * @param bool $excluded Current exclusion state.
	 * @param int  $user_id User ID.
	 * @return bool
	 */
	public static function exclude_author( $excluded, $user_id ) {
		unset( $user_id );

		if ( self::$exclude_authors ) {
			return true;
		}

		return $excluded;
	}

	/**
	 * Exclude specific page URLs from sitemap entries.
	 *
	 * @param array<string, mixed>|false $url     Sitemap entry.
	 * @param string                     $type    Sitemap object type.
	 * @param object                     $object  Source object.
	 * @return array<string, mixed>|false
	 */
	public static function exclude_page_entry( $url, $type, $object ) {
		unset( $type );

		if ( ! $url || ! is_array( $url ) || empty( $url['loc'] ) || ! isset( $object->post_type ) || 'page' !== $object->post_type ) {
			return $url;
		}

		$path = trim( (string) parse_url( $url['loc'], PHP_URL_PATH ), '/' );

		if ( in_array( $path, self::$excluded_page_paths, true ) ) {
			return false;
		}

		return $url;
	}

	/**
	 * Return a human-readable list of sitemap exclusions for audit reporting.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function get_removed_summary() {
		return array(
			'post_types' => self::$excluded_post_types,
			'taxonomies' => self::$excluded_taxonomies,
			'authors'    => self::$exclude_authors ? array( 'All author archive URLs (/author/*)' ) : array(),
			'pages'      => array_map(
				static function ( $path ) {
					return home_url( '/' . $path . '/' );
				},
				self::$excluded_page_paths
			),
		);
	}
}
