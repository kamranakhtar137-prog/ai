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
		add_action( 'save_post_product', array( __CLASS__, 'save_post' ), 20, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_regenerate_import_token' ) );
		add_action( 'wp_ajax_wcbc_save_option_swatch', array( __CLASS__, 'ajax_save_option_swatch' ) );
		add_action( 'wp_ajax_wcbc_save_variation_layer', array( __CLASS__, 'ajax_save_variation_layer' ) );
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

		global $post;
		$product_id = ( $post && ! empty( $post->ID ) ) ? (int) $post->ID : 0;

		wp_localize_script(
			'wcbc-admin-media',
			'wcbcAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wcbc_admin_media' ),
				'productId' => $product_id,
				'i18n'      => array(
					'saved'       => __( 'Image saved.', 'wc-bed-configurator' ),
					'removed'     => __( 'Image removed.', 'wc-bed-configurator' ),
					'error'       => __( 'Could not save image. Save the product first, then try again.', 'wc-bed-configurator' ),
					'noProduct'   => __( 'Save the product as a draft first, then upload images.', 'wc-bed-configurator' ),
				),
			)
		);
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
		$swatch_media       = WCBC_Config::get_option_swatch_media( $post->ID );
		$size_options       = WCBC_Config::size_options_for_admin( $config );
		$colour_options     = WCBC_Config::colour_options_for_admin( $config );
		?>
		<div id="wcbc_product_data" class="panel woocommerce_options_panel hidden">
			<input type="hidden" name="wcbc_admin_panel" value="1" />
			<input type="hidden" name="wcbc_variation_layers_save" value="1" />
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
						'description' => __( 'Assign all bed layers per size and colour. Headboard styles also have a separate image per colour.', 'wc-bed-configurator' ),
					)
				);
				?>
				<div class="wcbc-variation-layers-section options_group" <?php echo 'media' === $image_source ? '' : 'style="display:none"'; ?>>
					<p class="form-field">
						<strong><?php esc_html_e( 'Preview layers by size & colour', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description">
							<?php esc_html_e( 'Under each size, assign bed layers per colour. Headboard styles and storage options each have their own images per colour — selecting them on the storefront replaces the matching preview layers (including bed base).', 'wc-bed-configurator' ); ?>
						</span>
					</p>
					<?php self::render_size_colour_layer_sets( $size_options, $colour_options, WCBC_Config::headboard_options_for_admin( $config ), WCBC_Config::storage_options_for_admin( $config ), $variation_media, $layer_labels ); ?>
				</div>
				<div class="options_group">
					<p class="form-field">
						<strong><?php esc_html_e( 'Option swatch images', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description"><?php esc_html_e( 'Thumbnails shown in the configurator accordion for each size, colour, headboard, etc.', 'wc-bed-configurator' ); ?></span>
					</p>
					<?php foreach ( WCBC_Config::visible_groups( $config ) as $group ) : ?>
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
									$option_id = $option['id'];
									$img_id    = 0;
									if ( ! empty( $swatch_media[ $group['id'] ][ $option_id ] ) ) {
										$img_id = (int) $swatch_media[ $group['id'] ][ $option_id ];
									} elseif ( ! empty( $option['image'] ) ) {
										$img_id = attachment_url_to_postid( $option['image'] );
									}
									$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : ( ! empty( $option['image'] ) ? $option['image'] : '' );
									?>
									<tr>
										<td><?php echo esc_html( trim( $option['label'] . ' ' . $option['sublabel'] ) ); ?></td>
										<td>
											<img class="wcbc-option-thumb <?php echo $img_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $img_url ); ?>" alt="" />
										</td>
										<td>
											<input
												type="hidden"
												class="wcbc-option-image-id"
												name="wcbc_option_images[<?php echo esc_attr( $group['id'] ); ?>][<?php echo esc_attr( $option['id'] ); ?>]"
												value="<?php echo esc_attr( $img_id ); ?>"
												data-group-id="<?php echo esc_attr( $group['id'] ); ?>"
												data-option-id="<?php echo esc_attr( $option_id ); ?>"
											/>
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
	 * @param array<int,array<string,string>>                    $storage_options Storage options.
	 * @param array{size:array<string,array<string,mixed>>,colour:array<string,array<string,array<string,int>>>,headboard:array<string,array<string,array<string,int>>>,storage:array<string,array<string,array<string,array<string,int>>>>>} $saved Saved media.
	 * @param array<string,string>                               $layer_labels Layer labels.
	 */
	private static function render_size_colour_layer_sets( $size_options, $colour_options, $headboard_options, $storage_options, $saved, $layer_labels ) {
		$colour_slots   = WCBC_Config::colour_layer_slots();
		$storage_slots  = WCBC_Config::storage_layer_slots();
		$saved_headboards = isset( $saved['headboard'] ) ? $saved['headboard'] : array();
		$saved_storage    = isset( $saved['storage'] ) ? $saved['storage'] : array();

		foreach ( $size_options as $size ) {
			$size_id    = $size['id'];
			$size_title = trim( $size['label'] . ( ! empty( $size['sublabel'] ) ? ' (' . $size['sublabel'] . ')' : '' ) );
			$size_colours = isset( $saved['colour'][ $size_id ] ) ? $saved['colour'][ $size_id ] : array();
			$size_headboards = isset( $saved_headboards[ $size_id ] ) ? $saved_headboards[ $size_id ] : array();
			?>
			<details class="wcbc-variation-layer-set wcbc-size-layer-set">
				<summary><?php echo esc_html( $size_title ); ?></summary>

				<div class="wcbc-size-colour-layers">
					<h4><?php esc_html_e( 'Layers by colour', 'wc-bed-configurator' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Assign Bed Legs, Bed Headboard, Bed Storage Back, Bed Base, and Bed Storage 1–4 for each colour at this size.', 'wc-bed-configurator' ); ?></p>
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

				<div class="wcbc-size-headboard-layers">
					<h4><?php esc_html_e( 'Headboard by style & colour', 'wc-bed-configurator' ); ?></h4>
					<p class="description"><?php esc_html_e( 'For each headboard style, assign one preview image per colour. The selected style replaces the bed headboard on the storefront.', 'wc-bed-configurator' ); ?></p>
					<?php foreach ( $headboard_options as $headboard ) : ?>
						<?php
						$headboard_id    = $headboard['id'];
						$headboard_title = trim( $headboard['label'] . ( ! empty( $headboard['sublabel'] ) ? ' ' . $headboard['sublabel'] : '' ) );
						$style_colours   = isset( $size_headboards[ $headboard_id ] ) ? $size_headboards[ $headboard_id ] : array();
						?>
						<details class="wcbc-variation-layer-set wcbc-headboard-style-set">
							<summary><?php echo esc_html( $headboard_title ); ?></summary>
							<div class="wcbc-layer-media-grid">
								<?php foreach ( $colour_options as $colour ) : ?>
									<?php
									$colour_id    = $colour['id'];
									$colour_title = trim( $colour['label'] . ( ! empty( $colour['sublabel'] ) ? ' — ' . $colour['sublabel'] : '' ) );
									$attachment_id = isset( $style_colours[ $colour_id ] ) ? (int) $style_colours[ $colour_id ] : 0;
									$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
									$input_name    = 'wcbc_variation_layers[headboard][' . esc_attr( $size_id ) . '][' . esc_attr( $headboard_id ) . '][' . esc_attr( $colour_id ) . ']';
									$label         = sprintf(
										/* translators: %s: colour name */
										__( 'Bed Headboard — %s', 'wc-bed-configurator' ),
										$colour_title
									);
									?>
									<div class="wcbc-layer-media-item">
										<label><?php echo esc_html( $label ); ?></label>
										<img class="wcbc-layer-media-preview <?php echo $preview_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $preview_url ); ?>" alt="" />
										<input
											type="hidden"
											class="wcbc-variation-layer-input"
											name="<?php echo esc_attr( $input_name ); ?>"
											value="<?php echo esc_attr( $attachment_id ); ?>"
											data-layer-type="headboard"
											data-size-id="<?php echo esc_attr( $size_id ); ?>"
											data-style-id="<?php echo esc_attr( $headboard_id ); ?>"
											data-colour-id="<?php echo esc_attr( $colour_id ); ?>"
										/>
										<button type="button" class="button wcbc-upload-variation-layer" data-title="<?php echo esc_attr( $size_title . ' — ' . $headboard_title . ' — ' . $colour_title ); ?>">
											<?php esc_html_e( 'Select image', 'wc-bed-configurator' ); ?>
										</button>
										<button type="button" class="button wcbc-remove-variation-layer" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
											<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>

				<div class="wcbc-size-storage-layers">
					<h4><?php esc_html_e( 'Base & storage by option & colour', 'wc-bed-configurator' ); ?></h4>
					<p class="description"><?php esc_html_e( 'For each storage option (No Drawers, Ottoman, 2 Drawers, etc.), assign the bed base and drawer layers per colour. Selecting a storage option on the storefront replaces these layers for that size.', 'wc-bed-configurator' ); ?></p>
					<?php foreach ( $storage_options as $storage ) : ?>
						<?php
						$storage_id    = $storage['id'];
						$storage_title = trim( $storage['label'] . ( ! empty( $storage['sublabel'] ) ? ' ' . $storage['sublabel'] : '' ) );
						$size_storage  = isset( $saved_storage[ $size_id ] ) ? $saved_storage[ $size_id ] : array();
						$option_colours = isset( $size_storage[ $storage_id ] ) ? $size_storage[ $storage_id ] : array();
						?>
						<details class="wcbc-variation-layer-set wcbc-storage-option-set">
							<summary><?php echo esc_html( $storage_title ); ?></summary>
							<?php foreach ( $colour_options as $colour ) : ?>
								<?php
								$colour_id      = $colour['id'];
								$colour_title   = trim( $colour['label'] . ( ! empty( $colour['sublabel'] ) ? ' — ' . $colour['sublabel'] : '' ) );
								$storage_layers = isset( $option_colours[ $colour_id ] ) ? $option_colours[ $colour_id ] : array();
								?>
								<details class="wcbc-variation-layer-set wcbc-storage-colour-set">
									<summary><?php echo esc_html( $colour_title ); ?></summary>
									<div class="wcbc-layer-media-grid">
										<?php foreach ( $storage_slots as $layer ) : ?>
											<?php self::render_layer_picker( 'storage', $size_id, $colour_id, $layer, $storage_layers, $layer_labels, $size_title . ' / ' . $storage_title . ' / ' . $colour_title, $storage_id ); ?>
										<?php endforeach; ?>
									</div>
								</details>
							<?php endforeach; ?>
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
	 * @param string               $storage_id Storage id (storage dimension only).
	 */
	private static function render_layer_picker( $dimension, $size_id, $colour_id, $layer, $layers, $layer_labels, $context_title, $storage_id = '' ) {
		$attachment_id = isset( $layers[ $layer ] ) ? (int) $layers[ $layer ] : 0;
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$label         = isset( $layer_labels[ $layer ] ) ? $layer_labels[ $layer ] : $layer;

		if ( 'colour' === $dimension ) {
			$input_name = 'wcbc_variation_layers[colour][' . esc_attr( $size_id ) . '][' . esc_attr( $colour_id ) . '][' . esc_attr( $layer ) . ']';
		} elseif ( 'storage' === $dimension ) {
			$input_name = 'wcbc_variation_layers[storage][' . esc_attr( $size_id ) . '][' . esc_attr( $storage_id ) . '][' . esc_attr( $colour_id ) . '][' . esc_attr( $layer ) . ']';
		} else {
			$input_name = 'wcbc_variation_layers[size][' . esc_attr( $size_id ) . '][' . esc_attr( $layer ) . ']';
		}
		?>
		<div class="wcbc-layer-media-item">
			<label><?php echo esc_html( $label ); ?></label>
			<img class="wcbc-layer-media-preview <?php echo $preview_url ? '' : 'is-empty'; ?>" src="<?php echo esc_url( $preview_url ); ?>" alt="" />
			<input
				type="hidden"
				class="wcbc-variation-layer-input"
				name="<?php echo esc_attr( $input_name ); ?>"
				value="<?php echo esc_attr( $attachment_id ); ?>"
				data-layer-type="<?php echo esc_attr( $dimension ); ?>"
				data-size-id="<?php echo esc_attr( $size_id ); ?>"
				data-colour-id="<?php echo esc_attr( $colour_id ); ?>"
				data-layer="<?php echo esc_attr( $layer ); ?>"
				<?php if ( $storage_id ) : ?>
					data-storage-id="<?php echo esc_attr( $storage_id ); ?>"
				<?php endif; ?>
			/>
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
	 * Backup save on product post save (classic + block editor).
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public static function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_POST['wcbc_admin_panel'] ) && empty( $_POST['wcbc_option_images'] ) && empty( $_POST['wcbc_variation_layers'] ) ) {
			return;
		}
		self::save( $post_id );
	}

	/**
	 * AJAX: save one option swatch image immediately.
	 */
	public static function ajax_save_option_swatch() {
		check_ajax_referer( 'wcbc_admin_media', 'nonce' );

		$product_id    = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$group_id      = isset( $_POST['group_id'] ) ? sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$option_id     = isset( $_POST['option_id'] ) ? sanitize_text_field( wp_unslash( $_POST['option_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wc-bed-configurator' ) ), 403 );
		}

		$saved = WCBC_Config::set_option_swatch_attachment( $product_id, $group_id, $option_id, $attachment_id );
		if ( ! $saved ) {
			wp_send_json_error( array( 'message' => __( 'Could not save swatch.', 'wc-bed-configurator' ) ), 500 );
		}

		$url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

		wp_send_json_success(
			array(
				'attachment_id' => $attachment_id,
				'url'           => $url ? $url : '',
			)
		);
	}

	/**
	 * AJAX: save one variation layer image immediately.
	 */
	public static function ajax_save_variation_layer() {
		check_ajax_referer( 'wcbc_admin_media', 'nonce' );

		$product_id    = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$layer_type    = isset( $_POST['layer_type'] ) ? sanitize_text_field( wp_unslash( $_POST['layer_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$size_id       = isset( $_POST['size_id'] ) ? sanitize_text_field( wp_unslash( $_POST['size_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'headboard' === $layer_type ) {
			$key_a = isset( $_POST['style_id'] ) ? sanitize_text_field( wp_unslash( $_POST['style_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$key_b = isset( $_POST['colour_id'] ) ? sanitize_text_field( wp_unslash( $_POST['colour_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$key_c = '';
		} elseif ( 'storage' === $layer_type ) {
			$key_a = isset( $_POST['storage_id'] ) ? sanitize_text_field( wp_unslash( $_POST['storage_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$key_b = isset( $_POST['colour_id'] ) ? sanitize_text_field( wp_unslash( $_POST['colour_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$key_c = isset( $_POST['layer'] ) ? sanitize_text_field( wp_unslash( $_POST['layer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! $key_c && isset( $_POST['layer_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$key_c = sanitize_text_field( wp_unslash( $_POST['layer_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		} else {
			$key_a = isset( $_POST['colour_id'] ) ? sanitize_text_field( wp_unslash( $_POST['colour_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$key_b = isset( $_POST['layer_key'] ) ? sanitize_text_field( wp_unslash( $_POST['layer_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$key_c = '';
			if ( ! $key_b && isset( $_POST['layer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$key_b = sanitize_text_field( wp_unslash( $_POST['layer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}
		}

		if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wc-bed-configurator' ) ), 403 );
		}

		$saved = WCBC_Config::set_variation_layer_attachment( $product_id, $layer_type, $size_id, $key_a, $key_b, $attachment_id, $key_c );
		if ( ! $saved ) {
			wp_send_json_error( array( 'message' => __( 'Could not save layer image.', 'wc-bed-configurator' ) ), 500 );
		}

		$url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

		wp_send_json_success(
			array(
				'attachment_id' => $attachment_id,
				'url'           => $url ? $url : '',
			)
		);
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

		if ( isset( $_POST['wcbc_variation_layers_save'] ) || isset( $_POST['wcbc_variation_layers'] ) || isset( $_POST['wcbc_admin_panel'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$posted = ( isset( $_POST['wcbc_variation_layers'] ) && is_array( $_POST['wcbc_variation_layers'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
				? wp_unslash( $_POST['wcbc_variation_layers'] ) // phpcs:ignore WordPress.Security.NonceVerification
				: array();
			$incoming = WCBC_Config::sanitize_variation_layer_media_post( $posted );
			$existing = WCBC_Config::get_variation_layer_media( $post_id );
			$merged   = WCBC_Config::merge_variation_layer_media( $existing, $incoming );
			update_post_meta( $post_id, WCBC_Config::VARIATION_LAYER_MEDIA_KEY, $merged );
		}

		if ( isset( $_POST['wcbc_base_price'] ) && '' !== $_POST['wcbc_base_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$base = (float) wc_clean( wp_unslash( $_POST['wcbc_base_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$config = get_post_meta( $post_id, WCBC_Config::META_KEY, true );
		if ( ! is_array( $config ) || empty( $config['groups'] ) ) {
			$config = WCBC_Config::get_default_config();
		}

		if ( isset( $_POST['wcbc_option_images'] ) && is_array( $_POST['wcbc_option_images'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$posted_swatches = wp_unslash( $_POST['wcbc_option_images'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$existing        = WCBC_Config::get_option_swatch_media( $post_id );
			$merged_swatches = WCBC_Config::merge_option_swatch_media( $existing, $posted_swatches );
			update_post_meta( $post_id, WCBC_Config::OPTION_SWATCH_MEDIA_KEY, $merged_swatches );
			$config = WCBC_Config::merge_colour_group( $config );
			$config = self::apply_option_images_from_post( $config, $posted_swatches );
		}

		$config = WCBC_Config::merge_colour_group( $config );

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
		if ( ! is_array( $posted ) ) {
			return $config;
		}
		foreach ( $config['groups'] as $gi => $group ) {
			$gid = $group['id'];
			if ( empty( $posted[ $gid ] ) || ! is_array( $posted[ $gid ] ) ) {
				continue;
			}
			foreach ( $group['options'] as $oi => $option ) {
				$oid = $option['id'];
				if ( ! array_key_exists( $oid, $posted[ $gid ] ) ) {
					continue;
				}
				$attachment_id = absint( $posted[ $gid ][ $oid ] );
				if ( $attachment_id ) {
					$url = wp_get_attachment_image_url( $attachment_id, 'full' );
					if ( $url ) {
						$config['groups'][ $gi ]['options'][ $oi ]['image'] = $url;
					}
				}
			}
		}
		return $config;
	}
}
