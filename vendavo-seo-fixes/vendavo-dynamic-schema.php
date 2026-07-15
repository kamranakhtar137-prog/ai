<?php
/**
 * Plugin Name: Vendavo Dynamic Schema
 * Description: Dynamic JSON-LD for Organization, SoftwareApplication, and FAQPage on Vendavo platform pages. No static files required.
 * Version: 1.0.0
 * Author: Keoch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Vendavo_Dynamic_Schema_Buffer {
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
	}
	public static function start_buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		ob_start( array( __CLASS__, 'filter_html' ) );
	}
	public static function filter_html( $html ) {
		return preg_replace(
			'/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?vendavostg\.wpenginepowered\.com.*?<\/script>\s*/is',
			'',
			$html
		);
	}
}
/**
 * Dynamic schema content parser.
 *
 * @package Vendavo_SEO_Fixes
 */

/**
 * Extracts FAQ and page metadata from WordPress/Elementor content.
 */
class Vendavo_SEO_Schema_Parser {

	/**
	 * Check whether a page should receive platform schema.
	 *
	 * @param WP_Post $post Page object.
	 * @return bool
	 */
	public static function is_schema_enabled_page( WP_Post $post ) {
		$enabled = self::is_platform_page( $post );

		/**
		 * Allow other pages to opt into dynamic FAQPage / SoftwareApplication schema.
		 *
		 * @param bool    $enabled Whether schema is enabled.
		 * @param WP_Post $post    Page object.
		 */
		return (bool) apply_filters( 'vendavo_seo_schema_enabled_for_post', $enabled, $post );
	}

	/**
	 * Determine whether the page lives under /platform/.
	 *
	 * @param WP_Post $post Page object.
	 * @return bool
	 */
	public static function is_platform_page( WP_Post $post ) {
		$path = trim( str_replace( home_url(), '', get_permalink( $post ) ), '/' );

		return ( 'platform' === $path || 0 === strpos( $path, 'platform/' ) );
	}

	/**
	 * Build SoftwareApplication name from the page.
	 *
	 * @param WP_Post $post Page object.
	 * @return string
	 */
	public static function get_software_name( WP_Post $post ) {
		$title = get_the_title( $post );
		$title = preg_replace( '/\s*\|\s*Vendavo\s*$/i', '', $title );
		$title = trim( wp_strip_all_tags( $title ) );

		/**
		 * Filter the SoftwareApplication name.
		 *
		 * @param string  $title Software name.
		 * @param WP_Post $post  Page object.
		 */
		return (string) apply_filters( 'vendavo_seo_software_name', $title, $post );
	}

	/**
	 * Build SoftwareApplication description from SEO meta or page content.
	 *
	 * @param WP_Post $post Page object.
	 * @return string
	 */
	public static function get_software_description( WP_Post $post ) {
		$description = self::get_meta_description( $post );

		if ( '' === $description && ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		}

		if ( '' === $description ) {
			$description = self::get_first_text_paragraph( $post );
		}

		$description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $description ) ) );

		/**
		 * Filter the SoftwareApplication description.
		 *
		 * @param string  $description Software description.
		 * @param WP_Post $post        Page object.
		 */
		return (string) apply_filters( 'vendavo_seo_software_description', $description, $post );
	}

	/**
	 * Extract FAQ pairs from Elementor data or rendered page HTML.
	 *
	 * @param WP_Post $post Page object.
	 * @return array<int, array<string, string>>
	 */
	public static function get_faqs( WP_Post $post ) {
		$faqs = self::extract_faqs_from_elementor_data( $post->ID );

		if ( empty( $faqs ) ) {
			$faqs = self::extract_faqs_from_rendered_content( $post );
		}

		/**
		 * Filter parsed FAQ items before schema output.
		 *
		 * @param array<int, array<string, string>> $faqs FAQ question/answer pairs.
		 * @param WP_Post                           $post Page object.
		 */
		return (array) apply_filters( 'vendavo_seo_faq_items', $faqs, $post );
	}

	/**
	 * Read Yoast, Rank Math, or core meta description.
	 *
	 * @param WP_Post $post Page object.
	 * @return string
	 */
	private static function get_meta_description( WP_Post $post ) {
		$meta_keys = array(
			'_yoast_wpseo_metadesc',
			'rank_math_description',
			'_aioseo_description',
		);

		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( $post->ID, $meta_key, true );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	/**
	 * Get the first meaningful paragraph from rendered content.
	 *
	 * @param WP_Post $post Page object.
	 * @return string
	 */
	private static function get_first_text_paragraph( WP_Post $post ) {
		$content = self::get_rendered_page_content( $post );

		if ( preg_match_all( '/<div[^>]*elementor-widget-text-editor[^>]*>(.*?)<\/div>/is', $content, $matches ) ) {
			foreach ( $matches[1] as $chunk ) {
				$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $chunk ) ) );
				if ( '' !== $text && strlen( $text ) > 40 ) {
					return $text;
				}
			}
		}

		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $content, $match ) ) {
			return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $match[1] ) ) );
		}

		return '';
	}

	/**
	 * Parse FAQ content from Elementor post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array<string, string>>
	 */
	private static function extract_faqs_from_elementor_data( $post_id ) {
		$raw = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return array();
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		$faqs = array();
		self::walk_elementor_nodes( $data, $faqs );

		return $faqs;
	}

	/**
	 * Recursively walk Elementor nodes and collect FAQ pairs.
	 *
	 * @param array<int, mixed>                $nodes Elementor nodes.
	 * @param array<int, array<string, string>> $faqs  FAQ accumulator.
	 */
	private static function walk_elementor_nodes( $nodes, &$faqs ) {
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$widget_type = isset( $node['widgetType'] ) ? $node['widgetType'] : '';

			if ( in_array( $widget_type, array( 'accordion', 'toggle', 'nested-tabs', 'n-accordion' ), true ) ) {
				$faqs = array_merge( $faqs, self::extract_faqs_from_widget_node( $node ) );
			}

			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				self::walk_elementor_nodes( $node['elements'], $faqs );
			}
		}
	}

	/**
	 * Extract FAQ pairs from a single Elementor widget node.
	 *
	 * @param array<string, mixed> $node Widget node.
	 * @return array<int, array<string, string>>
	 */
	private static function extract_faqs_from_widget_node( $node ) {
		$faqs     = array();
		$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();

		if ( ! empty( $settings['tabs'] ) && is_array( $settings['tabs'] ) ) {
			foreach ( $settings['tabs'] as $index => $tab ) {
				if ( ! is_array( $tab ) ) {
					continue;
				}

				$question = self::get_tab_question( $tab );
				$answer   = self::get_tab_answer( $tab, $node, $index );

				if ( '' !== $question && '' !== $answer ) {
					$faqs[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}

			return $faqs;
		}

		if ( 'nested-tabs' === ( $node['widgetType'] ?? '' ) && ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$titles = self::collect_nested_tab_titles( $node );
			foreach ( $node['elements'] as $index => $child ) {
				if ( ! is_array( $child ) ) {
					continue;
				}

				$question = isset( $titles[ $index ] ) ? $titles[ $index ] : '';
				$answer   = self::collect_text_from_node( $child );

				if ( '' !== $question && '' !== $answer ) {
					$faqs[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}
		}

		return $faqs;
	}

	/**
	 * Resolve a tab question label.
	 *
	 * @param array<string, mixed> $tab Tab settings.
	 * @return string
	 */
	private static function get_tab_question( $tab ) {
		$keys = array( 'tab_title', 'title', 'question', 'accordion_title', 'item_title' );

		foreach ( $keys as $key ) {
			if ( ! empty( $tab[ $key ] ) && is_string( $tab[ $key ] ) ) {
				return trim( wp_strip_all_tags( $tab[ $key ] ) );
			}
		}

		return '';
	}

	/**
	 * Resolve a tab answer from settings or child nodes.
	 *
	 * @param array<string, mixed> $tab   Tab settings.
	 * @param array<string, mixed> $node  Widget node.
	 * @param int                    $index Tab index.
	 * @return string
	 */
	private static function get_tab_answer( $tab, $node, $index ) {
		$keys = array( 'tab_content', 'content', 'answer', 'accordion_content', 'item_description', 'text' );

		foreach ( $keys as $key ) {
			if ( ! empty( $tab[ $key ] ) && is_string( $tab[ $key ] ) ) {
				return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $tab[ $key ] ) ) );
			}
		}

		if ( ! empty( $node['elements'][ $index ] ) && is_array( $node['elements'][ $index ] ) ) {
			return self::collect_text_from_node( $node['elements'][ $index ] );
		}

		return '';
	}

	/**
	 * Collect nested tab titles from widget settings/children.
	 *
	 * @param array<string, mixed> $node Widget node.
	 * @return array<int, string>
	 */
	private static function collect_nested_tab_titles( $node ) {
		$titles = array();

		if ( ! empty( $node['settings']['tabs'] ) && is_array( $node['settings']['tabs'] ) ) {
			foreach ( $node['settings']['tabs'] as $tab ) {
				if ( is_array( $tab ) ) {
					$title = self::get_tab_question( $tab );
					if ( '' !== $title ) {
						$titles[] = $title;
					}
				}
			}
		}

		return $titles;
	}

	/**
	 * Collect visible text from an Elementor node tree.
	 *
	 * @param array<string, mixed> $node Elementor node.
	 * @return string
	 */
	private static function collect_text_from_node( $node ) {
		$chunks = array();

		if ( isset( $node['settings'] ) && is_array( $node['settings'] ) ) {
			foreach ( array( 'editor', 'text', 'description', 'tab_content', 'title_text', 'description_text' ) as $key ) {
				if ( ! empty( $node['settings'][ $key ] ) && is_string( $node['settings'][ $key ] ) ) {
					$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $node['settings'][ $key ] ) ) );
					if ( '' !== $text && ! preg_match( '/\?\s*$/', $text ) ) {
						$chunks[] = $text;
					}
				}
			}
		}

		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			foreach ( $node['elements'] as $child ) {
				if ( ! is_array( $child ) ) {
					continue;
				}

				$text = self::collect_text_from_node( $child );
				if ( '' !== $text ) {
					$chunks[] = $text;
				}
			}
		}

		$chunks = array_values( array_unique( array_filter( $chunks ) ) );

		return isset( $chunks[0] ) ? $chunks[0] : '';
	}

	/**
	 * Fallback FAQ extraction from rendered HTML.
	 *
	 * @param WP_Post $post Page object.
	 * @return array<int, array<string, string>>
	 */
	private static function extract_faqs_from_rendered_content( WP_Post $post ) {
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

			$answer = self::extract_answer_text_from_html( $panel_html );
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
	 * Pull answer text from rendered tab HTML.
	 *
	 * @param string $panel_html Tab panel HTML.
	 * @return string
	 */
	private static function extract_answer_text_from_html( $panel_html ) {
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

	/**
	 * Render page content via Elementor when available.
	 *
	 * @param WP_Post $post Page object.
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
}
/**
 * Dynamic Organization schema helpers.
 *
 * @package Vendavo_SEO_Fixes
 */

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
/**
 * Dynamic platform page schema output.
 *
 * @package Vendavo_SEO_Fixes
 */

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

add_action( 'plugins_loaded', function() {
	Vendavo_Dynamic_Schema_Buffer::init();
	Vendavo_SEO_Organization_Schema::init();
	Vendavo_SEO_Platform_Schema::init();
} );
