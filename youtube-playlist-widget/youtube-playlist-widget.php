<?php
/**
 * Plugin Name: YouTube Playlist Widget
 * Description: Reusable Beaver Builder widget/module for configurable YouTube playlist cards.
 * Version: 1.0.0
 * Author: Cursor
 * Text Domain: youtube-playlist-widget
 *
 * @package YouTubePlaylistWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YPW_PLUGIN_FILE', __FILE__ );
define( 'YPW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YPW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YPW_VERSION', '1.0.0' );

/**
 * Return the block attribute schema used by PHP and the editor script.
 *
 * @return array<string,array<string,mixed>>
 */
function ypw_get_block_attributes() {
	return array(
		'title'                => array(
			'type'    => 'string',
			'default' => 'YouTube Playlist',
		),
		'description'          => array(
			'type'    => 'string',
			'default' => 'Hier findest Du unsere YouTube-Playlist abcdfeghijklmn',
		),
		'playlistUrl'          => array(
			'type'    => 'string',
			'default' => '',
		),
		'playlistId'           => array(
			'type'    => 'string',
			'default' => '',
		),
		'thumbnailId'          => array(
			'type'    => 'number',
			'default' => 0,
		),
		'thumbnailUrl'         => array(
			'type'    => 'string',
			'default' => '',
		),
		'backgroundColor'      => array(
			'type'    => 'string',
			'default' => '#f8f3ec',
		),
		'contentColor'         => array(
			'type'    => 'string',
			'default' => '#ffffff',
		),
		'titleColor'           => array(
			'type'    => 'string',
			'default' => '#1b1b1b',
		),
		'textColor'            => array(
			'type'    => 'string',
			'default' => '#3d3d3d',
		),
		'accentColor'          => array(
			'type'    => 'string',
			'default' => '#ff0000',
		),
		'playButtonColor'      => array(
			'type'    => 'string',
			'default' => '#ffffff',
		),
		'titleFontFamily'      => array(
			'type'    => 'string',
			'default' => '"Baloo 2", "Arial Rounded MT Bold", Arial, sans-serif',
		),
		'bodyFontFamily'       => array(
			'type'    => 'string',
			'default' => 'Arial, Helvetica, sans-serif',
		),
		'titleFontSize'        => array(
			'type'    => 'string',
			'default' => 'clamp(2rem, 5vw, 4.5rem)',
		),
		'descriptionFontSize'  => array(
			'type'    => 'string',
			'default' => 'clamp(1rem, 2vw, 1.25rem)',
		),
		'buttonText'           => array(
			'type'    => 'string',
			'default' => 'Playlist ansehen',
		),
		'layout'               => array(
			'type'    => 'string',
			'default' => 'split',
		),
		'openInNewTab'         => array(
			'type'    => 'boolean',
			'default' => true,
		),
	);
}

/**
 * Get default attribute values.
 *
 * @return array<string,mixed>
 */
function ypw_get_default_attributes() {
	$defaults = array();

	foreach ( ypw_get_block_attributes() as $key => $schema ) {
		$defaults[ $key ] = isset( $schema['default'] ) ? $schema['default'] : '';
	}

	return $defaults;
}

/**
 * Register scripts, styles, block, and shortcode.
 */
function ypw_register_widget() {
	$css_path = YPW_PLUGIN_DIR . 'assets/css/youtube-playlist-widget.css';
	$js_path  = YPW_PLUGIN_DIR . 'assets/js/block-editor.js';

	wp_register_style(
		'ypw-widget',
		YPW_PLUGIN_URL . 'assets/css/youtube-playlist-widget.css',
		array(),
		file_exists( $css_path ) ? filemtime( $css_path ) : YPW_VERSION
	);

	wp_register_script(
		'ypw-block-editor',
		YPW_PLUGIN_URL . 'assets/js/block-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components' ),
		file_exists( $js_path ) ? filemtime( $js_path ) : YPW_VERSION,
		true
	);

	wp_localize_script(
		'ypw-block-editor',
		'ypwBlockDefaults',
		ypw_get_default_attributes()
	);

	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'ypw/youtube-playlist-widget',
			array(
				'api_version'     => 2,
				'attributes'      => ypw_get_block_attributes(),
				'editor_script'   => 'ypw-block-editor',
				'editor_style'    => 'ypw-widget',
				'style'           => 'ypw-widget',
				'render_callback' => 'ypw_render_widget',
			)
		);
	}

	add_shortcode( 'youtube_playlist_widget', 'ypw_render_shortcode' );
}
add_action( 'init', 'ypw_register_widget' );

/**
 * Register the Beaver Builder module when Beaver Builder is available.
 */
function ypw_register_beaver_builder_module() {
	if ( ! class_exists( 'FLBuilder' ) || ! class_exists( 'FLBuilderModule' ) ) {
		return;
	}

	require_once YPW_PLUGIN_DIR . 'beaver-builder/class-ypw-beaver-builder-module.php';
}
add_action( 'init', 'ypw_register_beaver_builder_module', 20 );

/**
 * Render the shortcode version.
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string
 */
function ypw_render_shortcode( $atts ) {
	wp_enqueue_style( 'ypw-widget' );

	$atts = shortcode_atts(
		array(
			'title'                 => '',
			'description'           => '',
			'playlist_url'          => '',
			'playlist_id'           => '',
			'thumbnail_id'          => 0,
			'thumbnail_url'         => '',
			'background_color'      => '',
			'content_color'         => '',
			'title_color'           => '',
			'text_color'            => '',
			'accent_color'          => '',
			'play_button_color'     => '',
			'title_font_family'     => '',
			'body_font_family'      => '',
			'title_font_size'       => '',
			'description_font_size' => '',
			'button_text'           => '',
			'layout'                => '',
			'open_in_new_tab'       => true,
		),
		$atts,
		'youtube_playlist_widget'
	);

	return ypw_render_widget(
		array(
			'title'               => $atts['title'],
			'description'         => $atts['description'],
			'playlistUrl'         => $atts['playlist_url'],
			'playlistId'          => $atts['playlist_id'],
			'thumbnailId'         => absint( $atts['thumbnail_id'] ),
			'thumbnailUrl'        => $atts['thumbnail_url'],
			'backgroundColor'     => $atts['background_color'],
			'contentColor'        => $atts['content_color'],
			'titleColor'          => $atts['title_color'],
			'textColor'           => $atts['text_color'],
			'accentColor'         => $atts['accent_color'],
			'playButtonColor'     => $atts['play_button_color'],
			'titleFontFamily'     => $atts['title_font_family'],
			'bodyFontFamily'      => $atts['body_font_family'],
			'titleFontSize'       => $atts['title_font_size'],
			'descriptionFontSize' => $atts['description_font_size'],
			'buttonText'          => $atts['button_text'],
			'layout'              => $atts['layout'],
			'openInNewTab'        => filter_var( $atts['open_in_new_tab'], FILTER_VALIDATE_BOOLEAN ),
		)
	);
}

/**
 * Map Beaver Builder module settings to renderer attributes.
 *
 * @param object $settings Beaver Builder settings object.
 * @return array<string,mixed>
 */
function ypw_map_beaver_builder_settings( $settings ) {
	$settings = (object) $settings;

	return array(
		'title'               => ypw_get_object_value( $settings, 'title' ),
		'description'         => ypw_get_object_value( $settings, 'description' ),
		'playlistUrl'         => ypw_get_object_value( $settings, 'playlist_url' ),
		'playlistId'          => ypw_get_object_value( $settings, 'playlist_id' ),
		'thumbnailId'         => absint( ypw_get_object_value( $settings, 'thumbnail' ) ),
		'thumbnailUrl'        => ypw_get_object_value( $settings, 'external_thumbnail_url', ypw_get_object_value( $settings, 'thumbnail_src' ) ),
		'backgroundColor'     => ypw_format_beaver_builder_color( ypw_get_object_value( $settings, 'background_color' ) ),
		'contentColor'        => ypw_format_beaver_builder_color( ypw_get_object_value( $settings, 'content_color' ) ),
		'titleColor'          => ypw_format_beaver_builder_color( ypw_get_object_value( $settings, 'title_color' ) ),
		'textColor'           => ypw_format_beaver_builder_color( ypw_get_object_value( $settings, 'text_color' ) ),
		'accentColor'         => ypw_format_beaver_builder_color( ypw_get_object_value( $settings, 'accent_color' ) ),
		'playButtonColor'     => ypw_format_beaver_builder_color( ypw_get_object_value( $settings, 'play_button_color' ) ),
		'titleFontFamily'     => ypw_resolve_font_family(
			ypw_get_object_value( $settings, 'title_font_preset' ),
			ypw_get_object_value( $settings, 'title_font_family' )
		),
		'bodyFontFamily'      => ypw_resolve_font_family(
			ypw_get_object_value( $settings, 'body_font_preset' ),
			ypw_get_object_value( $settings, 'body_font_family' )
		),
		'titleFontSize'       => ypw_get_object_value( $settings, 'title_font_size' ),
		'descriptionFontSize' => ypw_get_object_value( $settings, 'description_font_size' ),
		'buttonText'          => ypw_get_object_value( $settings, 'button_text' ),
		'layout'              => ypw_get_object_value( $settings, 'layout' ),
		'openInNewTab'        => 'yes' === ypw_get_object_value( $settings, 'open_in_new_tab', 'yes' ),
	);
}

/**
 * Render the YouTube playlist widget.
 *
 * @param array<string,mixed> $attributes Block attributes.
 * @return string
 */
function ypw_render_widget( $attributes ) {
	$attributes   = ypw_normalize_attributes( $attributes );
	$playlist_id  = ypw_get_playlist_id( $attributes['playlistUrl'], $attributes['playlistId'] );
	$playlist_url = ypw_get_playlist_url( $playlist_id, $attributes['playlistUrl'] );
	$thumbnail    = ypw_get_thumbnail_url( $attributes['thumbnailId'], $attributes['thumbnailUrl'] );
	$mask_id      = wp_unique_id( 'ypw-play-mask-' );
	$target       = $attributes['openInNewTab'] ? ' target="_blank" rel="noopener noreferrer"' : '';
	$style        = ypw_build_inline_style( $attributes );

	ob_start();
	?>
	<section class="ypw-widget ypw-layout-<?php echo esc_attr( $attributes['layout'] ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $attributes['title'] ); ?>">
		<a class="ypw-media" href="<?php echo esc_url( $playlist_url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $thumbnail ) : ?>
				<img class="ypw-thumbnail" src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $attributes['title'] ); ?>" loading="lazy" />
			<?php else : ?>
				<span class="ypw-thumbnail ypw-thumbnail-placeholder" aria-hidden="true"></span>
			<?php endif; ?>
			<span class="ypw-play-button" aria-hidden="true">
				<svg viewBox="0 0 96 96" focusable="false" role="img" aria-hidden="true">
					<defs>
						<mask id="<?php echo esc_attr( $mask_id ); ?>">
							<rect width="96" height="96" fill="white" />
							<path d="M40 31 L67 48 L40 65 Z" fill="black" />
						</mask>
					</defs>
					<circle cx="48" cy="48" r="43" fill="currentColor" mask="url(#<?php echo esc_attr( $mask_id ); ?>)" />
				</svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Open YouTube playlist', 'youtube-playlist-widget' ); ?></span>
			</span>
		</a>

		<div class="ypw-content">
			<?php if ( $attributes['title'] ) : ?>
				<h2 class="ypw-title"><?php echo esc_html( $attributes['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $attributes['description'] ) : ?>
				<p class="ypw-description"><?php echo wp_kses_post( nl2br( $attributes['description'] ) ); ?></p>
			<?php endif; ?>

			<?php if ( $playlist_url ) : ?>
				<a class="ypw-cta" href="<?php echo esc_url( $playlist_url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo esc_html( $attributes['buttonText'] ); ?>
				</a>
			<?php endif; ?>
		</div>
	</section>
	<?php

	return trim( ob_get_clean() );
}

/**
 * Merge, sanitize, and normalize attributes.
 *
 * @param array<string,mixed> $attributes Raw attributes.
 * @return array<string,mixed>
 */
function ypw_normalize_attributes( $attributes ) {
	$attributes = wp_parse_args( (array) $attributes, ypw_get_default_attributes() );

	$attributes['title']               = sanitize_text_field( $attributes['title'] );
	$attributes['description']         = sanitize_textarea_field( $attributes['description'] );
	$attributes['playlistUrl']         = esc_url_raw( $attributes['playlistUrl'] );
	$attributes['playlistId']          = ypw_sanitize_playlist_id( $attributes['playlistId'] );
	$attributes['thumbnailId']         = absint( $attributes['thumbnailId'] );
	$attributes['thumbnailUrl']        = esc_url_raw( $attributes['thumbnailUrl'] );
	$attributes['backgroundColor']     = ypw_sanitize_css_value( $attributes['backgroundColor'], '#f8f3ec' );
	$attributes['contentColor']        = ypw_sanitize_css_value( $attributes['contentColor'], '#ffffff' );
	$attributes['titleColor']          = ypw_sanitize_css_value( $attributes['titleColor'], '#1b1b1b' );
	$attributes['textColor']           = ypw_sanitize_css_value( $attributes['textColor'], '#3d3d3d' );
	$attributes['accentColor']         = ypw_sanitize_css_value( $attributes['accentColor'], '#ff0000' );
	$attributes['playButtonColor']     = ypw_sanitize_css_value( $attributes['playButtonColor'], '#ffffff' );
	$attributes['titleFontFamily']     = ypw_sanitize_font_family( $attributes['titleFontFamily'], '"Baloo 2", "Arial Rounded MT Bold", Arial, sans-serif' );
	$attributes['bodyFontFamily']      = ypw_sanitize_font_family( $attributes['bodyFontFamily'], 'Arial, Helvetica, sans-serif' );
	$attributes['titleFontSize']       = ypw_sanitize_css_value( $attributes['titleFontSize'], 'clamp(2rem, 5vw, 4.5rem)' );
	$attributes['descriptionFontSize'] = ypw_sanitize_css_value( $attributes['descriptionFontSize'], 'clamp(1rem, 2vw, 1.25rem)' );
	$attributes['buttonText']          = sanitize_text_field( $attributes['buttonText'] );
	$attributes['layout']              = in_array( $attributes['layout'], array( 'split', 'stacked' ), true ) ? $attributes['layout'] : 'split';
	$attributes['openInNewTab']        = (bool) $attributes['openInNewTab'];

	return $attributes;
}

/**
 * Build inline CSS custom properties.
 *
 * @param array<string,mixed> $attributes Sanitized attributes.
 * @return string
 */
function ypw_build_inline_style( $attributes ) {
	$variables = array(
		'--ypw-background'            => $attributes['backgroundColor'],
		'--ypw-content-background'    => $attributes['contentColor'],
		'--ypw-title-color'           => $attributes['titleColor'],
		'--ypw-text-color'            => $attributes['textColor'],
		'--ypw-accent-color'          => $attributes['accentColor'],
		'--ypw-play-button-color'     => $attributes['playButtonColor'],
		'--ypw-title-font-family'     => $attributes['titleFontFamily'],
		'--ypw-body-font-family'      => $attributes['bodyFontFamily'],
		'--ypw-title-font-size'       => $attributes['titleFontSize'],
		'--ypw-description-font-size' => $attributes['descriptionFontSize'],
	);

	$style = '';

	foreach ( $variables as $property => $value ) {
		$style .= sprintf( '%s:%s;', $property, $value );
	}

	return $style;
}

/**
 * Sanitize YouTube playlist IDs.
 *
 * @param mixed $playlist_id Raw playlist ID.
 * @return string
 */
function ypw_sanitize_playlist_id( $playlist_id ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $playlist_id );
}

/**
 * Extract the playlist ID from an ID field or common YouTube URL shapes.
 *
 * @param string $playlist_url YouTube URL.
 * @param string $playlist_id  Explicit playlist ID.
 * @return string
 */
function ypw_get_playlist_id( $playlist_url, $playlist_id ) {
	$playlist_id = ypw_sanitize_playlist_id( $playlist_id );

	if ( $playlist_id ) {
		return $playlist_id;
	}

	if ( ! $playlist_url ) {
		return '';
	}

	$query = wp_parse_url( $playlist_url, PHP_URL_QUERY );
	if ( $query ) {
		parse_str( $query, $params );
		if ( ! empty( $params['list'] ) ) {
			return ypw_sanitize_playlist_id( $params['list'] );
		}
	}

	$path = wp_parse_url( $playlist_url, PHP_URL_PATH );
	if ( $path && preg_match( '#/playlist/([A-Za-z0-9_-]+)#', $path, $matches ) ) {
		return ypw_sanitize_playlist_id( $matches[1] );
	}

	return '';
}

/**
 * Get the final playlist URL.
 *
 * @param string $playlist_id  Playlist ID.
 * @param string $playlist_url Fallback URL.
 * @return string
 */
function ypw_get_playlist_url( $playlist_id, $playlist_url ) {
	if ( $playlist_id ) {
		return sprintf( 'https://www.youtube.com/playlist?list=%s', rawurlencode( $playlist_id ) );
	}

	return $playlist_url ? $playlist_url : 'https://www.youtube.com/';
}

/**
 * Get configured thumbnail URL.
 *
 * @param int    $thumbnail_id  Attachment ID.
 * @param string $thumbnail_url Manual image URL.
 * @return string
 */
function ypw_get_thumbnail_url( $thumbnail_id, $thumbnail_url ) {
	if ( $thumbnail_id ) {
		$image = wp_get_attachment_image_url( $thumbnail_id, 'large' );

		if ( $image ) {
			return $image;
		}
	}

	return $thumbnail_url;
}

/**
 * Safely read a property from a settings object.
 *
 * @param object $object   Settings object.
 * @param string $property Property name.
 * @param mixed  $default  Default value.
 * @return mixed
 */
function ypw_get_object_value( $object, $property, $default = '' ) {
	return isset( $object->{$property} ) ? $object->{$property} : $default;
}

/**
 * Convert Beaver Builder color values into CSS color values.
 *
 * Beaver color fields commonly save hex colors without the leading #.
 *
 * @param mixed $value Raw Beaver Builder color value.
 * @return string
 */
function ypw_format_beaver_builder_color( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$/', $value ) ) {
		return '#' . $value;
	}

	return $value;
}

/**
 * Resolve editor font preset and custom value into a font-family stack.
 *
 * @param string $preset Font preset.
 * @param string $custom Custom font family stack.
 * @return string
 */
function ypw_resolve_font_family( $preset, $custom ) {
	if ( 'custom' === $preset && $custom ) {
		return $custom;
	}

	$fonts = array(
		'baloo'  => '"Baloo 2", "Arial Rounded MT Bold", Arial, sans-serif',
		'shadow' => '"Shadows Into Light", "Comic Sans MS", cursive',
		'sans'   => 'Arial, Helvetica, sans-serif',
		'serif'  => 'Georgia, "Times New Roman", serif',
	);

	return isset( $fonts[ $preset ] ) ? $fonts[ $preset ] : $custom;
}

/**
 * Sanitize CSS values that are stored in custom properties.
 *
 * @param mixed  $value    Raw CSS value.
 * @param string $fallback Fallback CSS value.
 * @return string
 */
function ypw_sanitize_css_value( $value, $fallback ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return $fallback;
	}

	// Keep custom CSS values useful while preventing declaration breaks.
	if ( preg_match( '/[;{}<>]/', $value ) ) {
		return $fallback;
	}

	return $value;
}

/**
 * Sanitize font-family lists.
 *
 * @param mixed  $value    Raw font family.
 * @param string $fallback Fallback font family.
 * @return string
 */
function ypw_sanitize_font_family( $value, $fallback ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return $fallback;
	}

	if ( preg_match( '/[^A-Za-z0-9\s,\-_"\'.]/', $value ) ) {
		return $fallback;
	}

	return $value;
}
