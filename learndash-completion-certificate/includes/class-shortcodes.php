<?php
/**
 * Custom LearnDash certificate shortcodes.
 *
 * @package LearnDashCompletionCertificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers certificate shortcodes for lesson count and topics.
 */
class LDCC_Shortcodes {

	/**
	 * Register shortcodes.
	 */
	public static function init() {
		add_shortcode( 'ldcc_lesson_count', array( __CLASS__, 'lesson_count' ) );
		add_shortcode( 'ldcc_course_topics', array( __CLASS__, 'course_topics' ) );
	}

	/**
	 * Output the number of lessons in the current course.
	 *
	 * Usage: [ldcc_lesson_count] or [ldcc_lesson_count course_id="123"]
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function lesson_count( $atts ) {
		$atts      = shortcode_atts(
			array(
				'course_id' => 0,
			),
			$atts,
			'ldcc_lesson_count'
		);
		$course_id = LDCC_Course_Context::get_course_id( (int) $atts['course_id'] );

		if ( $course_id <= 0 ) {
			return '';
		}

		$lesson_ids = self::get_step_ids_by_type( $course_id, 'sfwd-lessons' );
		return (string) count( $lesson_ids );
	}

	/**
	 * Output a bullet list of course topics.
	 *
	 * Usage:
	 *   [ldcc_course_topics]
	 *   [ldcc_course_topics source="topics" limit="10"]
	 *   [ldcc_course_topics source="lessons"]
	 *   [ldcc_course_topics source="custom"]
	 *
	 * `source="custom"` reads the course meta field `ldcc_certificate_topics`
	 * (one topic per line). Use this when topics should be hardcoded per course.
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function course_topics( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id' => 0,
				'source'    => 'topics',
				'limit'     => 0,
				'prefix'    => '– ',
				'bullet'    => 'svg',
			),
			$atts,
			'ldcc_course_topics'
		);

		$course_id = LDCC_Course_Context::get_course_id( (int) $atts['course_id'] );
		if ( $course_id <= 0 ) {
			return '';
		}

		$limit  = max( 0, (int) $atts['limit'] );
		$source = sanitize_key( $atts['source'] );
		$bullet = sanitize_key( $atts['bullet'] );
		$items  = self::get_topic_items( $course_id, $source );

		if ( empty( $items ) ) {
			return '';
		}

		if ( $limit > 0 ) {
			$items = array_slice( $items, 0, $limit );
		}

		$lines = array();
		foreach ( $items as $item ) {
			if ( 'svg' === $bullet && class_exists( 'LDCC_SVG_Icons' ) ) {
				$lines[] = '<span style="display:block;margin:0 0 6px 0;line-height:1.6;">' . LDCC_SVG_Icons::topic_dash() . esc_html( $item ) . '</span>';
			} else {
				$lines[] = esc_html( $atts['prefix'] . $item );
			}
		}

		return implode( '<br />', $lines );
	}

	/**
	 * Get step IDs for a course and post type.
	 *
	 * @param int    $course_id Course ID.
	 * @param string $post_type LearnDash step post type.
	 * @return array<int,int>
	 */
	private static function get_step_ids_by_type( $course_id, $post_type ) {
		if ( function_exists( 'learndash_course_get_steps_by_type' ) ) {
			$steps = learndash_course_get_steps_by_type( $course_id, $post_type );
			if ( is_array( $steps ) ) {
				return array_values( array_map( 'absint', $steps ) );
			}
		}

		if ( ! function_exists( 'learndash_get_course_steps' ) ) {
			return array();
		}

		$steps = learndash_get_course_steps( $course_id, array( $post_type ) );
		return self::flatten_step_ids( $steps, $post_type );
	}

	/**
	 * Flatten hierarchical LearnDash step arrays.
	 *
	 * @param mixed  $steps     Step tree or list.
	 * @param string $post_type Expected post type.
	 * @return array<int,int>
	 */
	private static function flatten_step_ids( $steps, $post_type ) {
		if ( empty( $steps ) || ! is_array( $steps ) ) {
			return array();
		}

		$ids = array();
		foreach ( $steps as $key => $value ) {
			$step_id = is_numeric( $key ) ? absint( $value ) : absint( $key );
			if ( $step_id > 0 && get_post_type( $step_id ) === $post_type ) {
				$ids[] = $step_id;
			}

			if ( is_array( $value ) ) {
				$ids = array_merge( $ids, self::flatten_step_ids( $value, $post_type ) );
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Build topic labels for a course.
	 *
	 * @param int    $course_id Course ID.
	 * @param string $source    topics|lessons|custom.
	 * @return array<int,string>
	 */
	private static function get_topic_items( $course_id, $source ) {
		if ( 'custom' === $source ) {
			return self::get_custom_topics( $course_id );
		}

		$post_type = ( 'lessons' === $source ) ? 'sfwd-lessons' : 'sfwd-topic';
		$step_ids  = self::get_step_ids_by_type( $course_id, $post_type );

		$items = array();
		foreach ( $step_ids as $step_id ) {
			$title = get_the_title( $step_id );
			if ( '' !== $title ) {
				$items[] = html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
		}

		return $items;
	}

	/**
	 * Read manually configured topics from course meta.
	 *
	 * @param int $course_id Course ID.
	 * @return array<int,string>
	 */
	private static function get_custom_topics( $course_id ) {
		$raw = get_post_meta( $course_id, 'ldcc_certificate_topics', true );
		if ( empty( $raw ) ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		if ( ! is_array( $lines ) ) {
			return array();
		}

		$items = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$items[] = $line;
			}
		}

		return $items;
	}
}
