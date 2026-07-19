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
			'description' => __( 'Help the egg find its way through fun mazes and reach the destination!', 'eggs-shop' ),
			'url'         => 'http://eggstime.com/upload/mazes/index.html',
			'image'       => $uploads . '/fun-egg-game-maze-time.jpg',
			'icon'        => 'maze',
			'tone'        => 'blue',
			'panel'       => '#e8f3fc',
			'accent'      => '#1a9fe0',
		),
		array(
			'slug'        => 'magical-words',
			'title'       => __( 'Magical Words', 'eggs-shop' ),
			'description' => __( 'Learn good manners, polite words, and everyday etiquette through fun activities!', 'eggs-shop' ),
			'url'         => $games_url,
			'image'       => $uploads . '/fun-egg-game-magical-words.jpg',
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
	if ( ! function_exists( 'et_home_icon' ) ) {
		require_once get_template_directory() . '/inc/home-icons.php';
	}

	$icon_map = array(
		'maze'    => 'game-maze',
		'chat'    => 'game-chat',
		'palette' => 'game-palette',
		'puzzle'  => 'game-puzzle',
	);

	$icon_key = isset( $icon_map[ $icon ] ) ? $icon_map[ $icon ] : 'game-maze';

	return et_home_icon( $icon_key );
}
