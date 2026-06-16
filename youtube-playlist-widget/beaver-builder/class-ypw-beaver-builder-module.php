<?php
/**
 * Beaver Builder module registration for the YouTube Playlist Widget.
 *
 * @package YouTubePlaylistWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FLBuilderModule' ) ) {
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
					'category'        => __( 'Media', 'youtube-playlist-widget' ),
					'dir'             => YPW_PLUGIN_DIR . 'beaver-builder/',
					'url'             => YPW_PLUGIN_URL . 'beaver-builder/',
					'editor_export'   => true,
					'enabled'         => true,
					'partial_refresh' => true,
				)
			);
		}
	}
}

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
							'label'   => __( 'Title', 'youtube-playlist-widget' ),
							'default' => 'YouTube Playlist',
						),
						'description' => array(
							'type'    => 'textarea',
							'label'   => __( 'Description/Text', 'youtube-playlist-widget' ),
							'rows'    => 4,
							'default' => 'Hier findest Du unsere YouTube-Playlist abcdfeghijklmn',
						),
						'button_text' => array(
							'type'    => 'text',
							'label'   => __( 'Button Text', 'youtube-playlist-widget' ),
							'default' => 'Playlist ansehen',
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
						'thumbnail'     => array(
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
							'label'      => __( 'Outer Background', 'youtube-playlist-widget' ),
							'default'    => 'f8f3ec',
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
							'label'      => __( 'Title Color', 'youtube-playlist-widget' ),
							'default'    => '1b1b1b',
							'show_reset' => true,
						),
						'text_color'        => array(
							'type'       => 'color',
							'label'      => __( 'Description Color', 'youtube-playlist-widget' ),
							'default'    => '3d3d3d',
							'show_reset' => true,
						),
						'accent_color'      => array(
							'type'       => 'color',
							'label'      => __( 'CTA/Accent Color', 'youtube-playlist-widget' ),
							'default'    => 'ff0000',
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
							'default' => 'baloo',
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
							'default'     => '"Baloo 2", "Arial Rounded MT Bold", Arial, sans-serif',
							'placeholder' => '"Baloo 2", Arial, sans-serif',
							'help'        => __( 'Used when the preset is set to Custom.', 'youtube-playlist-widget' ),
						),
						'title_font_size'   => array(
							'type'    => 'text',
							'label'   => __( 'Title Font Size', 'youtube-playlist-widget' ),
							'default' => 'clamp(2rem, 5vw, 4.5rem)',
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
