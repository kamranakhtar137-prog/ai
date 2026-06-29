<?php
/**
 * TCPDF-safe decorative elements for certificate PDFs.
 *
 * LearnDash uses TCPDF which does not reliably render inline SVG.
 * These helpers use HTML borders and characters instead.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDF-safe certificate decorations.
 */
class LDCC_Certificate_Decorations {

	/**
	 * Line under the main title.
	 *
	 * @return string
	 */
	public static function title_divider() {
		return '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 20px 0;">
			<tr><td style="border-bottom:2px solid #ffffff;font-size:1px;line-height:1px;">&nbsp;</td></tr>
			<tr><td style="border-bottom:1px solid #888888;font-size:1px;line-height:1px;width:65%;">&nbsp;</td></tr>
		</table>';
	}

	/**
	 * Line before a section heading.
	 *
	 * @return string
	 */
	public static function section_divider() {
		return '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 16px 0;">
			<tr><td style="border-bottom:1px solid #666666;font-size:1px;line-height:1px;">&nbsp;</td></tr>
		</table>';
	}

	/**
	 * Decorative prefix for the title.
	 *
	 * @return string
	 */
	public static function title_badge() {
		return '<span style="font-size:22px;color:#ffffff;margin-right:8px;">&#9733;</span>';
	}

	/**
	 * Separator between name and date.
	 *
	 * @return string
	 */
	public static function meta_separator() {
		return '<span style="color:#cccccc;margin:0 10px;">&middot;</span>';
	}
}

/**
 * Backward-compatible alias for older layout code.
 */
class LDCC_SVG_Icons {

	/**
	 * @return string
	 */
	public static function title_divider() {
		return LDCC_Certificate_Decorations::title_divider();
	}

	/**
	 * @return string
	 */
	public static function section_divider() {
		return LDCC_Certificate_Decorations::section_divider();
	}

	/**
	 * @return string
	 */
	public static function certificate_badge() {
		return LDCC_Certificate_Decorations::title_badge();
	}

	/**
	 * @return string
	 */
	public static function meta_dot() {
		return LDCC_Certificate_Decorations::meta_separator();
	}

	/**
	 * @return string
	 */
	public static function topic_dash() {
		return '<span style="color:#f2f2f2;margin-right:8px;">&ndash;</span>';
	}
}
