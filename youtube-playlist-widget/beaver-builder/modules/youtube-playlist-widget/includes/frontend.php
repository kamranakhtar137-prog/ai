<?php
/**
 * Beaver Builder frontend template.
 *
 * @package YouTubePlaylistWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_style( 'ypw-widget' );

echo ypw_render_widget( ypw_map_beaver_builder_settings( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
