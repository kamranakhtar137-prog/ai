<?php
/**
 * Admin product settings.
 *
 * @package WCBedConfigurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCBC_Admin {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_regenerate_import_token' ) );
	}

	/**
	 * Regenerate import token when requested from product admin.
	 */
	public static function maybe_regenerate_import_token() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['wcbc_regenerate_token'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'wcbc_regenerate_import_token' );

		if ( class_exists( 'WCBC_Cache_API' ) ) {
			WCBC_Cache_API::import_token( true );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$redirect = $post_id
			? add_query_arg(
				array(
					'post'             => $post_id,
					'action'           => 'edit',
					'wcbc_token_reset' => '1',
				),
				admin_url( 'post.php' )
			)
			: admin_url( 'edit.php?post_type=product' );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Enqueue media library scripts on product edit screen.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wcbc-admin', WCBC_PLUGIN_URL . 'assets/css/admin.css', array(), WCBC_VERSION );
		wp_enqueue_script( 'wcbc-admin-media', WCBC_PLUGIN_URL . 'assets/js/admin-media.js', array( 'jquery' ), WCBC_VERSION, true );
	}

	/**
	 * Add product data tab.
	 *
	 * @param array<string,array<string,mixed>> $tabs Tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public static function add_tab( $tabs ) {
		$tabs['wcbc'] = array(
			'label'    => __( 'Bed Configurator', 'wc-bed-configurator' ),
			'target'   => 'wcbc_product_data',
			'class'    => array( 'show_if_simple' ),
			'priority' => 65,
		);
		return $tabs;
	}

	/**
	 * Render admin panel.
	 */
	public static function render_panel() {
		global $post;
		$enabled = get_post_meta( $post->ID, WCBC_Config::ENABLED_KEY, true ) === 'yes';
		$config  = WCBC_Config::get_product_config( $post->ID );
		$import_all_script  = WCBC_Cache_API::import_all_script();
		$import_core_script = WCBC_Cache_API::import_core_script();
		$image_source       = WCBC_Config::get_image_source( $post->ID );
		$layer_labels       = WCBC_Config::get_layer_labels();
		$variation_media    = WCBC_Config::get_variation_layer_media( $post->ID );
		$size_options       = WCBC_Config::size_options_for_admin( $config );
		$colour_options     = WCBC_Config::colour_options_for_admin( $config );
		?>
		<div id="wcbc_product_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => 'wcbc_enabled',
						'label'       => __( 'Enable Bed Configurator', 'wc-bed-configurator' ),
						'description' => __( 'Replace the standard add-to-cart area with the BYOB configurator.', 'wc-bed-configurator' ),
						'value'       => $enabled ? 'yes' : 'no',
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'                => 'wcbc_base_price',
						'label'             => __( 'Configurator base price', 'wc-bed-configurator' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' => '0.01',
							'min'  => '0',
						),
						'value'             => $config['base_price'],
						'description'       => __( 'Base price before option modifiers. Leave empty to use product regular price.', 'wc-bed-configurator' ),
					)
				);
				woocommerce_wp_select(
					array(
						'id'          => 'wcbc_image_source',
						'label'       => __( 'Preview image source', 'wc-bed-configurator' ),
						'value'       => $image_source,
						'options'     => array(
							'media'     => __( 'Media Library (recommended)', 'wc-bed-configurator' ),
							'demo'      => __( 'Bundled demo layers', 'wc-bed-configurator' ),
							'happybeds' => __( 'Happy Beds CDN / cache (advanced)', 'wc-bed-configurator' ),
						),
						'description' => __( 'Assign a base layer set per size, then colour-specific fabric layers for each size + colour combination.', 'wc-bed-configurator' ),
					)
				);
				?>
				<div class="wcbc-variation-layers-section options_group" <?php echo 'media' === $image_source ? '' : 'style="display:none"'; ?>>
					<p class="form-field">
						<strong><?php esc_html_e( 'Preview layers by size & colour', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description">
							<?php esc_html_e( 'Each size has a base layer set (shadow, legs, etc.). Under each size, assign colour-specific images (base, headboard, storage) for that size only — e.g. Super King Blue Marine and Super King Black are separate.', 'wc-bed-configurator' ); ?>
						</span>
					</p>
					<?php self::render_size_colour_layer_sets( $size_options, $colour_options, WCBC_Config::headboard_options_for_admin( $config ), $variation_media, $layer_labels ); ?>
				</div>
				<div class="options_group">
					<p class="form-field">
						<strong><?php esc_html_e( 'Option swatch images', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description"><?php esc_html_e( 'Thumbnails shown in the configurator accordion for each size, colour, headboard, etc.', 'wc-bed-configurator' ); ?></span>
					</p>
					<?php foreach ( $config['groups'] as $group ) : ?>
						<h4 style="margin:16px 0 8px;padding:0 12px;"><?php echo esc_html( $group['label'] ); ?> <?php esc_html_e( 'swatches', 'wc-bed-configurator' ); ?></h4>
						<table class="wcbc-option-media-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Option', 'wc-bed-configurator' ); ?></th>
									<th><?php esc_html_e( 'Swatch', 'wc-bed-configurator' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'wc-bed-configurator' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $group['options'] as $option ) : ?>
									<?php
									$img_url = ! empty( $option['image'] ) ? $option['image'] : '';
									$img_id  = $img_url ? attachment_url_to_postid( $img_url ) : 0;
									?>
									<tr>
										<td><?php echo esc_html( trim( $option['label'] . ' ' . $option['sublabel'] ) ); ?></td>
										<td>
											<img class="wcbc-option-thumb <?php echo $img_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $img_url ); ?>" alt="" />
										</td>
										<td>
											<input type="hidden" class="wcbc-option-image-id" name="wcbc_option_images[<?php echo esc_attr( $group['id'] ); ?>][<?php echo esc_attr( $option['id'] ); ?>]" value="<?php echo esc_attr( $img_id ); ?>" />
											<button type="button" class="button wcbc-upload-option-image" data-group="<?php echo esc_attr( $group['id'] ); ?>" data-option="<?php echo esc_attr( $option['id'] ); ?>">
												<?php esc_html_e( 'Select', 'wc-bed-configurator' ); ?>
											</button>
											<button type="button" class="button wcbc-remove-option-image" data-group="<?php echo esc_attr( $group['id'] ); ?>" data-option="<?php echo esc_attr( $option['id'] ); ?>" <?php echo $img_id ? '' : 'style="display:none"'; ?>>
												<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endforeach; ?>
				</div>
				<details class="wcbc-advanced-panel" style="margin:16px 12px;">
					<summary style="cursor:pointer;font-weight:600;"><?php esc_html_e( 'Advanced: Happy Beds import (optional)', 'wc-bed-configurator' ); ?></summary>
					<p class="form-field">
						<?php esc_html_e( 'Only needed if Preview image source is set to Happy Beds. Paste on happybeds.co.uk Console.', 'wc-bed-configurator' ); ?>
					</p>
					<textarea id="wcbc_import_core_script" readonly rows="6" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea( $import_core_script ); ?></textarea>
					<button type="button" class="button" id="wcbc-copy-import-core-script"><?php esc_html_e( 'Copy core import', 'wc-bed-configurator' ); ?></button>
					<textarea id="wcbc_import_all_script" readonly rows="6" style="width:100%;font-family:monospace;font-size:11px;margin-top:12px;"><?php echo esc_textarea( $import_all_script ); ?></textarea>
					<button type="button" class="button" id="wcbc-copy-import-all-script"><?php esc_html_e( 'Copy full import', 'wc-bed-configurator' ); ?></button>
					<?php
					$regen_url = wp_nonce_url(
						add_query_arg(
							array(
								'post'                  => $post->ID,
								'action'                => 'edit',
								'wcbc_regenerate_token' => '1',
							),
							admin_url( 'post.php' )
						),
						'wcbc_regenerate_import_token'
					);
					?>
					<p><a href="<?php echo esc_url( $regen_url ); ?>" class="button"><?php esc_html_e( 'Regenerate import token', 'wc-bed-configurator' ); ?></a></p>
				</details>
			</div>
		</div>
		<script>
		(function(){
			document.getElementById('wcbc-copy-import-core-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_core_script', 'Core import copied.');
			});
			document.getElementById('wcbc-copy-import-all-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_all_script', 'Full import copied.');
			});
			function copyTextarea(id, msg) {
				var ta = document.getElementById(id);
				if (!ta) return;
				ta.select();
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(ta.value).then(function(){ alert(msg); });
				} else {
					document.execCommand('copy');
					alert(msg);
				}
			}
		})();
		</script>
		<?php
	}

	/**
	 * Render per-size base layers with nested colour layers for that size.
	 *
	 * @param array<int,array<string,string>>                    $size_options Size options.
	 * @param array<int,array<string,string>>                    $colour_options Colour options.
	 * @param array<int,array<string,string>>                    $headboard_options Headboard options.
	 * @param array{size:array<string,array<string,mixed>>,colour:array<string,array<string,array<string,int>>>} $saved Saved media.
	 * @param array<string,string>                               $layer_labels Layer labels.
	 */
	private static function render_size_colour_layer_sets( $size_options, $colour_options, $headboard_options, $saved, $layer_labels ) {
		$colour_slots = WCBC_Config::colour_layer_slots();

		foreach ( $size_options as $size ) {
			$size_id    = $size['id'];
			$size_title = trim( $size['label'] . ( ! empty( $size['sublabel'] ) ? ' (' . $size['sublabel'] . ')' : '' ) );
			$size_layers = isset( $saved['size'][ $size_id ] ) ? $saved['size'][ $size_id ] : array();
			$size_headboards = ( ! empty( $size_layers['headboard'] ) && is_array( $size_layers['headboard'] ) ) ? $size_layers['headboard'] : array();
			$size_colours = isset( $saved['colour'][ $size_id ] ) ? $saved['colour'][ $size_id ] : array();
			?>
			<details class="wcbc-variation-layer-set wcbc-size-layer-set">
				<summary><?php echo esc_html( $size_title ); ?></summary>

				<div class="wcbc-size-base-layers">
					<h4><?php esc_html_e( 'Size layers', 'wc-bed-configurator' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Shadow and legs for this size. These stay the same when colour changes.', 'wc-bed-configurator' ); ?></p>
					<div class="wcbc-layer-media-grid">
						<?php foreach ( WCBC_Config::size_base_layer_slots() as $layer ) : ?>
							<?php self::render_layer_picker( 'size', $size_id, '', $layer, $size_layers, $layer_labels, $size_title ); ?>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wcbc-size-headboard-layers">
					<h4><?php esc_html_e( 'Headboard styles for this size', 'wc-bed-configurator' ); ?></h4>
					<p class="description"><?php esc_html_e( 'One preview image per headboard style (Victor Plain, Cornell Lined, etc.) for this size.', 'wc-bed-configurator' ); ?></p>
					<div class="wcbc-layer-media-grid">
						<?php foreach ( $headboard_options as $headboard ) : ?>
							<?php
							$headboard_id    = $headboard['id'];
							$headboard_title = trim( $headboard['label'] . ( ! empty( $headboard['sublabel'] ) ? ' ' . $headboard['sublabel'] : '' ) );
							$attachment_id   = isset( $size_headboards[ $headboard_id ] ) ? (int) $size_headboards[ $headboard_id ] : 0;
							$preview_url     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
							$input_name      = 'wcbc_variation_layers[size][' . esc_attr( $size_id ) . '][headboard][' . esc_attr( $headboard_id ) . ']';
							?>
							<div class="wcbc-layer-media-item">
								<label><?php echo esc_html( $headboard_title ); ?></label>
								<img class="wcbc-layer-media-preview <?php echo $preview_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $preview_url ); ?>" alt="" />
								<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" />
								<button type="button" class="button wcbc-upload-variation-layer" data-title="<?php echo esc_attr( $size_title . ' — ' . $headboard_title ); ?>">
									<?php esc_html_e( 'Select image', 'wc-bed-configurator' ); ?>
								</button>
								<button type="button" class="button wcbc-remove-variation-layer" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
									<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="wcbc-size-colour-layers">
					<h4><?php esc_html_e( 'Colour layers for this size', 'wc-bed-configurator' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Fabric layers for each colour at this size: Storage back, Base, Storage drawer back, Storage drawer front, and Headboard.', 'wc-bed-configurator' ); ?></p>
					<?php foreach ( $colour_options as $colour ) : ?>
						<?php
						$colour_id    = $colour['id'];
						$colour_title = trim( $colour['label'] . ( ! empty( $colour['sublabel'] ) ? ' — ' . $colour['sublabel'] : '' ) );
						$colour_layers = isset( $size_colours[ $colour_id ] ) ? $size_colours[ $colour_id ] : array();
						?>
						<details class="wcbc-variation-layer-set wcbc-colour-layer-set">
							<summary><?php echo esc_html( $colour_title ); ?></summary>
							<div class="wcbc-layer-media-grid">
								<?php foreach ( $colour_slots as $layer ) : ?>
									<?php self::render_layer_picker( 'colour', $size_id, $colour_id, $layer, $colour_layers, $layer_labels, $size_title . ' / ' . $colour_title ); ?>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</details>
			<?php
		}
	}

	/**
	 * Render a single layer media picker field.
	 *
	 * @param string               $dimension size|colour.
	 * @param string               $size_id Size id.
	 * @param string               $colour_id Colour id (colour dimension only).
	 * @param string               $layer Layer slug.
	 * @param array<string,int>    $layers Saved layer map.
	 * @param array<string,string> $layer_labels Labels.
	 * @param string               $context_title Context for media frame title.
	 */
	private static function render_layer_picker( $dimension, $size_id, $colour_id, $layer, $layers, $layer_labels, $context_title ) {
		$attachment_id = isset( $layers[ $layer ] ) ? (int) $layers[ $layer ] : 0;
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$label         = isset( $layer_labels[ $layer ] ) ? $layer_labels[ $layer ] : $layer;

		if ( 'colour' === $dimension ) {
			$input_name = 'wcbc_variation_layers[colour][' . esc_attr( $size_id ) . '][' . esc_attr( $colour_id ) . '][' . esc_attr( $layer ) . ']';
		} else {
			$input_name = 'wcbc_variation_layers[size][' . esc_attr( $size_id ) . '][' . esc_attr( $layer ) . ']';
		}
		?>
		<div class="wcbc-layer-media-item">
			<label><?php echo esc_html( $label ); ?></label>
			<img class="wcbc-layer-media-preview <?php echo $preview_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $preview_url ); ?>" alt="" />
			<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" />
			<button type="button" class="button wcbc-upload-variation-layer" data-title="<?php echo esc_attr( $context_title . ' — ' . $label ); ?>">
				<?php esc_html_e( 'Select image', 'wc-bed-configurator' ); ?>
			</button>
			<button type="button" class="button wcbc-remove-variation-layer" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
				<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( $post_id ) {
		$enabled = isset( $_POST['wcbc_enabled'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification
		update_post_meta( $post_id, WCBC_Config::ENABLED_KEY, $enabled );

		if ( isset( $_POST['wcbc_image_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$source = sanitize_text_field( wp_unslash( $_POST['wcbc_image_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( in_array( $source, array( 'media', 'demo', 'auto', 'happybeds', 'hybrid' ), true ) ) {
				update_post_meta( $post_id, WCBC_Config::IMAGE_SOURCE_KEY, $source );
			}
		}

		if ( isset( $_POST['wcbc_variation_layers'] ) && is_array( $_POST['wcbc_variation_layers'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$variation_layers = WCBC_Config::sanitize_variation_layer_media_post( wp_unslash( $_POST['wcbc_variation_layers'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, WCBC_Config::VARIATION_LAYER_MEDIA_KEY, $variation_layers );
		}

		if ( isset( $_POST['wcbc_base_price'] ) && '' !== $_POST['wcbc_base_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$base = (float) wc_clean( wp_unslash( $_POST['wcbc_base_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$config = get_post_meta( $post_id, WCBC_Config::META_KEY, true );
		if ( ! is_array( $config ) || empty( $config['groups'] ) ) {
			$config = WCBC_Config::get_default_config();
		}

		if ( isset( $_POST['wcbc_option_images'] ) && is_array( $_POST['wcbc_option_images'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$config = self::apply_option_images_from_post( $config, $_POST['wcbc_option_images'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( isset( $base ) ) {
			$config['base_price'] = $base;
		}

		if ( ! empty( $config['groups'] ) ) {
			update_post_meta( $post_id, WCBC_Config::META_KEY, $config );
		}
	}

	/**
	 * Apply media library swatch selections to config options.
	 *
	 * @param array<string,mixed> $config Config.
	 * @param array<string,mixed> $posted Posted option image ids.
	 * @return array<string,mixed>
	 */
	private static function apply_option_images_from_post( $config, $posted ) {
		foreach ( $config['groups'] as $gi => $group ) {
			$gid = $group['id'];
			if ( empty( $posted[ $gid ] ) || ! is_array( $posted[ $gid ] ) ) {
				continue;
			}
			foreach ( $group['options'] as $oi => $option ) {
				$oid = $option['id'];
				if ( empty( $posted[ $gid ][ $oid ] ) ) {
					continue;
				}
				$url = wp_get_attachment_image_url( absint( $posted[ $gid ][ $oid ] ), 'full' );
				if ( $url ) {
					$config['groups'][ $gi ]['options'][ $oi ]['image'] = $url;
				}
			}
		}
		return $config;
	}
}
