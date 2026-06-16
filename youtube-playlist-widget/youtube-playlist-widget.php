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
 * Add this plugin's Beaver Builder modules directory to Beaver's scanner.
 *
 * Some Beaver Builder installs build the module list before a late direct
 * registration runs. Supplying a module path lets Beaver load the module in
 * the same way it loads built-in and third-party module folders.
 *
 * @param array<int,string> $paths Existing Beaver Builder module paths.
 * @return array<int,string>
 */
function ypw_add_beaver_builder_module_path( $paths ) {
	$module_path = YPW_PLUGIN_DIR . 'beaver-builder/modules/';

	if ( is_dir( $module_path ) && ! in_array( $module_path, $paths, true ) ) {
		$paths[] = $module_path;
	}

	return $paths;
}
add_filter( 'fl_builder_load_modules_paths', 'ypw_add_beaver_builder_module_path' );

/**
 * Return the block attribute schema used by PHP and the editor script.
 *
 * @return array<string,array<string,mixed>>
 */
function ypw_get_block_attributes() {
	return array(
		'title'                => array(
			'type'    => 'string',
			'default' => 'Vlogs aus dem Austausch',
		),
		'description'          => array(
			'type'    => 'string',
			'default' => "Video Description\ndescription description\ndescription\ndescription description\ndescription",
		),
		'contentTitle'         => array(
			'type'    => 'string',
			'default' => 'Hier ist eine Überschrift',
		),
		'contentText'          => array(
			'type'    => 'string',
			'default' => 'Hast Du schon mal vom "American Dream" gehört? Er besagt, dass jede*r in den Vereinigten Staaten durch seine Fähigkeiten und Leistungen das individuelle Glück finden kann. Begib Dich mit uns auf die Reise Deines Lebens und erlebe Deinen ganz eigenen amerikanischen Traum in Deinem Schüleraustausch USA.',
		),
		'videoOneDate'         => array(
			'type'    => 'string',
			'default' => '3. März 2025',
		),
		'videoOneTitle'        => array(
			'type'    => 'string',
			'default' => 'Schulalltag in Schweden | Experiment Vlog',
		),
		'videoTwoDate'         => array(
			'type'    => 'string',
			'default' => '18. Feb. 2025',
		),
		'videoTwoTitle'        => array(
			'type'    => 'string',
			'default' => 'Ein Wochenende in Stockholm | Experiment Vlog',
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
			'default' => '#ff7f66',
		),
		'contentColor'         => array(
			'type'    => 'string',
			'default' => '#ffffff',
		),
		'titleColor'           => array(
			'type'    => 'string',
			'default' => '#ffffff',
		),
		'textColor'            => array(
			'type'    => 'string',
			'default' => '#ffffff',
		),
		'accentColor'          => array(
			'type'    => 'string',
			'default' => '#ff6f61',
		),
		'playButtonColor'      => array(
			'type'    => 'string',
			'default' => '#ffffff',
		),
		'titleFontFamily'      => array(
			'type'    => 'string',
			'default' => '"Shadows Into Light", "Comic Sans MS", cursive',
		),
		'bodyFontFamily'       => array(
			'type'    => 'string',
			'default' => 'Arial, Helvetica, sans-serif',
		),
		'titleFontSize'        => array(
			'type'    => 'string',
			'default' => 'clamp(1.85rem, 3vw, 2.45rem)',
		),
		'descriptionFontSize'  => array(
			'type'    => 'string',
			'default' => 'clamp(1rem, 2vw, 1.25rem)',
		),
		'buttonText'           => array(
			'type'    => 'string',
			'default' => 'Alle Videos',
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

	require_once YPW_PLUGIN_DIR . 'beaver-builder/modules/youtube-playlist-widget/youtube-playlist-widget.php';
}
add_action( 'init', 'ypw_register_beaver_builder_module', 20 );
add_action( 'fl_builder_init_ui', 'ypw_register_beaver_builder_module', 1 );

/**
 * Show a helpful admin notice if the plugin is active without Beaver Builder.
 */
function ypw_show_beaver_builder_missing_notice() {
	if ( class_exists( 'FLBuilder' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	?>
	<div class="notice notice-warning">
		<p>
			<?php esc_html_e( 'YouTube Playlist Widget is active, but Beaver Builder is not active. Activate Beaver Builder to see the widget in the Beaver Builder module panel.', 'youtube-playlist-widget' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'ypw_show_beaver_builder_missing_notice' );

/**
 * Register a classic WordPress widget.
 *
 * Beaver Builder exposes registered WordPress widgets through its WordPress
 * Widgets module group on many installs. This provides a second fully
 * configurable editor path when custom Beaver modules are disabled or hidden.
 */
function ypw_register_classic_wordpress_widget() {
	if ( class_exists( 'YPW_WordPress_Widget' ) ) {
		register_widget( 'YPW_WordPress_Widget' );
	}
}
add_action( 'widgets_init', 'ypw_register_classic_wordpress_widget' );

if ( ! class_exists( 'WP_Widget' ) && defined( 'ABSPATH' ) && defined( 'WPINC' ) && file_exists( ABSPATH . WPINC . '/class-wp-widget.php' ) ) {
	require_once ABSPATH . WPINC . '/class-wp-widget.php';
}

if ( class_exists( 'WP_Widget' ) && ! class_exists( 'YPW_WordPress_Widget' ) ) {
	/**
	 * Fully configurable WordPress widget for Beaver Builder's widget bridge.
	 */
	class YPW_WordPress_Widget extends WP_Widget {
		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct(
				'ypw_wordpress_widget',
				__( 'YouTube Playlist Widget', 'youtube-playlist-widget' ),
				array(
					'classname'                   => 'ypw-wordpress-widget',
					'description'                 => __( 'Configurable YouTube playlist card with thumbnail, colors, and fonts.', 'youtube-playlist-widget' ),
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * Render widget output.
		 *
		 * @param array<string,mixed> $args     Widget wrapper args.
		 * @param array<string,mixed> $instance Saved widget instance.
		 */
		public function widget( $args, $instance ) {
			wp_enqueue_style( 'ypw-widget' );

			echo isset( $args['before_widget'] ) ? $args['before_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ypw_render_widget( ypw_map_wordpress_widget_instance( $instance ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo isset( $args['after_widget'] ) ? $args['after_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Render widget admin form.
		 *
		 * @param array<string,mixed> $instance Saved widget instance.
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, ypw_get_wordpress_widget_defaults() );

			$this->render_text_field( 'title', __( 'Title', 'youtube-playlist-widget' ), $instance['title'] );
			$this->render_textarea_field( 'description', __( 'Description/Text', 'youtube-playlist-widget' ), $instance['description'] );
			$this->render_text_field( 'contentTitle', __( 'Right Heading', 'youtube-playlist-widget' ), $instance['contentTitle'] );
			$this->render_textarea_field( 'contentText', __( 'Right Text', 'youtube-playlist-widget' ), $instance['contentText'] );
			$this->render_text_field( 'videoOneDate', __( 'Video 1 Date', 'youtube-playlist-widget' ), $instance['videoOneDate'] );
			$this->render_text_field( 'videoOneTitle', __( 'Video 1 Title', 'youtube-playlist-widget' ), $instance['videoOneTitle'] );
			$this->render_text_field( 'videoTwoDate', __( 'Video 2 Date', 'youtube-playlist-widget' ), $instance['videoTwoDate'] );
			$this->render_text_field( 'videoTwoTitle', __( 'Video 2 Title', 'youtube-playlist-widget' ), $instance['videoTwoTitle'] );
			$this->render_text_field( 'playlistUrl', __( 'YouTube Playlist URL', 'youtube-playlist-widget' ), $instance['playlistUrl'] );
			$this->render_text_field( 'playlistId', __( 'YouTube Playlist ID', 'youtube-playlist-widget' ), $instance['playlistId'] );
			$this->render_text_field( 'thumbnailUrl', __( 'Thumbnail/Image URL', 'youtube-playlist-widget' ), $instance['thumbnailUrl'] );
			$this->render_text_field( 'buttonText', __( 'Button Text', 'youtube-playlist-widget' ), $instance['buttonText'] );
			$this->render_select_field(
				'layout',
				__( 'Layout', 'youtube-playlist-widget' ),
				$instance['layout'],
				array(
					'split'   => __( 'Version 6 split layout', 'youtube-playlist-widget' ),
					'stacked' => __( 'Stacked', 'youtube-playlist-widget' ),
				)
			);

			echo '<hr />';
			echo '<p><strong>' . esc_html__( 'Colors', 'youtube-playlist-widget' ) . '</strong></p>';
			$this->render_color_field( 'backgroundColor', __( 'Outer Background', 'youtube-playlist-widget' ), $instance['backgroundColor'] );
			$this->render_color_field( 'contentColor', __( 'Content Background', 'youtube-playlist-widget' ), $instance['contentColor'] );
			$this->render_color_field( 'titleColor', __( 'Title Color', 'youtube-playlist-widget' ), $instance['titleColor'] );
			$this->render_color_field( 'textColor', __( 'Description Color', 'youtube-playlist-widget' ), $instance['textColor'] );
			$this->render_color_field( 'accentColor', __( 'CTA/Accent Color', 'youtube-playlist-widget' ), $instance['accentColor'] );
			$this->render_color_field( 'playButtonColor', __( 'Play Circle Color', 'youtube-playlist-widget' ), $instance['playButtonColor'] );

			echo '<hr />';
			echo '<p><strong>' . esc_html__( 'Fonts', 'youtube-playlist-widget' ) . '</strong></p>';
			$this->render_select_field( 'titleFontPreset', __( 'Title Font Preset', 'youtube-playlist-widget' ), $instance['titleFontPreset'], ypw_get_font_preset_options() );
			$this->render_text_field( 'titleFontFamily', __( 'Custom Title Font Family', 'youtube-playlist-widget' ), $instance['titleFontFamily'] );
			$this->render_text_field( 'titleFontSize', __( 'Title Font Size', 'youtube-playlist-widget' ), $instance['titleFontSize'] );
			$this->render_select_field( 'bodyFontPreset', __( 'Body Font Preset', 'youtube-playlist-widget' ), $instance['bodyFontPreset'], ypw_get_font_preset_options() );
			$this->render_text_field( 'bodyFontFamily', __( 'Custom Body Font Family', 'youtube-playlist-widget' ), $instance['bodyFontFamily'] );
			$this->render_text_field( 'descriptionFontSize', __( 'Description Font Size', 'youtube-playlist-widget' ), $instance['descriptionFontSize'] );
			$this->render_checkbox_field( 'openInNewTab', __( 'Open playlist in a new tab', 'youtube-playlist-widget' ), ! empty( $instance['openInNewTab'] ) );
		}

		/**
		 * Sanitize widget settings.
		 *
		 * @param array<string,mixed> $new_instance New widget instance.
		 * @param array<string,mixed> $old_instance Previous widget instance.
		 * @return array<string,mixed>
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = ypw_get_wordpress_widget_defaults();

			$instance['title']               = sanitize_text_field( ypw_get_array_value( $new_instance, 'title' ) );
			$instance['description']         = sanitize_textarea_field( ypw_get_array_value( $new_instance, 'description' ) );
			$instance['contentTitle']        = sanitize_text_field( ypw_get_array_value( $new_instance, 'contentTitle' ) );
			$instance['contentText']         = sanitize_textarea_field( ypw_get_array_value( $new_instance, 'contentText' ) );
			$instance['videoOneDate']        = sanitize_text_field( ypw_get_array_value( $new_instance, 'videoOneDate' ) );
			$instance['videoOneTitle']       = sanitize_text_field( ypw_get_array_value( $new_instance, 'videoOneTitle' ) );
			$instance['videoTwoDate']        = sanitize_text_field( ypw_get_array_value( $new_instance, 'videoTwoDate' ) );
			$instance['videoTwoTitle']       = sanitize_text_field( ypw_get_array_value( $new_instance, 'videoTwoTitle' ) );
			$instance['playlistUrl']         = esc_url_raw( ypw_get_array_value( $new_instance, 'playlistUrl' ) );
			$instance['playlistId']          = ypw_sanitize_playlist_id( ypw_get_array_value( $new_instance, 'playlistId' ) );
			$instance['thumbnailUrl']        = esc_url_raw( ypw_get_array_value( $new_instance, 'thumbnailUrl' ) );
			$instance['buttonText']          = sanitize_text_field( ypw_get_array_value( $new_instance, 'buttonText' ) );
			$instance['layout']              = in_array( ypw_get_array_value( $new_instance, 'layout' ), array( 'split', 'stacked' ), true ) ? ypw_get_array_value( $new_instance, 'layout' ) : 'split';
			$instance['backgroundColor']     = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'backgroundColor' ), '#ff7f66' );
			$instance['contentColor']        = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'contentColor' ), '#ffffff' );
			$instance['titleColor']          = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'titleColor' ), '#ffffff' );
			$instance['textColor']           = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'textColor' ), '#ffffff' );
			$instance['accentColor']         = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'accentColor' ), '#ff6f61' );
			$instance['playButtonColor']     = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'playButtonColor' ), '#ffffff' );
			$instance['titleFontPreset']     = ypw_sanitize_font_preset( ypw_get_array_value( $new_instance, 'titleFontPreset' ), 'shadow' );
			$instance['titleFontFamily']     = ypw_sanitize_font_family( ypw_get_array_value( $new_instance, 'titleFontFamily' ), '"Shadows Into Light", "Comic Sans MS", cursive' );
			$instance['titleFontSize']       = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'titleFontSize' ), 'clamp(1.85rem, 3vw, 2.45rem)' );
			$instance['bodyFontPreset']      = ypw_sanitize_font_preset( ypw_get_array_value( $new_instance, 'bodyFontPreset' ), 'sans' );
			$instance['bodyFontFamily']      = ypw_sanitize_font_family( ypw_get_array_value( $new_instance, 'bodyFontFamily' ), 'Arial, Helvetica, sans-serif' );
			$instance['descriptionFontSize'] = ypw_sanitize_css_value( ypw_get_array_value( $new_instance, 'descriptionFontSize' ), 'clamp(1rem, 2vw, 1.25rem)' );
			$instance['openInNewTab']        = ! empty( $new_instance['openInNewTab'] );

			return $instance;
		}

		/**
		 * Render a text input.
		 *
		 * @param string $key   Instance key.
		 * @param string $label Field label.
		 * @param string $value Field value.
		 */
		private function render_text_field( $key, $label, $value ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" />
			</p>
			<?php
		}

		/**
		 * Render a textarea input.
		 *
		 * @param string $key   Instance key.
		 * @param string $label Field label.
		 * @param string $value Field value.
		 */
		private function render_textarea_field( $key, $label, $value ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
				<textarea class="widefat" rows="4" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
			</p>
			<?php
		}

		/**
		 * Render a color input.
		 *
		 * @param string $key   Instance key.
		 * @param string $label Field label.
		 * @param string $value Field value.
		 */
		private function render_color_field( $key, $label, $value ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="color" value="<?php echo esc_attr( $value ); ?>" />
			</p>
			<?php
		}

		/**
		 * Render a select input.
		 *
		 * @param string               $key     Instance key.
		 * @param string               $label   Field label.
		 * @param string               $value   Selected value.
		 * @param array<string,string> $options Select options.
		 */
		private function render_select_field( $key, $label, $value, $options ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>">
					<?php foreach ( $options as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php
		}

		/**
		 * Render a checkbox input.
		 *
		 * @param string $key     Instance key.
		 * @param string $label   Field label.
		 * @param bool   $checked Checked state.
		 */
		private function render_checkbox_field( $key, $label, $checked ) {
			?>
			<p>
				<input id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="checkbox" value="1" <?php checked( $checked ); ?> />
				<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $label ); ?></label>
			</p>
			<?php
		}
	}
}

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
			'content_title'         => '',
			'content_text'          => '',
			'video_one_date'        => '',
			'video_one_title'       => '',
			'video_two_date'        => '',
			'video_two_title'       => '',
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
			'contentTitle'        => $atts['content_title'],
			'contentText'         => $atts['content_text'],
			'videoOneDate'        => $atts['video_one_date'],
			'videoOneTitle'       => $atts['video_one_title'],
			'videoTwoDate'        => $atts['video_two_date'],
			'videoTwoTitle'       => $atts['video_two_title'],
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
		'contentTitle'        => ypw_get_object_value( $settings, 'content_title' ),
		'contentText'         => ypw_get_object_value( $settings, 'content_text' ),
		'videoOneDate'        => ypw_get_object_value( $settings, 'video_one_date' ),
		'videoOneTitle'       => ypw_get_object_value( $settings, 'video_one_title' ),
		'videoTwoDate'        => ypw_get_object_value( $settings, 'video_two_date' ),
		'videoTwoTitle'       => ypw_get_object_value( $settings, 'video_two_title' ),
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
 * Get defaults for the classic WordPress widget.
 *
 * @return array<string,mixed>
 */
function ypw_get_wordpress_widget_defaults() {
	return array_merge(
		ypw_get_default_attributes(),
		array(
			'titleFontPreset' => 'shadow',
			'bodyFontPreset'  => 'sans',
		)
	);
}

/**
 * Map WordPress widget instance settings to renderer attributes.
 *
 * @param array<string,mixed> $instance Widget instance.
 * @return array<string,mixed>
 */
function ypw_map_wordpress_widget_instance( $instance ) {
	$instance = wp_parse_args( (array) $instance, ypw_get_wordpress_widget_defaults() );

	return array(
		'title'               => ypw_get_array_value( $instance, 'title' ),
		'description'         => ypw_get_array_value( $instance, 'description' ),
		'contentTitle'        => ypw_get_array_value( $instance, 'contentTitle' ),
		'contentText'         => ypw_get_array_value( $instance, 'contentText' ),
		'videoOneDate'        => ypw_get_array_value( $instance, 'videoOneDate' ),
		'videoOneTitle'       => ypw_get_array_value( $instance, 'videoOneTitle' ),
		'videoTwoDate'        => ypw_get_array_value( $instance, 'videoTwoDate' ),
		'videoTwoTitle'       => ypw_get_array_value( $instance, 'videoTwoTitle' ),
		'playlistUrl'         => ypw_get_array_value( $instance, 'playlistUrl' ),
		'playlistId'          => ypw_get_array_value( $instance, 'playlistId' ),
		'thumbnailId'         => 0,
		'thumbnailUrl'        => ypw_get_array_value( $instance, 'thumbnailUrl' ),
		'backgroundColor'     => ypw_get_array_value( $instance, 'backgroundColor' ),
		'contentColor'        => ypw_get_array_value( $instance, 'contentColor' ),
		'titleColor'          => ypw_get_array_value( $instance, 'titleColor' ),
		'textColor'           => ypw_get_array_value( $instance, 'textColor' ),
		'accentColor'         => ypw_get_array_value( $instance, 'accentColor' ),
		'playButtonColor'     => ypw_get_array_value( $instance, 'playButtonColor' ),
		'titleFontFamily'     => ypw_resolve_font_family(
			ypw_get_array_value( $instance, 'titleFontPreset', 'shadow' ),
			ypw_get_array_value( $instance, 'titleFontFamily' )
		),
		'bodyFontFamily'      => ypw_resolve_font_family(
			ypw_get_array_value( $instance, 'bodyFontPreset', 'sans' ),
			ypw_get_array_value( $instance, 'bodyFontFamily' )
		),
		'titleFontSize'       => ypw_get_array_value( $instance, 'titleFontSize' ),
		'descriptionFontSize' => ypw_get_array_value( $instance, 'descriptionFontSize' ),
		'buttonText'          => ypw_get_array_value( $instance, 'buttonText' ),
		'layout'              => ypw_get_array_value( $instance, 'layout' ),
		'openInNewTab'        => ! empty( $instance['openInNewTab'] ),
	);
}

/**
 * Get font preset labels.
 *
 * @return array<string,string>
 */
function ypw_get_font_preset_options() {
	return array(
		'baloo'  => __( 'Baloo', 'youtube-playlist-widget' ),
		'shadow' => __( 'Shadow', 'youtube-playlist-widget' ),
		'sans'   => __( 'System Sans', 'youtube-playlist-widget' ),
		'serif'  => __( 'Serif', 'youtube-playlist-widget' ),
		'custom' => __( 'Custom font-family value', 'youtube-playlist-widget' ),
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
	$target       = $attributes['openInNewTab'] ? ' target="_blank" rel="noopener noreferrer"' : '';
	$style        = ypw_build_inline_style( $attributes );
	$videos       = array(
		array(
			'date'  => $attributes['videoOneDate'],
			'title' => $attributes['videoOneTitle'],
		),
		array(
			'date'  => $attributes['videoTwoDate'],
			'title' => $attributes['videoTwoTitle'],
		),
	);

	ob_start();
	?>
	<section class="ypw-widget ypw-layout-<?php echo esc_attr( $attributes['layout'] ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $attributes['title'] ); ?>">
		<div class="ypw-card">
			<?php if ( $attributes['title'] ) : ?>
				<h2 class="ypw-card-title"><?php echo esc_html( $attributes['title'] ); ?></h2>
			<?php endif; ?>

			<div class="ypw-card-intro">
				<?php if ( $attributes['description'] ) : ?>
					<p class="ypw-description"><?php echo wp_kses_post( nl2br( $attributes['description'] ) ); ?></p>
				<?php endif; ?>

				<a class="ypw-thumbnail-link" href="<?php echo esc_url( $playlist_url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php if ( $thumbnail ) : ?>
						<img class="ypw-thumbnail" src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $attributes['title'] ); ?>" loading="lazy" />
					<?php else : ?>
						<span class="ypw-thumbnail ypw-thumbnail-placeholder" aria-hidden="true"></span>
					<?php endif; ?>
				</a>
			</div>

			<div class="ypw-video-list">
				<?php foreach ( $videos as $video ) : ?>
					<?php if ( $video['date'] || $video['title'] ) : ?>
						<a class="ypw-video-row" href="<?php echo esc_url( $playlist_url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
							<span class="ypw-video-copy">
								<?php if ( $video['date'] ) : ?>
									<span class="ypw-video-date"><?php echo esc_html( $video['date'] ); ?></span>
								<?php endif; ?>
								<?php if ( $video['title'] ) : ?>
									<span class="ypw-video-title"><?php echo esc_html( $video['title'] ); ?></span>
								<?php endif; ?>
							</span>
							<span class="ypw-row-play" aria-hidden="true"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Open YouTube playlist', 'youtube-playlist-widget' ); ?></span>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php if ( $playlist_url && $attributes['buttonText'] ) : ?>
				<a class="ypw-cta" href="<?php echo esc_url( $playlist_url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<?php echo esc_html( $attributes['buttonText'] ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="ypw-content">
			<?php if ( $attributes['contentTitle'] ) : ?>
				<h3 class="ypw-content-title"><?php echo esc_html( $attributes['contentTitle'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $attributes['contentText'] ) : ?>
				<p class="ypw-content-text"><?php echo wp_kses_post( nl2br( $attributes['contentText'] ) ); ?></p>
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
	$attributes['contentTitle']        = sanitize_text_field( $attributes['contentTitle'] );
	$attributes['contentText']         = sanitize_textarea_field( $attributes['contentText'] );
	$attributes['videoOneDate']        = sanitize_text_field( $attributes['videoOneDate'] );
	$attributes['videoOneTitle']       = sanitize_text_field( $attributes['videoOneTitle'] );
	$attributes['videoTwoDate']        = sanitize_text_field( $attributes['videoTwoDate'] );
	$attributes['videoTwoTitle']       = sanitize_text_field( $attributes['videoTwoTitle'] );
	$attributes['playlistUrl']         = esc_url_raw( $attributes['playlistUrl'] );
	$attributes['playlistId']          = ypw_sanitize_playlist_id( $attributes['playlistId'] );
	$attributes['thumbnailId']         = absint( $attributes['thumbnailId'] );
	$attributes['thumbnailUrl']        = esc_url_raw( $attributes['thumbnailUrl'] );
	$attributes['backgroundColor']     = ypw_sanitize_css_value( $attributes['backgroundColor'], '#ff7f66' );
	$attributes['contentColor']        = ypw_sanitize_css_value( $attributes['contentColor'], '#ffffff' );
	$attributes['titleColor']          = ypw_sanitize_css_value( $attributes['titleColor'], '#ffffff' );
	$attributes['textColor']           = ypw_sanitize_css_value( $attributes['textColor'], '#ffffff' );
	$attributes['accentColor']         = ypw_sanitize_css_value( $attributes['accentColor'], '#ff6f61' );
	$attributes['playButtonColor']     = ypw_sanitize_css_value( $attributes['playButtonColor'], '#ffffff' );
	$attributes['titleFontFamily']     = ypw_sanitize_font_family( $attributes['titleFontFamily'], '"Shadows Into Light", "Comic Sans MS", cursive' );
	$attributes['bodyFontFamily']      = ypw_sanitize_font_family( $attributes['bodyFontFamily'], 'Arial, Helvetica, sans-serif' );
	$attributes['titleFontSize']       = ypw_sanitize_css_value( $attributes['titleFontSize'], 'clamp(1.85rem, 3vw, 2.45rem)' );
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
		'--ypw-card-background'       => $attributes['backgroundColor'],
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
 * Safely read a value from an array.
 *
 * @param array<string,mixed> $array   Source array.
 * @param string              $key     Array key.
 * @param mixed               $default Default value.
 * @return mixed
 */
function ypw_get_array_value( $array, $key, $default = '' ) {
	return isset( $array[ $key ] ) ? $array[ $key ] : $default;
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
 * Sanitize font preset keys.
 *
 * @param mixed  $preset   Raw font preset.
 * @param string $fallback Fallback preset.
 * @return string
 */
function ypw_sanitize_font_preset( $preset, $fallback ) {
	$preset = (string) $preset;

	return array_key_exists( $preset, ypw_get_font_preset_options() ) ? $preset : $fallback;
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
