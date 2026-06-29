<?php
/**
 * Admin helpers for sfwd-certificates posts.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Certificate admin UI.
 */
class LDCC_Certificate_Admin {

	/**
	 * Register admin hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post_sfwd-certificates', array( __CLASS__, 'save_meta_box' ), 10, 2 );
	}

	/**
	 * Register preview course meta box.
	 */
	public static function register_meta_box() {
		add_meta_box(
			'ldcc-certificate-preview',
			__( 'Certificate Settings', 'learndash-completion-certificate' ),
			array( __CLASS__, 'render_meta_box' ),
			'sfwd-certificates',
			'side',
			'default'
		);
	}

	/**
	 * Render preview course selector.
	 *
	 * @param WP_Post $post Certificate post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'ldcc_save_certificate_preview', 'ldcc_certificate_preview_nonce' );

		$selected_course = absint( get_post_meta( $post->ID, 'ldcc_preview_course_id', true ) );
		$text_width      = absint( get_post_meta( $post->ID, 'ldcc_layout_text_width', true ) );
		$image_width     = absint( get_post_meta( $post->ID, 'ldcc_layout_image_width', true ) );
		$courses         = get_posts(
			array(
				'post_type'      => 'sfwd-courses',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		echo '<p>';
		esc_html_e( 'Optional: choose a course used when previewing this certificate in admin or when course context cannot be detected automatically.', 'learndash-completion-certificate' );
		echo '</p>';

		echo '<label class="screen-reader-text" for="ldcc_preview_course_id">';
		esc_html_e( 'Preview course', 'learndash-completion-certificate' );
		echo '</label>';
		echo '<select name="ldcc_preview_course_id" id="ldcc_preview_course_id" style="width:100%;">';
		echo '<option value="0">' . esc_html__( 'Auto-detect', 'learndash-completion-certificate' ) . '</option>';

		foreach ( $courses as $course ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				absint( $course->ID ),
				selected( $selected_course, $course->ID, false ),
				esc_html( $course->post_title )
			);
		}

		echo '</select>';

		echo '<p style="margin-top:16px;"><strong>';
		esc_html_e( 'Layout split (match your background image)', 'learndash-completion-certificate' );
		echo '</strong></p>';

		echo '<p><label for="ldcc_layout_image_width">';
		esc_html_e( 'Photo column width (%)', 'learndash-completion-certificate' );
		echo '</label><br />';
		printf(
			'<input type="number" min="20" max="80" step="1" name="ldcc_layout_image_width" id="ldcc_layout_image_width" value="%1$d" style="width:100%%;" placeholder="58" />',
			$image_width > 0 ? $image_width : 58
		);
		echo '</p>';

		echo '<p><label for="ldcc_layout_text_width">';
		esc_html_e( 'Text column width (%)', 'learndash-completion-certificate' );
		echo '</label><br />';
		printf(
			'<input type="number" min="20" max="80" step="1" name="ldcc_layout_text_width" id="ldcc_layout_text_width" value="%1$d" style="width:100%%;" placeholder="42" />',
			$text_width > 0 ? $text_width : 42
		);
		echo '</p>';

		echo '<p style="font-size:12px;color:#646970;">';
		esc_html_e( 'Paste only [ldcc_certificate] into the certificate content area. The layout and dynamic fields are generated automatically.', 'learndash-completion-certificate' );
		echo '</p>';
	}

	/**
	 * Save preview course meta.
	 *
	 * @param int     $post_id Certificate post ID.
	 * @param WP_Post $post    Certificate post object.
	 */
	public static function save_meta_box( $post_id, $post ) {
		unset( $post );

		if ( ! isset( $_POST['ldcc_certificate_preview_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ldcc_certificate_preview_nonce'] ) ), 'ldcc_save_certificate_preview' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$course_id = isset( $_POST['ldcc_preview_course_id'] ) ? absint( wp_unslash( $_POST['ldcc_preview_course_id'] ) ) : 0;

		if ( $course_id > 0 ) {
			update_post_meta( $post_id, 'ldcc_preview_course_id', $course_id );
		} else {
			delete_post_meta( $post_id, 'ldcc_preview_course_id' );
		}

		$image_width = isset( $_POST['ldcc_layout_image_width'] ) ? absint( wp_unslash( $_POST['ldcc_layout_image_width'] ) ) : 0;
		$text_width  = isset( $_POST['ldcc_layout_text_width'] ) ? absint( wp_unslash( $_POST['ldcc_layout_text_width'] ) ) : 0;

		if ( $image_width >= 20 && $image_width <= 80 ) {
			update_post_meta( $post_id, 'ldcc_layout_image_width', $image_width );
		} else {
			delete_post_meta( $post_id, 'ldcc_layout_image_width' );
		}

		if ( $text_width >= 20 && $text_width <= 80 ) {
			update_post_meta( $post_id, 'ldcc_layout_text_width', $text_width );
		} else {
			delete_post_meta( $post_id, 'ldcc_layout_text_width' );
		}
	}
}
