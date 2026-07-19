<?php
/**
 * Home v2 — inline SVG icons (CSP nonce aware).
 *
 * @package EggsShop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve CSP nonce for inline SVG output when the host enforces script-src nonces.
 *
 * @return string
 */
function et_home_get_inline_svg_nonce() {
	static $resolved = false;
	static $nonce    = '';

	if ( $resolved ) {
		return $nonce;
	}

	$resolved = true;
	$nonce    = (string) apply_filters( 'et_home_inline_svg_nonce', '' );

	if ( '' !== $nonce ) {
		return $nonce;
	}

	if ( defined( 'CSP_NONCE' ) && CSP_NONCE ) {
		$nonce = (string) CSP_NONCE;
		return $nonce;
	}

	if ( function_exists( 'wp_script_attributes' ) ) {
		$attrs = wp_script_attributes(
			array(
				'type' => 'text/javascript',
			)
		);

		if ( is_string( $attrs ) && preg_match( '/\bnonce=["\']([^"\']+)["\']/', $attrs, $matches ) ) {
			$nonce = $matches[1];
		}
	}

	return $nonce;
}

/**
 * Add CSP nonce + safe defaults to an inline SVG string.
 *
 * @param string $svg Raw SVG markup.
 * @return string
 */
function et_home_inline_svg( $svg ) {
	if ( ! is_string( $svg ) || '' === $svg ) {
		return '';
	}

	$nonce = et_home_get_inline_svg_nonce();

	if ( $nonce && false === stripos( $svg, 'nonce=' ) ) {
		$svg = preg_replace( '/<svg\b/i', '<svg nonce="' . esc_attr( $nonce ) . '"', $svg, 1 );
	}

	if ( false === stripos( $svg, 'aria-hidden=' ) ) {
		$svg = preg_replace( '/<svg\b/i', '<svg aria-hidden="true"', $svg, 1 );
	}

	if ( false === stripos( $svg, 'focusable=' ) ) {
		$svg = preg_replace( '/<svg\b/i', '<svg focusable="false"', $svg, 1 );
	}

	return $svg;
}

/**
 * Return a named home inline SVG icon.
 *
 * @param string $name Icon key.
 * @return string
 */
function et_home_icon( $name ) {
	$icons = array(
		'mobile'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M12 18h.01"/></svg>',
		'heart'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
		'arrow-right' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h12M13 7l5 5-5 5"/></svg>',
		'game-maze'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 8h3v8H8zM13 8h3v3h-3zM13 16h3v-3h-3z"/></svg>',
		'game-chat'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-1.9 5.4 8.5 8.5 0 0 1-6.6 3.1 8.4 8.4 0 0 1-3.9-1L3 21l1.9-5.6a8.4 8.4 0 0 1-1-3.9 8.5 8.5 0 0 1 3.1-6.6A8.4 8.4 0 0 1 12 3a8.5 8.5 0 0 1 5.5 2 8.4 8.4 0 0 1 3 6.5z"/><path d="M9.5 11h.01M12 11h.01M14.5 11h.01"/></svg>',
		'game-palette'=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a10 10 0 1 0-8.6-15"/><circle cx="8.5" cy="10.5" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="15.5" cy="10.5" r="1"/><circle cx="10" cy="14.5" r="1"/></svg>',
		'game-puzzle' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4h2v3h3a2 2 0 0 1 2 2v2h-3v2h3v2a2 2 0 0 1-2 2h-3v3H11v-3H8a2 2 0 0 1-2-2v-2h3v-2H6v-2a2 2 0 0 1 2-2h3z"/></svg>',
	);

	$key = isset( $icons[ $name ] ) ? $name : 'arrow-right';

	return et_home_inline_svg( $icons[ $key ] );
}
