<?php
/**
 * Plugin Name: LearnDash Completion Certificate
 * Description: Custom shortcodes and assets for the German Teilnahmezertifikat (lesson count, topics list).
 * Version: 1.0.7
 * Author: Cursor
 * Text Domain: learndash-completion-certificate
 * Requires Plugins: sfwd-lms
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LDCC_PLUGIN_FILE', __FILE__ );
define( 'LDCC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LDCC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LDCC_VERSION', '1.0.6' );

require_once LDCC_PLUGIN_DIR . 'includes/class-course-context.php';
require_once LDCC_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once LDCC_PLUGIN_DIR . 'includes/class-svg-icons.php';
require_once LDCC_PLUGIN_DIR . 'includes/class-certificate-layout.php';
require_once LDCC_PLUGIN_DIR . 'includes/class-certificate-button.php';
require_once LDCC_PLUGIN_DIR . 'includes/class-certificate-media-import.php';
require_once LDCC_PLUGIN_DIR . 'includes/class-certificate-admin.php';

/**
 * Bootstrap plugin components.
 */
function ldcc_bootstrap() {
	if ( ! class_exists( 'SFWD_LMS' ) && ! defined( 'LEARNDASH_LMS_PLUGIN_DIR' ) ) {
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'LearnDash Completion Certificate requires LearnDash LMS to be active.', 'learndash-completion-certificate' );
				echo '</p></div>';
			}
		);
		return;
	}

	LDCC_Course_Context::init();
	LDCC_Shortcodes::init();
	LDCC_Certificate_Layout::init();
	LDCC_Certificate_Button::init();
	LDCC_Certificate_Media_Import::init();
	LDCC_Certificate_Admin::init();
}
add_action( 'plugins_loaded', 'ldcc_bootstrap', 20 );
