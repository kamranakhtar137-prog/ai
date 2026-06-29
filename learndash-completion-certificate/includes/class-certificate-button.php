<?php
/**
 * Shows the certificate download button on course and lesson pages.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend certificate download button.
 */
class LDCC_Certificate_Button {

	/**
	 * Track rendered buttons per request.
	 *
	 * @var array<string,bool>
	 */
	private static $rendered = array();

	/**
	 * Register hooks.
	 */
	public static function init() {
		if ( 'no' === get_option( 'ldcc_show_download_button', 'yes' ) ) {
			return;
		}

		$step_hooks = array(
			'learndash-lesson-after',
			'learndash-topic-after',
			'learndash-course-after',
		);

		foreach ( $step_hooks as $hook ) {
			add_action( $hook, array( __CLASS__, 'render_for_step' ), 20, 3 );
		}

		add_action( 'learndash-focus-content-footer-after', array( __CLASS__, 'render_for_focus_footer' ), 20, 2 );
		add_action( 'learndash-focus-content-content-after', array( __CLASS__, 'render_for_focus_content' ), 20, 2 );
	}

	/**
	 * Render on lesson/topic/course templates.
	 *
	 * @param int $post_id   Step post ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	public static function render_for_step( $post_id, $course_id, $user_id ) {
		unset( $post_id );
		self::render( $course_id, $user_id );
	}

	/**
	 * Render in focus mode footer.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	public static function render_for_focus_footer( $course_id, $user_id ) {
		self::render( $course_id, $user_id );
	}

	/**
	 * Render in focus mode content area.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	public static function render_for_focus_content( $course_id, $user_id ) {
		self::render( $course_id, $user_id );
	}

	/**
	 * Output certificate UI for a course/user pair.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	private static function render( $course_id, $user_id ) {
		$course_id = absint( $course_id );
		$user_id   = absint( $user_id );

		if ( $course_id <= 0 || $user_id <= 0 ) {
			return;
		}

		$render_key = $course_id . ':' . $user_id;
		if ( isset( self::$rendered[ $render_key ] ) ) {
			return;
		}

		$html = self::get_markup( $course_id, $user_id );
		if ( '' === $html ) {
			return;
		}

		self::$rendered[ $render_key ] = true;
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build certificate button or finish-course notice.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 * @return string
	 */
	public static function get_markup( $course_id, $user_id ) {
		if ( ! function_exists( 'learndash_course_status' ) || ! function_exists( 'learndash_get_course_certificate_link' ) ) {
			return '';
		}

		$status = learndash_course_status( $course_id, $user_id, true );
		$link   = learndash_get_course_certificate_link( $course_id, $user_id );
		$cert   = self::get_assigned_certificate_id( $course_id );

		if ( 'completed' === $status && ! empty( $link ) ) {
			return self::get_download_button_html( $link );
		}

		if ( 'completed' === $status && $cert <= 0 ) {
			return self::get_notice_html(
				__( 'Der Kurs ist abgeschlossen, aber es ist kein Zertifikat mit diesem Kurs verknüpft. Bitte weisen Sie in den Kurseinstellungen ein Zertifikat zu.', 'learndash-completion-certificate' )
			);
		}

		if ( self::all_steps_complete( $course_id, $user_id ) && 'completed' !== $status ) {
			return self::get_notice_html(
				__( 'Alle Lektionen sind abgeschlossen. Klicken Sie auf „Beenden Kurs“, um Ihr Zertifikat freizuschalten.', 'learndash-completion-certificate' )
			);
		}

		return '';
	}

	/**
	 * Get assigned certificate ID for a course.
	 *
	 * @param int $course_id Course ID.
	 * @return int
	 */
	private static function get_assigned_certificate_id( $course_id ) {
		if ( ! function_exists( 'learndash_get_setting' ) ) {
			return 0;
		}

		return absint( learndash_get_setting( $course_id, 'certificate' ) );
	}

	/**
	 * Check whether all course steps are complete.
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 * @return bool
	 */
	private static function all_steps_complete( $course_id, $user_id ) {
		if ( ! function_exists( 'learndash_course_progress' ) ) {
			return false;
		}

		$progress = learndash_course_progress(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'array'     => true,
			)
		);

		if ( empty( $progress ) || ! is_array( $progress ) ) {
			return false;
		}

		$completed = isset( $progress['completed'] ) ? (int) $progress['completed'] : 0;
		$total     = isset( $progress['total'] ) ? (int) $progress['total'] : 0;

		return $total > 0 && $completed >= $total;
	}

	/**
	 * Download button markup.
	 *
	 * @param string $link Certificate URL.
	 * @return string
	 */
	private static function get_download_button_html( $link ) {
		$label = apply_filters( 'ldcc_certificate_download_label', __( 'Zertifikat herunterladen', 'learndash-completion-certificate' ) );

		return sprintf(
			'<div class="ldcc-certificate-download" style="margin:24px 0;padding:20px;border:1px solid #d9e2f2;border-radius:8px;background:#f7faff;text-align:center;">
				<p style="margin:0 0 12px 0;font-size:16px;font-weight:600;color:#1d2327;">%1$s</p>
				<a href="%2$s" class="ldcc-certificate-download__button" style="display:inline-block;padding:12px 24px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:4px;font-weight:700;" target="_blank" rel="noopener noreferrer">%3$s</a>
			</div>',
			esc_html__( 'Sie haben diesen Kurs erfolgreich abgeschlossen.', 'learndash-completion-certificate' ),
			esc_url( $link ),
			esc_html( $label )
		);
	}

	/**
	 * Informational notice markup.
	 *
	 * @param string $message Notice text.
	 * @return string
	 */
	private static function get_notice_html( $message ) {
		return sprintf(
			'<div class="ldcc-certificate-notice" style="margin:24px 0;padding:16px 20px;border:1px solid #f0c36d;border-radius:8px;background:#fff8e5;color:#6a4b16;font-size:15px;line-height:1.5;">%s</div>',
			esc_html( $message )
		);
	}
}
