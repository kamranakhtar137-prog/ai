<?php
/**
 * Plugin Name: Vendavo SEO Fixes
 * Description: Post-launch SEO fixes for vendavo.com — schema, canonicals, pagination, sitemap, redirects, and lowercase URL normalization.
 * Version: 1.0.0
 * Author: Keoch
 * Text Domain: vendavo-seo-fixes
 *
 * @package Vendavo_SEO_Fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VENDAVO_SEO_VERSION', '1.0.0' );
define( 'VENDAVO_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'VENDAVO_SEO_URL', plugin_dir_url( __FILE__ ) );

require_once VENDAVO_SEO_PATH . 'includes/class-output-buffer.php';
require_once VENDAVO_SEO_PATH . 'includes/class-platform-schema.php';
require_once VENDAVO_SEO_PATH . 'includes/class-canonical.php';
require_once VENDAVO_SEO_PATH . 'includes/class-pagination.php';
require_once VENDAVO_SEO_PATH . 'includes/class-sitemap.php';
require_once VENDAVO_SEO_PATH . 'includes/class-lowercase-redirect.php';
require_once VENDAVO_SEO_PATH . 'includes/class-redirects.php';

/**
 * Bootstrap plugin modules.
 */
final class Vendavo_SEO_Fixes {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		Vendavo_SEO_Output_Buffer::init();
		Vendavo_SEO_Platform_Schema::init();
		Vendavo_SEO_Canonical::init();
		Vendavo_SEO_Pagination::init();
		Vendavo_SEO_Sitemap::init();
		Vendavo_SEO_Lowercase_Redirect::init();
		Vendavo_SEO_Redirects::init();
	}
}

add_action( 'plugins_loaded', array( 'Vendavo_SEO_Fixes', 'init' ) );
