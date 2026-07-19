<?php
/**
 * Home v2 — Fun Egg games data helpers.
 *
 * @package EggsShop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Game cards for the Fun in Every Egg section slider.
 *
 * @param string $theme_uri Theme directory URI.
 * @param string $games_url Games hub URL.
 * @return array<int, array<string, string>>
 */
function et_home_get_fun_egg_game_cards( $theme_uri = '', $games_url = '' ) {
	if ( ! $theme_uri && function_exists( 'get_template_directory_uri' ) ) {
		$theme_uri = get_template_directory_uri();
	}

	if ( ! $games_url ) {
		$games_url = function_exists( 'get_permalink' ) ? get_permalink( 617 ) : home_url( '/games/' );
	}

	if ( empty( $games_url ) || '#' === $games_url ) {
		$games_url = home_url( '/games/' );
	}

	$uploads = 'https://eggstime.com/wp-content/uploads/2026/07';
	$images  = $theme_uri . '/images/games';

	return array(
		array(
			'slug'        => 'maze-time',
			'title'       => __( 'Maze Time', 'eggs-shop' ),
			'description' => __( 'Find the right path and complete exciting maze adventures! Build problem-solving skills while having fun exploring every challenge.', 'eggs-shop' ),
			'url'         => 'http://eggstime.com/upload/mazes/index.html',
			'image'       => $uploads . '/Play_4.png',
			'icon'        => 'maze',
			'tone'        => 'blue',
			'panel'       => '#e8f3fc',
			'accent'      => '#1a9fe0',
		),
		array(
			'slug'        => 'magical-words',
			'title'       => __( 'Magical Words', 'eggs-shop' ),
			'description' => __( 'Learn good manners, kind words, and everyday etiquette through fun interactive activities. Practice saying “Please,” “Thank You,” and other magical words that help children become kind and respectful.', 'eggs-shop' ),
			'url'         => 'https://eggstime.com/games/',
			'image'       => $uploads . '/%D0%98%D0%B3%D1%80%D0%B0.png',
			'icon'        => 'chat',
			'tone'        => 'purple',
			'panel'       => '#f3eef9',
			'accent'      => '#8e44ad',
		),
		array(
			'slug'        => 'coloring-time',
			'title'       => __( 'Coloring Time', 'eggs-shop' ),
			'description' => __( 'Color cheerful egg characters and bring every scene to life with creativity!', 'eggs-shop' ),
			'url'         => 'http://eggstime.com/upload/index.html',
			'image'       => $images . '/coloring-time-card.jpg',
			'icon'        => 'palette',
			'tone'        => 'pink',
			'panel'       => '#fff0f6',
			'accent'      => '#e91e8c',
		),
		array(
			'slug'        => 'puzzle-time',
			'title'       => __( 'Puzzle Time', 'eggs-shop' ),
			'description' => __( 'Solve playful puzzles that build focus, logic, and problem-solving skills!', 'eggs-shop' ),
			'url'         => 'http://eggstime.com/upload/puzzles/index.html',
			'image'       => $images . '/puzzle-time-card.jpg',
			'icon'        => 'puzzle',
			'tone'        => 'green',
			'panel'       => '#eaf8ef',
			'accent'      => '#27ae60',
		),
	);
}

/**
 * App promo game thumbnails.
 *
 * @param string $theme_uri Theme directory URI.
 * @return array<int, array<string, string>>
 */
function et_home_get_fun_egg_app_games( $theme_uri = '' ) {
	if ( ! $theme_uri && function_exists( 'get_template_directory_uri' ) ) {
		$theme_uri = get_template_directory_uri();
	}

	$base = $theme_uri . '/images/games';

	return array(
		array(
			'label' => __( 'Happy Egg game', 'eggs-shop' ),
			'url'   => 'https://apps.apple.com/app/id1234567890',
			'image' => $base . '/happy-egg-app.png',
			'tone'  => 'pink',
		),
		array(
			'label' => __( 'King Egg game', 'eggs-shop' ),
			'url'   => 'https://play.google.com/store/apps/details?id=com.eggstime',
			'image' => $base . '/king-egg-app.png',
			'tone'  => 'blue',
		),
		array(
			'label' => __( 'Magik Egg game', 'eggs-shop' ),
			'url'   => 'https://apps.apple.com/app/id1234567890',
			'image' => $base . '/magik-egg-app.png',
			'tone'  => 'purple',
		),
	);
}

/**
 * App store badge links.
 *
 * @param string $theme_uri Theme directory URI.
 * @return array<string, array<string, string>>
 */
function et_home_get_game_app_store_badges( $theme_uri = '' ) {
	if ( ! $theme_uri && function_exists( 'get_template_directory_uri' ) ) {
		$theme_uri = get_template_directory_uri();
	}

	return array(
		'app'  => array(
			'label' => __( 'Download on the App Store', 'eggs-shop' ),
			'url'   => 'https://apps.apple.com/app/id1234567890',
			'image' => $theme_uri . '/images/app-store-badge.png',
		),
		'play' => array(
			'label' => __( 'Get it on Google Play', 'eggs-shop' ),
			'url'   => 'https://play.google.com/store/apps/details?id=com.eggstime',
			'image' => $theme_uri . '/images/google-play-badge.png',
		),
	);
}

/**
 * Age rating badge images.
 *
 * @param string $theme_uri Theme directory URI.
 * @return array<int, string>
 */
function et_home_get_game_rating_badge_images( $theme_uri = '' ) {
	if ( ! $theme_uri && function_exists( 'get_template_directory_uri' ) ) {
		$theme_uri = get_template_directory_uri();
	}

	return array(
		$theme_uri . '/images/rating-3plus.png',
		$theme_uri . '/images/rating-4plus.png',
		$theme_uri . '/images/rating-everyone.png',
	);
}

/**
 * Inline SVG icon for a fun-egg game card.
 *
 * @param string $icon Icon key.
 * @return string
 */
function et_home_get_fun_egg_game_card_icon( $icon ) {
	$icons = array(
		'maze' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h3v8H8zM13 8h3v3h-3zM13 16h3v-3h-3z"/></svg>',
		'chat' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-1.9 5.4 8.5 8.5 0 0 1-6.6 3.1 8.4 8.4 0 0 1-3.9-1L3 21l1.9-5.6a8.4 8.4 0 0 1-1-3.9 8.5 8.5 0 0 1 3.1-6.6A8.4 8.4 0 0 1 12 3a8.5 8.5 0 0 1 5.5 2 8.4 8.4 0 0 1 3 6.5z"/><path d="M9.5 11h.01M12 11h.01M14.5 11h.01"/></svg>',
		'palette' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22a10 10 0 1 0-8.6-15"/><circle cx="8.5" cy="10.5" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="15.5" cy="10.5" r="1"/><circle cx="10" cy="14.5" r="1"/></svg>',
		'puzzle' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4h2v3h3a2 2 0 0 1 2 2v2h-3v2h3v2a2 2 0 0 1-2 2h-3v3H11v-3H8a2 2 0 0 1-2-2v-2h3v-2H6v-2a2 2 0 0 1 2-2h3z"/></svg>',
	);

	return isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['maze'];
}
