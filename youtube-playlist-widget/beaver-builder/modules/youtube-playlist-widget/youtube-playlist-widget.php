<?php
/**
 * Canonical Beaver Builder module definition.
 *
 * Beaver Builder scans module paths for folders like this:
 *
 * modules/youtube-playlist-widget/youtube-playlist-widget.php
 * modules/youtube-playlist-widget/includes/frontend.php
 *
 * Keeping the full module definition here avoids timing issues with bridge
 * loaders and makes the module discoverable by Beaver's standard scanner.
 *
 * @package YouTubePlaylistWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FLBuilder' ) || ! class_exists( 'FLBuilderModule' ) ) {
	return;
}

if ( ! class_exists( 'YPW_Beaver_Builder_Module' ) ) {
	/**
	 * Reusable Beaver Builder module.
	 */
	class YPW_Beaver_Builder_Module extends FLBuilderModule {
		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct(
				array(
					'name'            => __( 'YouTube Playlist Widget', 'youtube-playlist-widget' ),
					'description'     => __( 'Configurable YouTube playlist card with thumbnail, text, colors, and fonts.', 'youtube-playlist-widget' ),
					'category'        => __( 'YouTube', 'youtube-playlist-widget' ),
					'dir'             => YPW_PLUGIN_DIR . 'beaver-builder/modules/youtube-playlist-widget/',
					'url'             => YPW_PLUGIN_URL . 'beaver-builder/modules/youtube-playlist-widget/',
					'editor_export'   => true,
					'enabled'         => true,
					'partial_refresh' => true,
				)
			);
		}
	}
}

if ( ! defined( 'YPW_BB_MODULE_REGISTERED' ) ) {
	define( 'YPW_BB_MODULE_REGISTERED', true );

	FLBuilder::register_module(
		'YPW_Beaver_Builder_Module',
		array(
			'content'    => array(
				'title'    => __( 'Content', 'youtube-playlist-widget' ),
				'sections' => array(
					'text'     => array(
						'title'  => __( 'Text', 'youtube-playlist-widget' ),
						'fields' => array(
							'title'       => array(
								'type'    => 'text',
								'label'   => __( 'Playlist Card Title', 'youtube-playlist-widget' ),
								'default' => 'Vlogs aus dem Austausch',
							),
							'description' => array(
								'type'    => 'textarea',
								'label'   => __( 'Playlist Card Description/Text', 'youtube-playlist-widget' ),
								'rows'    => 4,
								'default' => "Video Description\ndescription description\ndescription\ndescription description\ndescription",
							),
							'button_text' => array(
								'type'    => 'text',
								'label'   => __( 'Button Text', 'youtube-playlist-widget' ),
								'default' => 'Alle Videos',
							),
							'content_title' => array(
								'type'    => 'text',
								'label'   => __( 'Right Heading', 'youtube-playlist-widget' ),
								'default' => 'Hier ist eine Überschrift',
							),
							'content_text'  => array(
								'type'    => 'textarea',
								'label'   => __( 'Right Text', 'youtube-playlist-widget' ),
								'rows'    => 5,
								'default' => 'Hast Du schon mal vom "American Dream" gehört? Er besagt, dass jede*r in den Vereinigten Staaten durch seine Fähigkeiten und Leistungen das individuelle Glück finden kann. Begib Dich mit uns auf die Reise Deines Lebens und erlebe Deinen ganz eigenen amerikanischen Traum in Deinem Schüleraustausch USA.',
							),
						),
					),
					'videos'   => array(
						'title'  => __( 'Video Rows', 'youtube-playlist-widget' ),
						'fields' => array(
							'video_one_date'  => array(
								'type'    => 'text',
								'label'   => __( 'Video 1 Date', 'youtube-playlist-widget' ),
								'default' => '3. März 2025',
							),
							'video_one_title' => array(
								'type'    => 'text',
								'label'   => __( 'Video 1 Title', 'youtube-playlist-widget' ),
								'default' => 'Schulalltag in Schweden | Experiment Vlog',
							),
							'video_two_date'  => array(
								'type'    => 'text',
								'label'   => __( 'Video 2 Date', 'youtube-playlist-widget' ),
								'default' => '18. Feb. 2025',
							),
							'video_two_title' => array(
								'type'    => 'text',
								'label'   => __( 'Video 2 Title', 'youtube-playlist-widget' ),
								'default' => 'Ein Wochenende in Stockholm | Experiment Vlog',
							),
						),
					),
					'playlist' => array(
						'title'  => __( 'Playlist', 'youtube-playlist-widget' ),
						'fields' => array(
							'playlist_url'    => array(
								'type'        => 'text',
								'label'       => __( 'YouTube Playlist URL', 'youtube-playlist-widget' ),
								'placeholder' => 'https://www.youtube.com/playlist?list=PLxxxxxxxxxxxx',
								'help'        => __( 'Paste a YouTube playlist URL, or enter only the playlist ID below.', 'youtube-playlist-widget' ),
							),
							'playlist_id'     => array(
								'type'        => 'text',
								'label'       => __( 'YouTube Playlist ID', 'youtube-playlist-widget' ),
								'placeholder' => 'PLxxxxxxxxxxxx',
							),
							'open_in_new_tab' => array(
								'type'    => 'select',
								'label'   => __( 'Open Playlist', 'youtube-playlist-widget' ),
								'default' => 'yes',
								'options' => array(
									'yes' => __( 'In a new tab', 'youtube-playlist-widget' ),
									'no'  => __( 'In the same tab', 'youtube-playlist-widget' ),
								),
							),
						),
					),
					'media'    => array(
						'title'  => __( 'Thumbnail/Image', 'youtube-playlist-widget' ),
						'fields' => array(
							'thumbnail'              => array(
								'type'        => 'photo',
								'label'       => __( 'Thumbnail Image', 'youtube-playlist-widget' ),
								'show_remove' => true,
							),
							'external_thumbnail_url' => array(
								'type'        => 'text',
								'label'       => __( 'External Thumbnail URL', 'youtube-playlist-widget' ),
								'placeholder' => 'https://example.com/playlist.jpg',
								'help'        => __( 'Optional fallback if no Media Library image is selected.', 'youtube-playlist-widget' ),
							),
						),
					),
					'layout'   => array(
						'title'  => __( 'Layout', 'youtube-playlist-widget' ),
						'fields' => array(
							'layout' => array(
								'type'    => 'select',
								'label'   => __( 'Layout', 'youtube-playlist-widget' ),
								'default' => 'split',
								'options' => array(
									'split'   => __( 'Version 6 split layout', 'youtube-playlist-widget' ),
									'stacked' => __( 'Stacked', 'youtube-playlist-widget' ),
								),
							),
						),
					),
				),
			),
			'style'      => array(
				'title'    => __( 'Style', 'youtube-playlist-widget' ),
				'sections' => array(
					'colors' => array(
						'title'  => __( 'Colors', 'youtube-playlist-widget' ),
						'fields' => array(
							'background_color'  => array(
								'type'       => 'color',
								'label'      => __( 'Playlist Card Background', 'youtube-playlist-widget' ),
								'default'    => 'ff7f66',
								'show_reset' => true,
							),
							'content_color'     => array(
								'type'       => 'color',
								'label'      => __( 'Content Background', 'youtube-playlist-widget' ),
								'default'    => 'ffffff',
								'show_reset' => true,
							),
							'title_color'       => array(
								'type'       => 'color',
								'label'      => __( 'Card Title Color', 'youtube-playlist-widget' ),
								'default'    => 'ffffff',
								'show_reset' => true,
							),
							'text_color'        => array(
								'type'       => 'color',
								'label'      => __( 'Card Text Color', 'youtube-playlist-widget' ),
								'default'    => 'ffffff',
								'show_reset' => true,
							),
							'accent_color'      => array(
								'type'       => 'color',
								'label'      => __( 'Heading/Accent Color', 'youtube-playlist-widget' ),
								'default'    => 'ff6f61',
								'show_reset' => true,
							),
							'play_button_color' => array(
								'type'       => 'color',
								'label'      => __( 'Play Circle Color', 'youtube-playlist-widget' ),
								'default'    => 'ffffff',
								'show_reset' => true,
							),
						),
					),
				),
			),
			'typography' => array(
				'title'    => __( 'Typography', 'youtube-playlist-widget' ),
				'sections' => array(
					'title_typography' => array(
						'title'  => __( 'Title', 'youtube-playlist-widget' ),
						'fields' => array(
							'title_font_preset' => array(
								'type'    => 'select',
								'label'   => __( 'Title Font Preset', 'youtube-playlist-widget' ),
								'default' => 'shadow',
								'options' => array(
									'baloo'  => __( 'Baloo', 'youtube-playlist-widget' ),
									'shadow' => __( 'Shadow', 'youtube-playlist-widget' ),
									'sans'   => __( 'System Sans', 'youtube-playlist-widget' ),
									'serif'  => __( 'Serif', 'youtube-playlist-widget' ),
									'custom' => __( 'Custom font-family value', 'youtube-playlist-widget' ),
								),
							),
							'title_font_family' => array(
								'type'        => 'text',
								'label'       => __( 'Custom Title Font Family', 'youtube-playlist-widget' ),
								'default'     => '"Shadows Into Light", "Comic Sans MS", cursive',
								'placeholder' => '"Shadows Into Light", cursive',
								'help'        => __( 'Used when the preset is set to Custom.', 'youtube-playlist-widget' ),
							),
							'title_font_size'   => array(
								'type'    => 'text',
								'label'   => __( 'Title Font Size', 'youtube-playlist-widget' ),
								'default' => 'clamp(1.85rem, 3vw, 2.45rem)',
								'help'    => __( 'Accepts CSS values such as 56px, 4rem, or clamp(...).', 'youtube-playlist-widget' ),
							),
						),
					),
					'body_typography'  => array(
						'title'  => __( 'Body', 'youtube-playlist-widget' ),
						'fields' => array(
							'body_font_preset'      => array(
								'type'    => 'select',
								'label'   => __( 'Body Font Preset', 'youtube-playlist-widget' ),
								'default' => 'sans',
								'options' => array(
									'baloo'  => __( 'Baloo', 'youtube-playlist-widget' ),
									'shadow' => __( 'Shadow', 'youtube-playlist-widget' ),
									'sans'   => __( 'System Sans', 'youtube-playlist-widget' ),
									'serif'  => __( 'Serif', 'youtube-playlist-widget' ),
									'custom' => __( 'Custom font-family value', 'youtube-playlist-widget' ),
								),
							),
							'body_font_family'      => array(
								'type'        => 'text',
								'label'       => __( 'Custom Body Font Family', 'youtube-playlist-widget' ),
								'default'     => 'Arial, Helvetica, sans-serif',
								'placeholder' => 'Arial, Helvetica, sans-serif',
								'help'        => __( 'Used when the preset is set to Custom.', 'youtube-playlist-widget' ),
							),
							'description_font_size' => array(
								'type'    => 'text',
								'label'   => __( 'Description Font Size', 'youtube-playlist-widget' ),
								'default' => 'clamp(1rem, 2vw, 1.25rem)',
							),
						),
					),
				),
			),
		)
	);
}
