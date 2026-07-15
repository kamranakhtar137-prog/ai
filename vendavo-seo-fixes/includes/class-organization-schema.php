<?php
/**
 * Dynamic Organization schema helpers.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps Organization schema on production URLs and removes staging references.
 */
class Vendavo_SEO_Organization_Schema {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'wpseo_schema_graph', array( __CLASS__, 'sanitize_yoast_graph' ), 20, 2 );
		add_filter( 'wpseo_schema_organization', array( __CLASS__, 'sanitize_organization_node' ), 20, 1 );
	}

	/**
	 * Replace staging URLs anywhere in the Yoast schema graph.
	 *
	 * @param array<int, mixed> $graph   Schema graph.
	 * @param mixed             $context Schema context.
	 * @return array<int, mixed>
	 */
	public static function sanitize_yoast_graph( $graph, $context ) {
		unset( $context );

		return self::sanitize_schema_values( $graph );
	}

	/**
	 * Force Yoast Organization node to use production URLs.
	 *
	 * @param array<string, mixed> $organization Organization node.
	 * @return array<string, mixed>
	 */
	public static function sanitize_organization_node( $organization ) {
		if ( ! is_array( $organization ) ) {
			return $organization;
		}

		$organization['url'] = home_url( '/' );

		if ( ! empty( $organization['logo'] ) && is_array( $organization['logo'] ) ) {
			$logo_url = self::get_logo_url();
			if ( $logo_url ) {
				$organization['logo']['url']         = $logo_url;
				$organization['logo']['contentUrl']    = $logo_url;
				$organization['logo']['@id']           = home_url( '/#schema/logo/' );
				$organization['logo']['caption']       = get_bloginfo( 'name' );
			}
		}

		return self::sanitize_schema_values( $organization );
	}

	/**
	 * Build a dynamic Organization schema array.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_organization_schema() {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
			'sameAs'   => self::get_same_as_urls(),
		);

		$logo = self::get_logo_url();
		if ( $logo ) {
			$schema['logo'] = $logo;
		}

		/**
		 * Filter the dynamic Organization schema.
		 *
		 * @param array<string, mixed> $schema Organization schema.
		 */
		return (array) apply_filters( 'vendavo_seo_organization_schema', $schema );
	}

	/**
	 * Resolve the site logo URL.
	 *
	 * @return string
	 */
	private static function get_logo_url() {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$logo = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( $logo ) {
				return esc_url_raw( $logo );
			}
		}

		$site_icon = get_site_icon_url( 512 );
		if ( $site_icon ) {
			return esc_url_raw( $site_icon );
		}

		return '';
	}

	/**
	 * Collect social profile URLs from theme mods or defaults.
	 *
	 * @return array<int, string>
	 */
	private static function get_same_as_urls() {
		$urls = array(
			'https://www.facebook.com/Vendavo/',
			'https://x.com/Vendavo/',
			'https://www.youtube.com/user/Vendavo',
			'https://www.linkedin.com/company/vendavo/',
			home_url( '/' ),
		);

		/**
		 * Filter Organization sameAs URLs.
		 *
		 * @param array<int, string> $urls Social profile URLs.
		 */
		return array_values( array_unique( array_filter( (array) apply_filters( 'vendavo_seo_organization_same_as', $urls ) ) ) );
	}

	/**
	 * Recursively replace staging domains inside schema arrays/strings.
	 *
	 * @param mixed $value Schema value.
	 * @return mixed
	 */
	private static function sanitize_schema_values( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::sanitize_schema_values( $item );
			}

			return $value;
		}

		if ( is_string( $value ) ) {
			return str_replace(
				array(
					'https://vendavostg.wpenginepowered.com',
					'http://vendavostg.wpenginepowered.com',
					'//vendavostg.wpenginepowered.com',
				),
				home_url(),
				$value
			);
		}

		return $value;
	}
}
