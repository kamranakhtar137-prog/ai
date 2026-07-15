<?php
/**
 * Platform page schema output.
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds FAQPage and SoftwareApplication schema to platform pages.
 */
class Vendavo_SEO_Platform_Schema {

	/**
	 * Platform page metadata keyed by page slug.
	 *
	 * @var array<string, array<string, string>>
	 */
	private static $software_apps = array(
		'platform'                    => array(
			'name'        => 'Vendavo Commercial Excellence Platform',
			'description' => 'A unified commercial platform for B2B pricing, quoting, rebates, and analytics.',
		),
		'pricing'                     => array(
			'name'        => 'Vendavo Pricing',
			'description' => 'Pricing software that brings control, clarity, and precision to complex pricing environments.',
		),
		'quoting-and-agreements'      => array(
			'name'        => 'Vendavo Quoting & Agreements',
			'description' => 'Quoting and agreement management software for faster, more profitable B2B deals.',
		),
		'rebates'                     => array(
			'name'        => 'Vendavo Rebates',
			'description' => 'Rebate and incentive management software for compliant, profitable growth.',
		),
		'ai-and-intelligence'         => array(
			'name'        => 'Vendavo AI & Intelligence',
			'description' => 'Embedded quantitative AI, machine learning, and agentic AI for commercial decision making.',
		),
		'analytics'                   => array(
			'name'        => 'Vendavo Analytics',
			'description' => 'Pricing analytics and margin bridge analysis for commercial performance insight.',
		),
		'integrations-and-security'   => array(
			'name'        => 'Vendavo Integrations & Security',
			'description' => 'Enterprise integrations and security for the Vendavo commercial excellence platform.',
		),
	);

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render' ), 20 );
	}

	/**
	 * Render platform page schema blocks.
	 */
	public static function render() {
		if ( ! is_page() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$slug = self::get_platform_slug( $post );
		if ( ! $slug || ! isset( self::$software_apps[ $slug ] ) ) {
			return;
		}

		$app_meta = self::$software_apps[ $slug ];
		$page_url = get_permalink( $post );

		$software_schema = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'SoftwareApplication',
			'name'                => $app_meta['name'],
			'description'         => $app_meta['description'],
			'url'                 => $page_url,
			'applicationCategory' => 'BusinessApplication',
			'operatingSystem'     => 'Web',
			'provider'            => array(
				'@type' => 'Organization',
				'name'  => 'Vendavo',
				'url'   => home_url( '/' ),
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $software_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";

		$faqs = self::extract_faqs_from_page( $post );
		if ( empty( $faqs ) ) {
			return;
		}

		$faq_entities = array();
		foreach ( $faqs as $faq ) {
			$faq_entities[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq['answer'],
				),
			);
		}

		$faq_schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $faq_entities,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}

	/**
	 * Resolve the platform slug for the current page.
	 *
	 * @param WP_Post $post Current page.
	 * @return string|null
	 */
	private static function get_platform_slug( WP_Post $post ) {
		$path = trim( str_replace( home_url(), '', get_permalink( $post ) ), '/' );

		if ( 'platform' === $path ) {
			return 'platform';
		}

		if ( 0 === strpos( $path, 'platform/' ) ) {
			return trim( str_replace( 'platform/', '', $path ), '/' );
		}

		return null;
	}

	/**
	 * Extract FAQ question/answer pairs from Elementor nested tabs.
	 *
	 * @param WP_Post $post Current page.
	 * @return array<int, array<string, string>>
	 */
	private static function extract_faqs_from_page( WP_Post $post ) {
		$content = self::get_rendered_page_content( $post );
		if ( '' === $content ) {
			return array();
		}

		$faqs      = array();
		$questions = array();

		if ( preg_match_all( '/<span class="e-n-tab-title-text">\s*(.*?)\s*<\/span>/is', $content, $question_matches ) ) {
			foreach ( $question_matches[1] as $question ) {
				$question = trim( wp_strip_all_tags( $question ) );
				if ( '' !== $question ) {
					$questions[] = $question;
				}
			}
		}

		if ( empty( $questions ) ) {
			return $faqs;
		}

		if ( ! preg_match( '/<div class="e-n-tabs-content">(.*)<\/div>\s*<\/div>\s*<\/div>/is', $content, $content_match ) ) {
			return $faqs;
		}

		if ( ! preg_match_all( '/<div[^>]*id="e-n-tab-content-[^"]+"[^>]*>(.*?)<\/div>\s*(?=<div[^>]*id="e-n-tab-content-|<\/div>\s*<\/div>\s*<\/div>)/is', $content_match[1], $panel_matches ) ) {
			return $faqs;
		}

		foreach ( $panel_matches[1] as $index => $panel_html ) {
			if ( ! isset( $questions[ $index ] ) ) {
				continue;
			}

			$answer = self::extract_answer_text( $panel_html );
			if ( '' === $answer ) {
				continue;
			}

			$faqs[] = array(
				'question' => $questions[ $index ],
				'answer'   => $answer,
			);
		}

		return $faqs;
	}

	/**
	 * Render page content via Elementor when available.
	 *
	 * @param WP_Post $post Current page.
	 * @return string
	 */
	private static function get_rendered_page_content( WP_Post $post ) {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$elementor = \Elementor\Plugin::$instance;
			if ( $elementor->db->is_built_with_elementor( $post->ID ) ) {
				return (string) $elementor->frontend->get_builder_content( $post->ID, true );
			}
		}

		return (string) apply_filters( 'the_content', $post->post_content );
	}

	/**
	 * Pull answer text from a tab panel, skipping duplicate question headings.
	 *
	 * @param string $panel_html Tab panel HTML.
	 * @return string
	 */
	private static function extract_answer_text( $panel_html ) {
		if ( preg_match_all( '/<div[^>]*elementor-widget-text-editor[^>]*>(.*?)<\/div>/is', $panel_html, $editor_matches ) ) {
			foreach ( $editor_matches[1] as $chunk ) {
				$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $chunk ) ) );
				if ( '' !== $text && ! preg_match( '/\?\s*$/', $text ) ) {
					return $text;
				}
			}
		}

		return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $panel_html ) ) );
	}
}
