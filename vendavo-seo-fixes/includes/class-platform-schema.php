<?php
/**
 * Dynamic platform page schema output.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs dynamic FAQPage and SoftwareApplication schema.
 */
class Vendavo_SEO_Platform_Schema {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render' ), 20 );
	}

	/**
	 * Render dynamic schema for eligible pages.
	 */
	public static function render() {
		if ( ! is_page() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! Vendavo_SEO_Schema_Parser::is_schema_enabled_page( $post ) ) {
			return;
		}

		$software_schema = self::build_software_application_schema( $post );
		if ( ! empty( $software_schema ) ) {
			self::print_json_ld( $software_schema );
		}

		$faq_schema = self::build_faq_page_schema( $post );
		if ( ! empty( $faq_schema ) ) {
			self::print_json_ld( $faq_schema );
		}
	}

	/**
	 * Build dynamic SoftwareApplication schema from page data.
	 *
	 * @param WP_Post $post Page object.
	 * @return array<string, mixed>
	 */
	private static function build_software_application_schema( WP_Post $post ) {
		$name        = Vendavo_SEO_Schema_Parser::get_software_name( $post );
		$description = Vendavo_SEO_Schema_Parser::get_software_description( $post );

		if ( '' === $name || '' === $description ) {
			return array();
		}

		$schema = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'SoftwareApplication',
			'name'                => $name,
			'description'         => $description,
			'url'                 => get_permalink( $post ),
			'applicationCategory' => 'BusinessApplication',
			'operatingSystem'     => 'Web',
			'provider'            => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			),
		);

		/**
		 * Filter the dynamic SoftwareApplication schema.
		 *
		 * @param array<string, mixed> $schema SoftwareApplication schema.
		 * @param WP_Post              $post   Page object.
		 */
		return (array) apply_filters( 'vendavo_seo_software_application_schema', $schema, $post );
	}

	/**
	 * Build dynamic FAQPage schema from page content.
	 *
	 * @param WP_Post $post Page object.
	 * @return array<string, mixed>
	 */
	private static function build_faq_page_schema( WP_Post $post ) {
		$faqs = Vendavo_SEO_Schema_Parser::get_faqs( $post );
		if ( empty( $faqs ) ) {
			return array();
		}

		$entities = array();

		foreach ( $faqs as $faq ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq['answer'],
				),
			);
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);

		/**
		 * Filter the dynamic FAQPage schema.
		 *
		 * @param array<string, mixed> $schema FAQPage schema.
		 * @param WP_Post              $post   Page object.
		 */
		return (array) apply_filters( 'vendavo_seo_faq_page_schema', $schema, $post );
	}

	/**
	 * Print a JSON-LD script tag.
	 *
	 * @param array<string, mixed> $schema Schema array.
	 */
	private static function print_json_ld( $schema ) {
		echo '<script type="application/ld+json" class="vendavo-dynamic-schema">';
		echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		echo "</script>\n";
	}
}
