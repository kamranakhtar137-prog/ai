<?php
/**
 * Home v2 — Fun in Every Egg (game cards slider + app promo)
 *
 * @package EggsShop
 */

if ( ! function_exists( 'et_get_home_core_egg_brand_meta' ) ) {
	require_once get_template_directory() . '/inc/home-characters.php';
}

if ( ! function_exists( 'et_home_icon' ) ) {
	require_once get_template_directory() . '/inc/home-icons.php';
}

if ( ! function_exists( 'et_home_get_fun_egg_game_cards' ) ) {
	require_once get_template_directory() . '/inc/home-games.php';
}

$theme_uri                      = get_template_directory_uri();
$brand_meta                     = et_get_home_core_egg_brand_meta();
$games_url                      = function_exists( 'get_permalink' ) ? get_permalink( 617 ) : home_url( '/games/' );
$et_home_fun_egg_game_cards     = et_home_get_fun_egg_game_cards( $theme_uri, $games_url );
$et_home_fun_egg_game_count     = count( $et_home_fun_egg_game_cards );
$et_home_fun_egg_app_games      = et_home_get_fun_egg_app_games( $theme_uri );
$et_home_game_store_badges      = et_home_get_game_app_store_badges( $theme_uri );
$et_home_game_rating_badges     = et_home_get_game_rating_badge_images( $theme_uri );

if ( empty( $games_url ) || '#' === $games_url ) {
	$games_url = home_url( '/games/' );
}

$magik_character = $brand_meta['magik']['character_image'];
?>
<section class="et-home__fun-egg et-home__playful-section" id="et-home-fun-egg" aria-labelledby="et-home-fun-egg-title">
	<div class="et-home__fun-egg-bg" aria-hidden="true"></div>
	<div class="et-home__section-inner center">
		<header class="et-home__fun-egg-head">
			<p class="et-home__section-kicker et-home__section-kicker--stars et-home__fun-egg-kicker">
				<span class="et-home__section-star" aria-hidden="true">★</span>
				<?php esc_html_e( 'To Play, Learn & Discover', 'eggs-shop' ); ?>
				<span class="et-home__section-star" aria-hidden="true">★</span>
			</p>
			<h2 class="et-home__fun-egg-title" id="et-home-fun-egg-title">
				<span class="et-home__fun-egg-title-line"><?php esc_html_e( 'Fun in', 'eggs-shop' ); ?></span>
				<span class="et-home__fun-egg-title-line et-home__fun-egg-title-line--accent"><?php esc_html_e( 'Every Egg!', 'eggs-shop' ); ?></span>
			</h2>
			<p class="et-home__fun-egg-lead et-home__fun-egg-lead--hearts">
				<span class="et-home__fun-egg-heart" aria-hidden="true">♥</span>
				<?php esc_html_e( 'Games and fun activities make learning exciting — inside and outside the egg!', 'eggs-shop' ); ?>
				<span class="et-home__fun-egg-heart" aria-hidden="true">♥</span>
			</p>
		</header>

		<?php if ( $et_home_fun_egg_game_count >= 2 ) : ?>
		<div class="et-home__fun-egg-games-cards-slider-wrap et-home__fun-egg-games-cards-slider-wrap--count-<?php echo esc_attr( $et_home_fun_egg_game_count ); ?>">
			<ul
				class="et-home__fun-egg-games-cards et-home__fun-egg-games-cards-slider"
				data-et-game-count="<?php echo esc_attr( $et_home_fun_egg_game_count ); ?>"
			>
				<?php foreach ( $et_home_fun_egg_game_cards as $game ) : ?>
					<li class="et-home__fun-egg-games-cards-item">
						<article
							class="et-home__fun-egg-game-card et-home__fun-egg-game-card--<?php echo esc_attr( $game['tone'] ); ?>"
							style="--et-fun-egg-game-panel: <?php echo esc_attr( $game['panel'] ); ?>; --et-fun-egg-game-accent: <?php echo esc_attr( $game['accent'] ); ?>;"
						>
							<div class="et-home__fun-egg-game-card-media">
								<img
									src="<?php echo esc_url( $game['image'] ); ?>"
									alt="<?php echo esc_attr( $game['title'] ); ?>"
									class="et-home__fun-egg-game-card-image"
									loading="lazy"
									decoding="async"
								/>
							</div>

							<div class="et-home__fun-egg-game-card-body">
								<div class="et-home__fun-egg-game-card-head">
									<span class="et-home__fun-egg-game-card-icon" aria-hidden="true">
										<?php echo et_home_get_fun_egg_game_card_icon( $game['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
									<h3 class="et-home__fun-egg-game-card-title"><?php echo esc_html( $game['title'] ); ?></h3>
								</div>

								<p class="et-home__fun-egg-game-card-desc"><?php echo esc_html( $game['description'] ); ?></p>

								<a
									href="<?php echo esc_url( $game['url'] ); ?>"
									class="et-home__fun-egg-game-card-btn"
									target="_blank"
									rel="noopener noreferrer"
								>
									<span class="et-home__fun-egg-game-card-btn-label"><?php esc_html_e( 'Play Now', 'eggs-shop' ); ?></span>
									<span class="et-home__fun-egg-game-card-btn-icon" aria-hidden="true">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
											<path d="M5 12h12M13 7l5 5-5 5"></path>
										</svg>
									</span>
								</a>
							</div>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<div class="et-home__fun-egg-app">
			<div class="et-home__fun-egg-app-panel">
				<div class="et-home__fun-egg-app-stage">
					<div class="et-home__fun-egg-app-left">
						<div class="et-home__fun-egg-app-copy">
							<div class="et-home__fun-egg-app-kicker">
								<span class="et-home__fun-egg-app-kicker-icon" aria-hidden="true">
									<?php echo et_home_icon( 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="et-home__fun-egg-app-kicker-text">
									<span class="et-home__fun-egg-app-kicker-line"><?php esc_html_e( 'Continue Learning with the', 'eggs-shop' ); ?></span>
									<span class="et-home__fun-egg-app-kicker-line"><?php esc_html_e( 'Eggs Time App', 'eggs-shop' ); ?></span>
								</span>
								<div class="et-home__fun-egg-app-text">
									<p class="et-home__fun-egg-app-summary"><?php esc_html_e( 'Play educational games with your favorite egg characters anytime, anywhere!', 'eggs-shop' ); ?></p>
									<p class="et-home__fun-egg-app-text-line et-home__fun-egg--desktop-detail">
										<span class="et-home__fun-egg-app-heart" aria-hidden="true">
											<?php echo et_home_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
										<?php esc_html_e( 'Play exciting educational games inspired', 'eggs-shop' ); ?>
									</p>
									<p class="et-home__fun-egg-app-text-line et-home__fun-egg--desktop-detail">
										<?php esc_html_e( 'by your favorite egg characters anytime,', 'eggs-shop' ); ?>
									</p>
									<p class="et-home__fun-egg-app-text-line et-home__fun-egg--desktop-detail">
										<span class="et-home__fun-egg-app-heart" aria-hidden="true">
											<?php echo et_home_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
										<?php esc_html_e( 'anywhere!', 'eggs-shop' ); ?>
										<span class="et-home__fun-egg-app-heart" aria-hidden="true">
											<?php echo et_home_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</span>
									</p>
								</div>
							</div>
						</div>
						<img
							src="<?php echo esc_url( $magik_character ); ?>"
							alt=""
							class="et-home__fun-egg-app-character et-home__fun-egg-app-character--left"
							loading="lazy"
							decoding="async"
						/>
					</div>

					<div class="et-home__fun-egg-app-games-slider-wrap">
						<ul class="et-home__fun-egg-app-games et-home__fun-egg-app-games-slider">
							<?php foreach ( $et_home_fun_egg_app_games as $game ) : ?>
								<li class="et-home__fun-egg-app-game-item">
									<a href="<?php echo esc_url( $game['url'] ); ?>" class="et-home__fun-egg-app-game et-home__fun-egg-app-game--<?php echo esc_attr( $game['tone'] ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="et-home__fun-egg-app-game-media">
											<img
												src="<?php echo esc_url( $game['image'] ); ?>"
												alt="<?php echo esc_attr( $game['label'] ); ?>"
												loading="lazy"
												decoding="async"
											/>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</div>

				<div class="et-home__fun-egg-app-stores">
					<a
						href="<?php echo esc_url( $et_home_game_store_badges['app']['url'] ); ?>"
						class="et-home__fun-egg-store-link et-home__fun-egg-store-link--app"
						target="_blank"
						rel="noopener noreferrer"
					>
						<img
							src="<?php echo esc_url( $et_home_game_store_badges['app']['image'] ); ?>"
							alt="<?php echo esc_attr( $et_home_game_store_badges['app']['label'] ); ?>"
							loading="lazy"
							decoding="async"
						/>
					</a>
					<a
						href="<?php echo esc_url( $et_home_game_store_badges['play']['url'] ); ?>"
						class="et-home__fun-egg-store-link et-home__fun-egg-store-link--play"
						target="_blank"
						rel="noopener noreferrer"
					>
						<img
							src="<?php echo esc_url( $et_home_game_store_badges['play']['image'] ); ?>"
							alt="<?php echo esc_attr( $et_home_game_store_badges['play']['label'] ); ?>"
							loading="lazy"
							decoding="async"
						/>
					</a>
				</div>

				<div class="et-home__fun-egg-ratings" aria-hidden="true">
					<?php foreach ( $et_home_game_rating_badges as $badge_image ) : ?>
						<span class="et-home__fun-egg-ratings-badge">
							<img
								src="<?php echo esc_url( $badge_image ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
							/>
						</span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</section>
