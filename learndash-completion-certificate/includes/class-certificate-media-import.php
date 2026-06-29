<?php
/**
 * Import JPG files from wp-content/uploads into the Media Library.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FTP / uploads folder media importer.
 */
class LDCC_Certificate_Media_Import {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	/**
	 * Import a JPG from uploads and set it as the certificate featured image.
	 *
	 * @param string $relative_path Path relative to uploads, e.g. 2026/06/certificate-bg.jpg.
	 * @param int    $post_id       Certificate post ID.
	 * @return int|WP_Error Attachment ID or error.
	 */
	public static function import_and_set_featured_image( $relative_path, $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return new WP_Error( 'ldcc_invalid_post', __( 'Invalid certificate post.', 'learndash-completion-certificate' ) );
		}

		$relative_path = self::sanitize_relative_upload_path( $relative_path );
		if ( '' === $relative_path ) {
			return new WP_Error( 'ldcc_invalid_path', __( 'Please enter a valid JPG path inside the uploads folder.', 'learndash-completion-certificate' ) );
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'ldcc_upload_dir', $upload_dir['error'] );
		}

		$file_path = trailingslashit( $upload_dir['basedir'] ) . $relative_path;
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new WP_Error(
				'ldcc_file_missing',
				sprintf(
					/* translators: %s: file path relative to uploads */
					__( 'File not found: uploads/%s', 'learndash-completion-certificate' ),
					$relative_path
				)
			);
		}

		$filetype = wp_check_filetype( basename( $file_path ) );
		if ( 'image/jpeg' !== $filetype['type'] ) {
			return new WP_Error( 'ldcc_invalid_type', __( 'LearnDash certificate backgrounds must be JPG files.', 'learndash-completion-certificate' ) );
		}

		$existing_id = self::find_attachment_by_relative_path( $relative_path );
		if ( $existing_id > 0 ) {
			set_post_thumbnail( $post_id, $existing_id );
			return $existing_id;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment = array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => sanitize_file_name( pathinfo( $file_path, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => trailingslashit( $upload_dir['baseurl'] ) . $relative_path,
		);

		$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		$metadata = wp_generate_attachment_metadata( $attach_id, $file_path );
		if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
			wp_update_attachment_metadata( $attach_id, $metadata );
		}

		update_post_meta( $attach_id, '_wp_attached_file', $relative_path );
		set_post_thumbnail( $post_id, $attach_id );

		return $attach_id;
	}

	/**
	 * Find an existing attachment for a relative uploads path.
	 *
	 * @param string $relative_path Relative uploads path.
	 * @return int
	 */
	public static function find_attachment_by_relative_path( $relative_path ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_wp_attached_file',
						'value' => $relative_path,
					),
				),
			)
		);

		return ! empty( $attachments[0] ) ? absint( $attachments[0] ) : 0;
	}

	/**
	 * Sanitize a path relative to wp-content/uploads.
	 *
	 * @param string $relative_path Raw path.
	 * @return string
	 */
	public static function sanitize_relative_upload_path( $relative_path ) {
		$relative_path = wp_normalize_path( (string) $relative_path );
		$relative_path = ltrim( $relative_path, '/' );

		if ( '' === $relative_path || str_contains( $relative_path, '..' ) ) {
			return '';
		}

		if ( ! preg_match( '/\.jpe?g$/i', $relative_path ) ) {
			return '';
		}

		return $relative_path;
	}

	/**
	 * Store an admin notice after import attempts.
	 *
	 * @param int          $post_id Certificate post ID.
	 * @param int|WP_Error $result  Attachment ID or error.
	 */
	public static function store_admin_notice( $post_id, $result ) {
		if ( is_wp_error( $result ) ) {
			set_transient(
				'ldcc_import_notice_' . get_current_user_id(),
				array(
					'type'    => 'error',
					'message' => $result->get_error_message(),
				),
				30
			);
			return;
		}

		set_transient(
			'ldcc_import_notice_' . get_current_user_id(),
			array(
				'type'    => 'success',
				'message' => sprintf(
					/* translators: %d: attachment ID */
					__( 'Background image imported and set as Featured Image (attachment ID %d).', 'learndash-completion-certificate' ),
					(int) $result
				),
			),
			30
		);
	}

	/**
	 * Show import success/error notices in admin.
	 */
	public static function render_admin_notice() {
		$notice = get_transient( 'ldcc_import_notice_' . get_current_user_id() );
		if ( empty( $notice ) || ! is_array( $notice ) ) {
			return;
		}

		delete_transient( 'ldcc_import_notice_' . get_current_user_id() );

		$class = ( 'success' === $notice['type'] ) ? 'notice-success' : 'notice-error';
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $notice['message'] )
		);
	}
}
