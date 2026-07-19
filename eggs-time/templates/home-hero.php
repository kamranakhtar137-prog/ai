<?php
/**
 * Home v2 — hero / banner section
 *
 * Set to false to revert to the static image banner.
 */
$et_home_hero_use_video       = true;
$et_home_hero_video_url       = 'http://eggstime.com/wp-content/uploads/2026/07/51be273fce2746bcbecc5fda78856c43.mp4';
/* Poster shown before play, while buffering, or if playback fails. */
$et_home_hero_video_poster    = 'http://eggstime.com/wp-content/uploads/2026/07/Baber_girl_650x650.png';
$et_home_hero_poster_width    = 800;
$et_home_hero_poster_height   = 600;
$et_home_hero_video_width     = 800;
$et_home_hero_video_height    = 600;
$et_home_hero_bg              = 'http://eggstime.com/wp-content/uploads/2026/07/800Х600.jpg.jpeg';
$et_home_hero_btn_arrow       = '<svg class="et-home__hero-btn-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="none" d="M8 5l8 7-8 7" stroke="#098BE5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

$et_home_hero_video_style = sprintf(
	'--et-home-hero-poster-aspect-ratio: %1$d / %2$d; --et-home-hero-video-aspect-ratio: %3$d / %4$d; --et-home-hero-video-ar-w: %3$d; --et-home-hero-video-ar-h: %4$d;',
	(int) $et_home_hero_poster_width,
	(int) $et_home_hero_poster_height,
	(int) $et_home_hero_video_width,
	(int) $et_home_hero_video_height
);

if ( $et_home_hero_use_video ) :
	?>
<section
	class="et-home__hero et-home__hero--split-video et-home__playful-section"
	aria-label="<?php esc_attr_e( 'Discover the Fun Inside Every Egg', 'eggs-shop' ); ?>"
	data-hero-video="<?php echo esc_url( $et_home_hero_video_url ); ?>"
>
	<div class="et-home__hero-inner center">
		<div class="et-home__hero-grid">
			<div class="et-home__hero-content">
				<p class="et-home__hero-badge et-home__hero--desktop-detail">
					<span class="et-home__hero-badge-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
							<path d="M12 2l2.9 6.9 7.5.6-5.7 4.9 1.7 7.3L12 18.8 5.6 21.7l1.7-7.3L1.6 9.5l7.5-.6L12 2z"/>
						</svg>
					</span>
					Welcome to the Eggs Universe
				</p>

				<h1 class="et-home__hero-title">
					<span class="et-home__hero-title-line">Discover the Fun</span>
					<span class="et-home__hero-title-line">
						Inside <span class="et-home__hero-title-accent">Every Egg!</span>
					</span>
				</h1>

				<p class="et-home__hero-text et-home__hero--desktop-detail">
					Discover educational surprises, interactive games, fun videos and lovable characters that inspire children to play, learn and grow.
				</p>
				<p class="et-home__hero-text et-home__hero-text--mobile">
					Educational surprises, interactive games, fun videos and lovable characters.
				</p>

				<div class="et-home__hero-actions">
					<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="et-home__hero-btn et-home__hero-btn--primary">
						<span class="et-home__hero-btn-label et-home__hero-btn-label--desktop">Explore Our Eggs</span>
						<span class="et-home__hero-btn-label et-home__hero-btn-label--mobile">Explore Eggs</span>
						<span class="et-home__hero-btn-icon" aria-hidden="true">
							<?php echo $et_home_hero_btn_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					</a>

					<a href="#et-home-characters" class="et-home__hero-btn et-home__hero-btn--secondary">
						<span class="et-home__hero-btn-label et-home__hero-btn-label--desktop">Meet Our Characters</span>
						<span class="et-home__hero-btn-label et-home__hero-btn-label--mobile">Meet Characters</span>
						<span class="et-home__hero-btn-icon" aria-hidden="true">
							<?php echo $et_home_hero_btn_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					</a>
				</div>

				<ul class="et-home__hero-trust et-home__hero--desktop-detail">
					<li class="et-home__hero-trust-item">
						<span class="et-home__hero-trust-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
								<path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
								<path d="M8 7h8"/>
								<path d="M8 11h6"/>
							</svg>
						</span>
						<span class="et-home__hero-trust-copy">
							<span class="et-home__hero-trust-label">Educational</span>
							<span class="et-home__hero-trust-desc">Learning made fun</span>
						</span>
					</li>
					<li class="et-home__hero-trust-item">
						<span class="et-home__hero-trust-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
								<path d="M9 12l2 2 4-4"/>
							</svg>
						</span>
						<span class="et-home__hero-trust-copy">
							<span class="et-home__hero-trust-label">Safe &amp; Certified</span>
							<span class="et-home__hero-trust-desc">Trusted by parents</span>
						</span>
					</li>
					<li class="et-home__hero-trust-item">
						<span class="et-home__hero-trust-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
								<path d="M5 19l1 3 1-3 3-1-3-1-1-3-1 3-3 1 3 1z"/>
								<path d="M19 13l.8 2.4L22 16l-2.2.6L19 19l-.8-2.4L16 16l2.2-.6L19 13z"/>
							</svg>
						</span>
						<span class="et-home__hero-trust-copy">
							<span class="et-home__hero-trust-label">Fun &amp; Engaging</span>
							<span class="et-home__hero-trust-desc">Kids love it!</span>
						</span>
					</li>
				</ul>
			</div>

			<div class="et-home__hero-media">
				<div class="et-home__hero-video-stack">
					<div class="et-home__hero-video-frame">
						<div
							class="et-home__hero-video-wrap"
							style="<?php echo esc_attr( $et_home_hero_video_style ); ?>"
						>
							<video
								class="et-home__hero-video"
								src="<?php echo esc_url( $et_home_hero_video_url ); ?>"
								width="<?php echo (int) $et_home_hero_video_width; ?>"
								height="<?php echo (int) $et_home_hero_video_height; ?>"
								data-video-width="<?php echo (int) $et_home_hero_video_width; ?>"
								data-video-height="<?php echo (int) $et_home_hero_video_height; ?>"
								muted
								loop
								playsinline
								preload="metadata"
								poster="<?php echo esc_url( $et_home_hero_video_poster ); ?>"
							></video>
							<img
								class="et-home__hero-video-fallback"
								src="<?php echo esc_url( $et_home_hero_video_poster ); ?>"
								alt=""
								width="<?php echo (int) $et_home_hero_poster_width; ?>"
								height="<?php echo (int) $et_home_hero_poster_height; ?>"
								loading="eager"
								decoding="async"
							/>
							<button
								type="button"
								class="et-home__hero-video-play"
								aria-label="<?php esc_attr_e( 'Play video', 'eggs-shop' ); ?>"
							>
								<span class="et-home__hero-video-play-icon et-home__hero-video-play-icon--play" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
										<path d="M8 5v14l11-7z"/>
									</svg>
								</span>
								<span class="et-home__hero-video-play-icon et-home__hero-video-play-icon--pause" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
										<path d="M6 5h4v14H6V5zm8 0h4v14h-4V5z"/>
									</svg>
								</span>
							</button>
							<button
								type="button"
								class="et-home__hero-video-sound"
								aria-label="Turn sound on"
								aria-pressed="false"
							>
								<span class="et-home__hero-video-sound-icon et-home__hero-video-sound-icon--off" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M11 5 6 9H3v6h3l5 4V5z"/>
										<line x1="23" y1="9" x2="17" y2="15"/>
										<line x1="17" y1="9" x2="23" y2="15"/>
									</svg>
								</span>
								<span class="et-home__hero-video-sound-icon et-home__hero-video-sound-icon--on" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M11 5 6 9H3v6h3l5 4V5z"/>
										<path d="M15.54 8.46a5 5 0 010 7.07"/>
										<path d="M19.07 4.93a10 10 0 010 14.14"/>
									</svg>
								</span>
								<span class="et-home__hero-video-sound-label">Sound On</span>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
	<?php
else :
	?>
<section
		class="et-home__hero et-home__playful-section"
		aria-label="<?php esc_attr_e( 'Discover the Fun Inside Every Egg', 'eggs-shop' ); ?>"
	>
		<div class="et-home__hero-overlay" aria-hidden="true"></div>
		<div class="et-home__hero-inner center">
			<div class="et-home__hero-content">
				<p class="et-home__hero-badge">
					<span class="et-home__hero-badge-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
							<path d="M12 2l2.9 6.9 7.5.6-5.7 4.9 1.7 7.3L12 18.8 5.6 21.7l1.7-7.3L1.6 9.5l7.5-.6L12 2z"/>
						</svg>
					</span>
					Welcome to the Eggs Universe
				</p>

				<h1 class="et-home__hero-title">
					<span class="et-home__hero-title-line">Discover the Fun</span>
					<span class="et-home__hero-title-line">
						Inside <span class="et-home__hero-title-accent">Every Egg!</span>
					</span>
				</h1>

				<p class="et-home__hero-text">
					Discover educational surprises, interactive games, fun videos and lovable characters that inspire children to play, learn and grow.
				</p>

				<div class="et-home__hero-actions">
					<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>" class="et-home__hero-btn et-home__hero-btn--primary">
						<span class="et-home__hero-btn-label">Explore Our Eggs</span>
						<span class="et-home__hero-btn-icon" aria-hidden="true">
							<?php echo $et_home_hero_btn_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					</a>

					<a href="#et-home-characters" class="et-home__hero-btn et-home__hero-btn--secondary">
						<span class="et-home__hero-btn-label">Meet Our Characters</span>
						<span class="et-home__hero-btn-icon" aria-hidden="true">
							<?php echo $et_home_hero_btn_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					</a>
				</div>

				<div class="et-home__hero-trust">
					<div class="et-home__hero-trust-item">
						<span class="et-home__hero-trust-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
								<path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
								<path d="M8 7h8"/>
								<path d="M8 11h6"/>
							</svg>
						</span>
						<span class="et-home__hero-trust-label">Educational</span>
					</div>
					<span class="et-home__hero-trust-dot" aria-hidden="true">·</span>
					<div class="et-home__hero-trust-item">
						<span class="et-home__hero-trust-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
								<path d="M9 12l2 2 4-4"/>
							</svg>
						</span>
						<span class="et-home__hero-trust-label">Safe &amp; Certified</span>
					</div>
					<span class="et-home__hero-trust-dot" aria-hidden="true">·</span>
					<div class="et-home__hero-trust-item">
						<span class="et-home__hero-trust-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
								<path d="M5 19l1 3 1-3 3-1-3-1-1-3-1 3-3 1 3 1z"/>
								<path d="M19 13l.8 2.4L22 16l-2.2.6L19 19l-.8-2.4L16 16l2.2-.6L19 13z"/>
							</svg>
						</span>
						<span class="et-home__hero-trust-label">Fun &amp; Engaging</span>
					</div>
				</div>
			</div>
		</div>

		<div class="et-home__hero-packaging">
			<img
				src="<?php echo esc_url( home_url( '/wp-content/uploads/eggs-time/product-box.png' ) ); ?>"
				alt="<?php esc_attr_e( 'Eggs Time surprise egg product packaging', 'eggs-shop' ); ?>"
				class="et-home__hero-packaging-img"
				width="200"
				height="auto"
			/>
		</div>
	</section>
	<?php
endif;
