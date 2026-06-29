<?php
/**
 * Renders the full Teilnahmezertifikat layout for LearnDash certificates.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic certificate layout shortcode.
 */
class LDCC_Certificate_Layout {

	/**
	 * Register shortcode.
	 */
	public static function init() {
		add_shortcode( 'ldcc_certificate', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the complete certificate body.
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id'      => 0,
				'topics_source'  => 'topics',
				'topics_limit'   => 0,
				'text_width'     => 42,
				'image_width'    => 58,
				'padding_top'    => 72,
				'padding_right'  => 56,
				'padding_bottom' => 56,
				'padding_left'   => 24,
			),
			$atts,
			'ldcc_certificate'
		);

		$course_id   = LDCC_Course_Context::get_course_id( (int) $atts['course_id'] );
		$cert_id     = LDCC_Course_Context::get_certificate_post_id();
		$text_width  = self::sanitize_percent( $atts['text_width'], 42 );
		$image_width = self::sanitize_percent( $atts['image_width'], 58 );

		if ( $cert_id > 0 ) {
			$stored_text_width = get_post_meta( $cert_id, 'ldcc_layout_text_width', true );
			if ( '' !== $stored_text_width ) {
				$text_width = self::sanitize_percent( $stored_text_width, $text_width );
			}

			$stored_image_width = get_post_meta( $cert_id, 'ldcc_layout_image_width', true );
			if ( '' !== $stored_image_width ) {
				$image_width = self::sanitize_percent( $stored_image_width, $image_width );
			}
		}

		$course_attr = $course_id > 0 ? " course_id='" . esc_attr( (string) $course_id ) . "'" : '';
		$topics_html = self::render_topics( $course_id, $atts );
		$font_stack  = 'Verdana, Geneva, sans-serif';
		$cell_style  = sprintf(
			'padding:%1$dpx %2$dpx %3$dpx %4$dpx;color:#ffffff;font-family:%5$s;',
			(int) $atts['padding_top'],
			(int) $atts['padding_right'],
			(int) $atts['padding_bottom'],
			(int) $atts['padding_left'],
			$font_stack
		);

		ob_start();
		?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="<?php echo esc_attr( (string) $image_width ); ?>%">&nbsp;</td>
		<td width="<?php echo esc_attr( (string) $text_width ); ?>%" valign="top" style="<?php echo esc_attr( $cell_style ); ?>">

			<table cellpadding="0" cellspacing="0" border="0" width="100%">
				<tr>
					<td style="padding:0;margin:0;">
						<?php echo LDCC_SVG_Icons::certificate_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span style="font-size:34px;line-height:1.1;font-weight:bold;color:#ffffff;letter-spacing:0.03em;">Teilnahmezertifikat</span>
					</td>
				</tr>
				<tr>
					<td style="padding:0;">
						<?php echo LDCC_SVG_Icons::title_divider(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 18px 0;font-size:21px;line-height:1.3;font-weight:bold;color:#ffffff;">
						Herzlichen Gl&uuml;ckwunsch!
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 16px 0;font-size:15px;line-height:1.6;color:#f2f2f2;">
						Sie haben den Kurs &bdquo;[courseinfo show='course_title'<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]&ldquo; erfolgreich abgeschlossen.
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 28px 0;font-size:15px;line-height:1.6;color:#f2f2f2;">
						Dieses Zertifikat best&auml;tigt Ihre erfolgreiche Teilnahme an der Schulung.
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 30px 0;font-size:14px;line-height:1.5;color:#e8e8e8;">
						<span style="font-weight:600;color:#ffffff;">Name:</span>
						[usermeta field='first_name'] [usermeta field='last_name']
						<?php echo LDCC_SVG_Icons::meta_dot(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span style="font-weight:600;color:#ffffff;">Datum:</span>
						[courseinfo show='completed_on' format='d. F Y'<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]
					</td>
				</tr>
				<tr>
					<td style="padding:0;">
						<?php echo LDCC_SVG_Icons::section_divider(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 14px 0;font-size:17px;line-height:1.35;font-weight:bold;color:#ffffff;">
						Kursinformationen
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 8px 0;font-size:14px;line-height:1.55;color:#f2f2f2;">
						<span style="font-weight:600;color:#ffffff;">Kurs:</span>
						[courseinfo show='course_title'<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 8px 0;font-size:14px;line-height:1.55;color:#f2f2f2;">
						<span style="font-weight:600;color:#ffffff;">Lektionen:</span>
						[ldcc_lesson_count<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 6px 0;font-size:14px;line-height:1.55;color:#f2f2f2;font-weight:600;">
						Themen:
					</td>
				</tr>
				<tr>
					<td style="padding:0 0 28px 12px;font-size:14px;line-height:1.7;color:#f2f2f2;">
						<?php echo $topics_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<tr>
					<td style="padding:0;font-size:13px;line-height:1.65;color:#dddddd;">
						Wir bedanken uns f&uuml;r Ihre Teilnahme und w&uuml;nschen Ihnen viel Erfolg bei der Anwendung der erworbenen Kenntnisse.
					</td>
				</tr>
			</table>

		</td>
	</tr>
</table>
		<?php
		$html = (string) ob_get_clean();

		return do_shortcode( $html );
	}

	/**
	 * Render topics section with dynamic shortcode.
	 *
	 * @param int                  $course_id Course ID.
	 * @param array<string,string> $atts      Layout attributes.
	 * @return string
	 */
	private static function render_topics( $course_id, $atts ) {
		$source = sanitize_key( $atts['topics_source'] );
		$limit  = max( 0, (int) $atts['topics_limit'] );

		$shortcode = "[ldcc_course_topics source='{$source}' bullet='svg' prefix='– '";
		if ( $course_id > 0 ) {
			$shortcode .= " course_id='{$course_id}'";
		}
		if ( $limit > 0 ) {
			$shortcode .= " limit='{$limit}'";
		}
		$shortcode .= ']';

		return do_shortcode( $shortcode );
	}

	/**
	 * Clamp a percentage value.
	 *
	 * @param mixed $value         Raw value.
	 * @param int   $default_value Fallback.
	 * @return int
	 */
	private static function sanitize_percent( $value, $default_value ) {
		$value = absint( $value );
		if ( $value < 20 || $value > 80 ) {
			return $default_value;
		}
		return $value;
	}
}
