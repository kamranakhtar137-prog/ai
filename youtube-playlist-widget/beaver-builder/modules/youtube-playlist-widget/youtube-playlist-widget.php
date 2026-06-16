<?php
/**
 * Standard Beaver Builder module loader.
 *
 * Beaver Builder scans module paths for lowercase module folders that contain
 * a PHP file matching the folder name. This loader bridges that convention to
 * the shared module registration class.
 *
 * @package YouTubePlaylistWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ypw_module_class_file = dirname( __DIR__, 2 ) . '/class-ypw-beaver-builder-module.php';

if ( file_exists( $ypw_module_class_file ) ) {
	require_once $ypw_module_class_file;
}
