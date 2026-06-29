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
	 * Register shortcode and PDF content filters.
	 */
	public static function init() {
		add_shortcode( 'ldcc_certificate', array( __CLASS__, 'render' ) );
		add_filter( 'learndash_certificate_content', array( __CLASS__, 'normalize_pdf_content' ), 5, 2 );
		add_action( 'save_post_sfwd-certificates', array( __CLASS__, 'normalize_saved_content' ), 20, 2 );
	}

	/**
	 * Ensure certificate posts only store the layout shortcode.
	 *
	 * @param int     $post_id Certificate post ID.
	 * @param WP_Post $post    Certificate post object.
	 */
	public static function normalize_saved_content( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || 'sfwd-certificates' !== $post->post_type ) {
			return;
		}

		if ( false === strpos( $post->post_content, '[ldcc_certificate' ) ) {
			return;
		}

		$shortcode = self::extract_shortcode_tag( $post->post_content );
		if ( '' === $shortcode || $shortcode === trim( $post->post_content ) ) {
			return;
		}

		remove_action( 'save_post_sfwd-certificates', array( __CLASS__, 'normalize_saved_content' ), 20 );
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $shortcode,
			)
		);
		add_action( 'save_post_sfwd-certificates', array( __CLASS__, 'normalize_saved_content' ), 20, 2 );
	}

	/**
	 * Strip legacy decoration markup and normalize shortcode-only certificates.
	 *
	 * @param string $content Certificate HTML.
	 * @param int    $cert_id Certificate post ID.
	 * @return string
	 */
	public static function normalize_pdf_content( $content, $cert_id ) {
		unset( $cert_id );

		if ( false !== strpos( $content, '[ldcc_certificate' ) ) {
			$shortcode = self::extract_shortcode_tag( $content );
			if ( '' !== $shortcode ) {
				return do_shortcode( $shortcode );
			}
		}

		return self::strip_legacy_decorations( $content );
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
				'topics_source'  => 'auto',
				'topics_limit'   => 0,
				'text_width'     => 42,
				'image_width'    => 58,
				'padding_top'    => 42,
				'padding_right'  => 32,
				'padding_bottom' => 32,
				'padding_left'   => 14,
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
			'padding:%1$dpt %2$dpt %3$dpt %4$dpt;color:#ffffff;font-family:%5$s;font-size:13pt;',
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
			<p style="margin:0 0 8pt 0;font-size:28pt;line-height:1.1;font-weight:bold;color:#ffffff;">Teilnahmezertifikat</p>
			<p style="margin:0 0 8pt 0;font-size:18pt;line-height:1.2;font-weight:bold;color:#ffffff;">Herzlichen Gl&uuml;ckwunsch!</p>
			<p style="margin:0 0 6pt 0;font-size:13pt;line-height:1.45;color:#f2f2f2;">Sie haben den Kurs &bdquo;[courseinfo show='course_title'<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]&ldquo; erfolgreich abgeschlossen.</p>
			<p style="margin:0 0 10pt 0;font-size:13pt;line-height:1.45;color:#f2f2f2;">Dieses Zertifikat best&auml;tigt Ihre erfolgreiche Teilnahme an der Schulung.</p>
			<p style="margin:0 0 12pt 0;font-size:12pt;line-height:1.4;color:#ececec;"><strong style="color:#ffffff;font-size:12pt;">Name:</strong> [usermeta field='first_name'] [usermeta field='last_name'] <span style="color:#cccccc;">|</span> <strong style="color:#ffffff;font-size:12pt;">Datum:</strong> [courseinfo show='completed_on' format='d. F Y'<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]</p>
			<p style="margin:0 0 6pt 0;font-size:16pt;line-height:1.25;font-weight:bold;color:#ffffff;">Kursinformationen</p>
			<p style="margin:0 0 4pt 0;font-size:12pt;line-height:1.4;color:#f2f2f2;"><strong style="color:#ffffff;font-size:12pt;">Kurs:</strong> [courseinfo show='course_title'<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]</p>
			<p style="margin:0 0 4pt 0;font-size:12pt;line-height:1.4;color:#f2f2f2;"><strong style="color:#ffffff;font-size:12pt;">Lektionen:</strong> [ldcc_lesson_count<?php echo $course_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>]</p>
			<p style="margin:0 0 3pt 0;font-size:12pt;line-height:1.4;color:#ffffff;font-weight:bold;">Themen:</p>
			<p style="margin:0 0 10pt 8pt;font-size:12pt;line-height:1.5;color:#f2f2f2;"><?php echo $topics_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<p style="margin:0;font-size:11pt;line-height:1.5;color:#dddddd;">Wir bedanken uns f&uuml;r Ihre Teilnahme und w&uuml;nschen Ihnen viel Erfolg bei der Anwendung der erworbenen Kenntnisse.</p>
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

		$shortcode = "[ldcc_course_topics source='{$source}' bullet='text' prefix='– '";
		if ( $course_id > 0 ) {
			$shortcode .= " course_id='{$course_id}'";
		}
		if ( $limit > 0 ) {
			$shortcode .= " limit='{$limit}'";
		}
		$shortcode .= ']';

		$output = do_shortcode( $shortcode );
		if ( '' !== $output ) {
			return $output;
		}

		return '<span style="color:#cccccc;font-size:12pt;">&ndash; ' . esc_html__( 'Keine Themen verfügbar', 'learndash-completion-certificate' ) . '</span>';
	}

	/**
	 * Extract the first ldcc_certificate shortcode from mixed content.
	 *
	 * @param string $content Raw certificate content.
	 * @return string
	 */
	private static function extract_shortcode_tag( $content ) {
		if ( preg_match( '/\[ldcc_certificate(?:\s+[^\]]*)?\]/', $content, $matches ) ) {
			return $matches[0];
		}

		return '';
	}

	/**
	 * Remove decoration markup from older plugin versions or pasted HTML.
	 *
	 * @param string $content Certificate HTML.
	 * @return string
	 */
	private static function strip_legacy_decorations( $content ) {
		$patterns = array(
			'/<span[^>]*>\s*(?:&#9733;|★|\?)\s*<\/span>/i',
			'/<table[^>]*>[\s\S]*?border-bottom[\s\S]*?<\/table>/i',
		);

		foreach ( $patterns as $pattern ) {
			$content = preg_replace( $pattern, '', $content );
		}

		return $content;
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
