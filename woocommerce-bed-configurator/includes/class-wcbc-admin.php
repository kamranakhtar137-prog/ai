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
		$json    = wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$import_script     = WCBC_Cache_API::import_script();
		$import_all_script = WCBC_Cache_API::import_all_script();
		$layer_media       = WCBC_Config::get_layer_media( $post->ID );
		$image_source      = WCBC_Config::get_image_source( $post->ID );
		$layer_labels      = WCBC_Config::get_layer_labels();
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
							'auto'     => __( 'Auto — Happy Beds cache / CDN', 'wc-bed-configurator' ),
							'happybeds' => __( 'Happy Beds only', 'wc-bed-configurator' ),
							'media'    => __( 'WordPress Media Library only', 'wc-bed-configurator' ),
							'hybrid'   => __( 'Happy Beds + Media Library overrides', 'wc-bed-configurator' ),
						),
						'description' => __( 'Media Library mode uses the layer images below. Hybrid replaces individual layers when you set a media image.', 'wc-bed-configurator' ),
					)
				);
				?>
				<div class="wcbc-layer-media-section options_group">
					<p class="form-field">
						<strong><?php esc_html_e( 'Preview layer images (Media Library)', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description"><?php esc_html_e( 'Upload one image per preview layer. Used when source is Media Library or Hybrid.', 'wc-bed-configurator' ); ?></span>
					</p>
					<div class="wcbc-layer-media-grid">
						<?php foreach ( WCBC_Config::get_layers() as $layer ) : ?>
							<?php
							$attachment_id = isset( $layer_media[ $layer ] ) ? (int) $layer_media[ $layer ] : 0;
							$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
							$label         = isset( $layer_labels[ $layer ] ) ? $layer_labels[ $layer ] : $layer;
							?>
							<div class="wcbc-layer-media-item">
								<label><?php echo esc_html( $label ); ?></label>
								<img
									class="wcbc-layer-media-preview <?php echo $preview_url ? '' : 'is-empty'; ?>"
									src="<?php echo esc_url( $preview_url ); ?>"
									alt=""
								/>
								<input type="hidden" name="wcbc_layer_media[<?php echo esc_attr( $layer ); ?>]" value="<?php echo esc_attr( $attachment_id ); ?>" />
								<button type="button" class="button wcbc-upload-layer" data-layer="<?php echo esc_attr( $layer ); ?>" data-title="<?php echo esc_attr( $label ); ?>">
									<?php esc_html_e( 'Select image', 'wc-bed-configurator' ); ?>
								</button>
								<button type="button" class="button wcbc-remove-layer" <?php echo $attachment_id ? '' : 'style="display:none"'; ?>>
									<?php esc_html_e( 'Remove', 'wc-bed-configurator' ); ?>
								</button>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="options_group">
					<p class="form-field">
						<strong><?php esc_html_e( 'Option swatch images (Media Library)', 'wc-bed-configurator' ); ?></strong><br />
						<span class="description"><?php esc_html_e( 'Set swatch thumbnails for each configurator option. Saves into the JSON config automatically.', 'wc-bed-configurator' ); ?></span>
					</p>
					<?php foreach ( $config['groups'] as $group ) : ?>
						<h4 style="margin:16px 0 8px;padding:0 12px;"><?php echo esc_html( $group['label'] ); ?></h4>
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
				<p class="form-field">
					<label for="wcbc_config_json"><?php esc_html_e( 'Configurator JSON (advanced)', 'wc-bed-configurator' ); ?></label>
					<textarea id="wcbc_config_json" name="wcbc_config_json" rows="18" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $json ); ?></textarea>
					<span class="description"><?php esc_html_e( 'Edit groups, options, images, and default selections. Invalid JSON is ignored on save.', 'wc-bed-configurator' ); ?></span>
				</p>
				<p class="form-field">
					<strong><?php esc_html_e( 'Happy Beds images — dynamic variations', 'wc-bed-configurator' ); ?></strong><br />
					<?php esc_html_e( 'The configurator already changes image URLs when you pick size, colour, headboard, depth, or storage. Each variation needs its image file saved on your server.', 'wc-bed-configurator' ); ?>
					<br /><br />
					<strong><?php esc_html_e( 'Quick import (25 files)', 'wc-bed-configurator' ); ?></strong> —
					<?php esc_html_e( 'Beige velvet, Cornell lined, 14 inch, 2 drawers only. Good for testing one look per size.', 'wc-bed-configurator' ); ?>
					<br />
					<textarea id="wcbc_import_script" readonly rows="8" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea( $import_script ); ?></textarea>
					<button type="button" class="button" id="wcbc-copy-import-script"><?php esc_html_e( 'Copy quick import', 'wc-bed-configurator' ); ?></button>
					<br /><br />
					<strong style="color:#b45309;"><?php esc_html_e( 'Full import (recommended)', 'wc-bed-configurator' ); ?></strong> —
					<?php esc_html_e( 'All colours, headboards, depths & storage options. Required for images to update when customers change variations. Takes ~15–30 min on happybeds.co.uk Console.', 'wc-bed-configurator' ); ?>
					<br />
					<textarea id="wcbc_import_all_script" readonly rows="8" style="width:100%;font-family:monospace;font-size:11px;"><?php echo esc_textarea( $import_all_script ); ?></textarea>
					<button type="button" class="button button-primary" id="wcbc-copy-import-all-script"><?php esc_html_e( 'Copy full import (all variations)', 'wc-bed-configurator' ); ?></button>
					<br /><br />
					<?php esc_html_e( 'Paste on https://www.happybeds.co.uk/build-your-own-bed → DevTools Console → Enter.', 'wc-bed-configurator' ); ?>
					<br />
					<?php
					printf(
						esc_html__( 'Cache status: %s', 'wc-bed-configurator' ),
						WCBC_Layer_Serve::cache_has_files()
							? '<span style="color:green;">' . esc_html__( 'imported', 'wc-bed-configurator' ) . '</span>'
							: '<span style="color:#b45309;">' . esc_html__( 'not imported yet', 'wc-bed-configurator' ) . '</span>'
					);
					?>
				</p>
				<p class="form-field">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wcbc-docs' ) ); ?>" class="button" onclick="alert('See plugin README.md for JSON schema and demo images in demo-images/ folder.');return false;">
						<?php esc_html_e( 'View documentation', 'wc-bed-configurator' ); ?>
					</a>
					<button type="button" class="button" id="wcbc-reset-defaults"><?php esc_html_e( 'Reset to demo defaults', 'wc-bed-configurator' ); ?></button>
				</p>
			</div>
		</div>
		<script>
		(function(){
			var defaults = <?php echo wp_json_encode( WCBC_Config::get_default_config() ); ?>;
			document.getElementById('wcbc-reset-defaults')?.addEventListener('click', function(){
				if (confirm('Reset configurator JSON to plugin demo defaults?')) {
					document.getElementById('wcbc_config_json').value = JSON.stringify(defaults, null, 2);
				}
			});
			document.getElementById('wcbc-copy-import-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_script', 'Quick import copied. Paste on happybeds.co.uk Console.');
			});
			document.getElementById('wcbc-copy-import-all-script')?.addEventListener('click', function(){
				copyTextarea('wcbc_import_all_script', 'Full import copied. Paste on happybeds.co.uk Console. Wait for FULL import complete.');
			});
			function copyTextarea(id, msg) {
				var ta = document.getElementById(id);
				if (!ta) return;
				ta.select();
				ta.setSelectionRange(0, 99999);
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
	 * Save product meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( $post_id ) {
		$enabled = isset( $_POST['wcbc_enabled'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification
		update_post_meta( $post_id, WCBC_Config::ENABLED_KEY, $enabled );

		if ( isset( $_POST['wcbc_image_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$source = sanitize_text_field( wp_unslash( $_POST['wcbc_image_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( in_array( $source, array( 'auto', 'happybeds', 'media', 'hybrid' ), true ) ) {
				update_post_meta( $post_id, WCBC_Config::IMAGE_SOURCE_KEY, $source );
			}
		}

		if ( isset( $_POST['wcbc_layer_media'] ) && is_array( $_POST['wcbc_layer_media'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$layer_media = array();
			foreach ( WCBC_Config::get_layers() as $layer ) {
				if ( ! empty( $_POST['wcbc_layer_media'][ $layer ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$layer_media[ $layer ] = absint( wp_unslash( $_POST['wcbc_layer_media'][ $layer ] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}
			update_post_meta( $post_id, WCBC_Config::LAYER_MEDIA_KEY, $layer_media );
		}

		if ( isset( $_POST['wcbc_base_price'] ) && '' !== $_POST['wcbc_base_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$base = (float) wc_clean( wp_unslash( $_POST['wcbc_base_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$config = null;
		if ( ! empty( $_POST['wcbc_config_json'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$raw    = wp_unslash( $_POST['wcbc_config_json'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$config = json_decode( $raw, true );
		}

		if ( ! is_array( $config ) || empty( $config['groups'] ) ) {
			$config = WCBC_Config::get_product_config( $post_id );
			unset( $config['product_id'], $config['layer_media'], $config['image_source'], $config['layers'] );
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
