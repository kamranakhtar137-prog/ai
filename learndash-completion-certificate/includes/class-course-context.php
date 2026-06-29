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
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'learndash_certificate_created', array( __CLASS__, 'capture_context_from_cert_args' ), 10, 1 );
	}

	/**
	 * Capture IDs from LearnDash certificate generation arguments.
	 *
	 * @param array<string,mixed> $cert_args Certificate arguments.
	 */
	public static function capture_context_from_cert_args( $cert_args ) {
		if ( ! empty( $cert_args['course_id'] ) ) {
			self::$course_id = absint( $cert_args['course_id'] );
		}

		if ( ! empty( $cert_args['user_id'] ) ) {
			self::$user_id = absint( $cert_args['user_id'] );
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
				return absint( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		$cert_id = self::get_certificate_post_id();
		if ( $cert_id > 0 ) {
			$linked_course_id = self::find_course_by_certificate( $cert_id );
			if ( $linked_course_id > 0 ) {
				self::$course_id = $linked_course_id;
				return $linked_course_id;
			}
		}

		if ( function_exists( 'learndash_get_course_id' ) ) {
			$resolved = learndash_get_course_id();
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

		return get_current_user_id();
	}

	/**
	 * Get the certificate post ID from the current request.
	 *
	 * @return int
	 */
	public static function get_certificate_post_id() {
		$request_keys = array( 'cert_id', 'ld_certificate', 'certificate_id' );
		foreach ( $request_keys as $key ) {
			if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return absint( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		if ( is_singular( 'sfwd-certificates' ) ) {
			return get_the_ID();
		}

		return 0;
	}

	/**
	 * Find a course that uses the given certificate.
	 *
	 * @param int $certificate_id Certificate post ID.
	 * @return int
	 */
	private static function find_course_by_certificate( $certificate_id ) {
		$courses = get_posts(
			array(
				'post_type'      => 'sfwd-courses',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'   => 'certificate',
						'value' => (string) $certificate_id,
					),
					array(
						'key'   => '_ld_certificate',
						'value' => (string) $certificate_id,
					),
				),
			)
		);

		if ( ! empty( $courses[0] ) ) {
			return absint( $courses[0] );
		}

		if ( function_exists( 'learndash_get_setting' ) ) {
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
		}

		return 0;
	}
}
