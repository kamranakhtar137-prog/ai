<?php
/**
 * Resolves course and user context while LearnDash renders a certificate PDF.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Certificate context helper.
 */
class LDCC_Course_Context {

	/**
	 * Cached course ID for the current certificate render.
	 *
	 * @var int|null
	 */
	private static $course_id = null;

	/**
	 * Cached user ID for the current certificate render.
	 *
	 * @var int|null
	 */
	private static $user_id = null;

	/**
	 * Cached certificate post ID for the current certificate render.
	 *
	 * @var int|null
	 */
	private static $cert_id = null;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'learndash_certification_thumbnail_before', array( __CLASS__, 'capture_context_from_cert_args' ), 10, 2 );
		add_action( 'learndash_certification_content_write_cell_before', array( __CLASS__, 'capture_context_from_cert_args' ), 10, 2 );
		add_filter( 'learndash_course_certificate_link', array( __CLASS__, 'append_course_id_to_certificate_link' ), 10, 3 );
	}

	/**
	 * Ensure course_id is present in certificate download URLs.
	 *
	 * @param string $url       Certificate URL.
	 * @param int    $course_id Course ID.
	 * @param int    $user_id   User ID.
	 * @return string
	 */
	public static function append_course_id_to_certificate_link( $url, $course_id, $user_id ) {
		if ( $course_id > 0 ) {
			$url = add_query_arg( 'course_id', $course_id, $url );
		}

		if ( $user_id > 0 ) {
			$url = add_query_arg( 'user_id', $user_id, $url );
		}

		return $url;
	}

	/**
	 * Capture IDs from LearnDash certificate generation arguments.
	 *
	 * @param mixed             $arg1 Certificate args or image path.
	 * @param array<string,mixed> $cert_args Certificate arguments.
	 */
	public static function capture_context_from_cert_args( $arg1, $cert_args = array() ) {
		if ( is_array( $arg1 ) ) {
			$cert_args = $arg1;
		}

		if ( empty( $cert_args ) || ! is_array( $cert_args ) ) {
			return;
		}

		self::store_cert_args( $cert_args );
	}

	/**
	 * Persist certificate arguments in static cache.
	 *
	 * @param array<string,mixed> $cert_args Certificate arguments.
	 */
	private static function store_cert_args( $cert_args ) {
		if ( ! empty( $cert_args['course_id'] ) ) {
			self::$course_id = absint( $cert_args['course_id'] );
		}

		if ( ! empty( $cert_args['user_id'] ) ) {
			self::$user_id = absint( $cert_args['user_id'] );
		}

		$cert_id = 0;
		if ( ! empty( $cert_args['cert_id'] ) ) {
			$cert_id = absint( $cert_args['cert_id'] );
		} elseif ( ! empty( $cert_args['post_id'] ) ) {
			$cert_id = absint( $cert_args['post_id'] );
		}

		if ( $cert_id > 0 ) {
			self::$cert_id = $cert_id;
		}
	}

	/**
	 * Resolve the course ID for the active certificate.
	 *
	 * @param int $course_id Optional explicit course ID.
	 * @return int
	 */
	public static function get_course_id( $course_id = 0 ) {
		if ( $course_id > 0 ) {
			return absint( $course_id );
		}

		if ( null !== self::$course_id && self::$course_id > 0 ) {
			return self::$course_id;
		}

		$request_keys = array( 'course_id', 'ld_course', 'course' );
		foreach ( $request_keys as $key ) {
			if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				self::$course_id = absint( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return self::$course_id;
			}
		}

		$cert_id = self::get_certificate_post_id();
		$user_id = self::get_user_id();

		if ( $cert_id > 0 && $user_id > 0 ) {
			$resolved = self::find_user_course_for_certificate( $user_id, $cert_id );
			if ( $resolved > 0 ) {
				self::$course_id = $resolved;
				return $resolved;
			}
		}

		if ( $cert_id > 0 ) {
			$preview_course_id = absint( get_post_meta( $cert_id, 'ldcc_preview_course_id', true ) );
			if ( $preview_course_id > 0 ) {
				self::$course_id = $preview_course_id;
				return $preview_course_id;
			}

			$linked_course_id = self::find_course_by_certificate( $cert_id );
			if ( $linked_course_id > 0 ) {
				self::$course_id = $linked_course_id;
				return $linked_course_id;
			}
		}

		if ( function_exists( 'learndash_get_course_id' ) ) {
			$resolved = learndash_get_course_id( $cert_id );
			if ( ! empty( $resolved ) ) {
				self::$course_id = absint( $resolved );
				return self::$course_id;
			}
		}

		return 0;
	}

	/**
	 * Resolve the user ID for the active certificate.
	 *
	 * @param int $user_id Optional explicit user ID.
	 * @return int
	 */
	public static function get_user_id( $user_id = 0 ) {
		if ( $user_id > 0 ) {
			return absint( $user_id );
		}

		if ( null !== self::$user_id && self::$user_id > 0 ) {
			return self::$user_id;
		}

		if ( ! empty( $_GET['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::$user_id = absint( wp_unslash( $_GET['user_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return self::$user_id;
		}

		if ( ! empty( $_GET['user'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::$user_id = absint( wp_unslash( $_GET['user'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return self::$user_id;
		}

		return get_current_user_id();
	}

	/**
	 * Get the certificate post ID from the current request.
	 *
	 * @return int
	 */
	public static function get_certificate_post_id() {
		if ( null !== self::$cert_id && self::$cert_id > 0 ) {
			return self::$cert_id;
		}

		$request_keys = array( 'cert_id', 'ld_certificate', 'certificate_id' );
		foreach ( $request_keys as $key ) {
			if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				self::$cert_id = absint( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return self::$cert_id;
			}
		}

		if ( is_singular( 'sfwd-certificates' ) ) {
			self::$cert_id = get_the_ID();
			return self::$cert_id;
		}

		return 0;
	}

	/**
	 * Find the completed course for a user/certificate pair.
	 *
	 * @param int $user_id User ID.
	 * @param int $cert_id Certificate post ID.
	 * @return int
	 */
	private static function find_user_course_for_certificate( $user_id, $cert_id ) {
		if ( ! function_exists( 'learndash_certificate_get_used_by' ) ) {
			return 0;
		}

		$course_ids = learndash_certificate_get_used_by( $cert_id, 'sfwd-courses' );
		if ( empty( $course_ids ) || ! is_array( $course_ids ) ) {
			return 0;
		}

		$course_ids = array_map( 'absint', $course_ids );

		if ( 1 === count( $course_ids ) ) {
			return $course_ids[0];
		}

		if ( function_exists( 'learndash_course_status' ) ) {
			foreach ( $course_ids as $course_id ) {
				if ( 'completed' === learndash_course_status( $course_id, $user_id ) ) {
					return $course_id;
				}
			}
		}

		return $course_ids[0];
	}

	/**
	 * Find a course that uses the given certificate.
	 *
	 * @param int $certificate_id Certificate post ID.
	 * @return int
	 */
	private static function find_course_by_certificate( $certificate_id ) {
		if ( function_exists( 'learndash_certificate_get_used_by' ) ) {
			$course_ids = learndash_certificate_get_used_by( $certificate_id, 'sfwd-courses' );
			if ( ! empty( $course_ids[0] ) ) {
				return absint( $course_ids[0] );
			}
		}

		if ( ! function_exists( 'learndash_get_setting' ) ) {
			return 0;
		}

		$all_courses = get_posts(
			array(
				'post_type'      => 'sfwd-courses',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $all_courses as $course_id ) {
			$linked_cert = learndash_get_setting( $course_id, 'certificate' );
			if ( (string) $linked_cert === (string) $certificate_id ) {
				return absint( $course_id );
			}
		}

		return 0;
	}
}
