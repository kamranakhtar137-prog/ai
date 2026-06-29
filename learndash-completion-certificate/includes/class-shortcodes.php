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
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function course_topics( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id' => 0,
				'source'    => 'auto',
				'limit'     => 0,
				'prefix'    => '– ',
				'bullet'    => 'text',
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
			if ( 'svg' === $bullet ) {
				$lines[] = '<span style="display:block;margin:0 0 8px 0;line-height:1.5;">' . LDCC_SVG_Icons::topic_dash() . esc_html( $item ) . '</span>';
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
		if ( 'sfwd-lessons' === $post_type && function_exists( 'learndash_get_course_lessons_list' ) ) {
			$lessons = learndash_get_course_lessons_list( $course_id );
			if ( is_array( $lessons ) && ! empty( $lessons ) ) {
				$ids = array();
				foreach ( $lessons as $lesson ) {
					if ( is_object( $lesson ) && isset( $lesson->ID ) ) {
						$ids[] = absint( $lesson->ID );
					} elseif ( is_array( $lesson ) && ! empty( $lesson['post']->ID ) ) {
						$ids[] = absint( $lesson['post']->ID );
					} elseif ( is_numeric( $lesson ) ) {
						$ids[] = absint( $lesson );
					}
				}
				if ( ! empty( $ids ) ) {
					return array_values( array_unique( $ids ) );
				}
			}
		}

		if ( function_exists( 'learndash_course_get_steps_by_type' ) ) {
			$steps = learndash_course_get_steps_by_type( $course_id, $post_type );
			if ( is_array( $steps ) && ! empty( $steps ) ) {
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
	 * @param string $source    auto|topics|lessons|all|custom.
	 * @return array<int,string>
	 */
	private static function get_topic_items( $course_id, $source ) {
		if ( 'custom' === $source ) {
			return self::get_custom_topics( $course_id );
		}

		if ( 'auto' === $source ) {
			$topics = self::collect_items_by_type( $course_id, 'sfwd-topic' );
			if ( ! empty( $topics ) ) {
				return $topics;
			}

			$lessons = self::collect_items_by_type( $course_id, 'sfwd-lessons' );
			if ( ! empty( $lessons ) ) {
				return $lessons;
			}

			return self::get_custom_topics( $course_id );
		}

		if ( 'all' === $source ) {
			$topics  = self::collect_items_by_type( $course_id, 'sfwd-topic' );
			$lessons = self::collect_items_by_type( $course_id, 'sfwd-lessons' );
			$merged  = array_merge( $topics, $lessons );
			if ( ! empty( $merged ) ) {
				return array_values( array_unique( $merged ) );
			}

			return self::get_custom_topics( $course_id );
		}

		$post_type = ( 'lessons' === $source ) ? 'sfwd-lessons' : 'sfwd-topic';
		return self::collect_items_by_type( $course_id, $post_type );
	}

	/**
	 * Collect ordered titles for a step post type.
	 *
	 * @param int    $course_id Course ID.
	 * @param string $post_type Step post type.
	 * @return array<int,string>
	 */
	private static function collect_items_by_type( $course_id, $post_type ) {
		$step_ids = self::get_step_ids_by_type( $course_id, $post_type );
		$items    = array();

		foreach ( $step_ids as $step_id ) {
			$title = get_the_title( $step_id );
			if ( '' !== $title ) {
				$items[] = html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
		}

		if ( ! empty( $items ) ) {
			return $items;
		}

		if ( 'sfwd-topic' === $post_type ) {
			return self::collect_topics_from_lessons( $course_id );
		}

		return array();
	}

	/**
	 * Fallback: collect topics nested under each lesson.
	 *
	 * @param int $course_id Course ID.
	 * @return array<int,string>
	 */
	private static function collect_topics_from_lessons( $course_id ) {
		if ( ! function_exists( 'learndash_get_topic_list' ) ) {
			return array();
		}

		$items      = array();
		$lesson_ids = self::get_step_ids_by_type( $course_id, 'sfwd-lessons' );

		foreach ( $lesson_ids as $lesson_id ) {
			$topics = learndash_get_topic_list( $lesson_id, $course_id );
			if ( empty( $topics ) || ! is_array( $topics ) ) {
				continue;
			}

			foreach ( $topics as $topic ) {
				$topic_id = 0;
				if ( is_object( $topic ) && isset( $topic->ID ) ) {
					$topic_id = absint( $topic->ID );
				} elseif ( is_numeric( $topic ) ) {
					$topic_id = absint( $topic );
				}

				if ( $topic_id > 0 ) {
					$title = get_the_title( $topic_id );
					if ( '' !== $title ) {
						$items[] = html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) );
					}
				}
			}
		}

		return array_values( array_unique( $items ) );
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
