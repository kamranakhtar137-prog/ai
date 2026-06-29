<?php
/**
 * Inline SVG fragments for LearnDash TCPDF certificates.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple SVG helpers compatible with LearnDash PDF rendering.
 */
class LDCC_SVG_Icons {

	/**
	 * Decorative line under the certificate title.
	 *
	 * @return string
	 */
	public static function title_divider() {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="8" viewBox="0 0 180 8" style="display:block;margin:0 0 24px 0;">
			<line x1="0" y1="4" x2="180" y2="4" stroke="#ffffff" stroke-width="1.5" opacity="0.9"/>
			<line x1="0" y1="7" x2="120" y2="7" stroke="#ffffff" stroke-width="1" opacity="0.35"/>
		</svg>';
	}

	/**
	 * Accent divider before a section heading.
	 *
	 * @return string
	 */
	public static function section_divider() {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="10" viewBox="0 0 320 10" style="display:block;margin:0 0 14px 0;">
			<line x1="0" y1="5" x2="320" y2="5" stroke="#ffffff" stroke-width="1" opacity="0.25"/>
		</svg>';
	}

	/**
	 * Small certificate badge icon.
	 *
	 * @return string
	 */
	public static function certificate_badge() {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" style="display:inline-block;vertical-align:middle;margin-right:8px;">
			<circle cx="14" cy="14" r="12" fill="none" stroke="#ffffff" stroke-width="1.5" opacity="0.85"/>
			<path d="M14 6 L16.2 11.8 L22.5 12.3 L17.6 16.1 L19.1 22.3 L14 19.2 L8.9 22.3 L10.4 16.1 L5.5 12.3 L11.8 11.8 Z" fill="none" stroke="#ffffff" stroke-width="1.2" opacity="0.75"/>
		</svg>';
	}

	/**
	 * Topic list bullet dash.
	 *
	 * @return string
	 */
	public static function topic_dash() {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="10" viewBox="0 0 14 10" style="display:inline-block;vertical-align:middle;margin-right:6px;">
			<line x1="0" y1="5" x2="10" y2="5" stroke="#f2f2f2" stroke-width="2" stroke-linecap="round"/>
		</svg>';
	}

	/**
	 * Dot separator for meta line.
	 *
	 * @return string
	 */
	public static function meta_dot() {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 8 8" style="display:inline-block;vertical-align:middle;margin:0 8px;">
			<circle cx="4" cy="4" r="2" fill="#d0d0d0"/>
		</svg>';
	}
}
